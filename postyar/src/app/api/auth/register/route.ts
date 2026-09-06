// POSTYAR registration API — email/password registration; mobile is optional.
// Username is required for the alternate password-login identifier. Mobile is
// retained in the user record/profile when provided, but never required for
// account creation and never used by the primary login form.
import { NextResponse } from "next/server";
import { z } from "zod";
import { db } from "@/lib/db";
import { hashPassword, newReferralCode, clientIp, audit, createSession } from "@/lib/server/auth";
import { isValidEmail, isValidIranMobile, normalizeMobile } from "@/lib/persian";
import { rateLimit } from "@/lib/security/cache";
import { ensurePlansSeeded } from "@/lib/payments/plans";
import { randomToken } from "@/lib/security/crypto";

const USERNAME_RE = /^[a-zA-Z0-9_.-]{3,32}$/;
const Schema = z.object({
  firstName: z.string().trim().min(2, "نام باید حداقل ۲ نویسه باشد.").max(60),
  lastName: z.string().trim().min(2, "نام خانوادگی باید حداقل ۲ نویسه باشد.").max(80),
  username: z.string().trim().min(3, "نام کاربری باید حداقل ۳ نویسه باشد.").max(32),
  email: z.string().trim().email("ایمیل نامعتبر است."),
  mobile: z.string().trim().optional().nullable().default(null),
  password: z.string().min(8, "رمز عبور باید حداقل ۸ نویسه باشد.").max(128),
  activityType: z.enum(["personal", "business", "marketer", "service", "media", "other"]),
  businessName: z.string().max(120).optional().default(""),
  referralCode: z.string().max(12).optional(),
});

export async function POST(req: Request) {
  const ip = clientIp(req);
  // Prevent one client/proxy bucket from being locked for an hour; keep a
  // meaningful anti-abuse control while allowing legitimate signup retries.
  const rl = await rateLimit({ key: `register:v3:${ip}`, limit: 20, windowMs: 15 * 60 * 1000, critical: true });
  if (!rl.ok) return NextResponse.json({ errorFa: "تعداد تلاش‌ها بیش از حد مجاز است. ۱۵ دقیقه بعد تلاش کنید." }, { status: 429 });

  let body: unknown;
  try { body = await req.json(); } catch {
    return NextResponse.json({ errorFa: "بدنه درخواست نامعتبر است." }, { status: 400 });
  }
  const parsed = Schema.safeParse(body);
  if (!parsed.success) return NextResponse.json({ errorFa: parsed.error.issues[0]?.message ?? "ورودی نامعتبر است." }, { status: 400 });

  const { firstName, lastName, username, email, mobile, password, activityType, businessName, referralCode } = parsed.data;
  const normalizedEmail = email.toLowerCase();
  const normalizedUsername = username.toLowerCase();
  if (!USERNAME_RE.test(username)) return NextResponse.json({ errorFa: "نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد، نقطه، خط تیره و زیرخط باشد." }, { status: 400 });
  if (!isValidEmail(normalizedEmail)) return NextResponse.json({ errorFa: "ایمیل نامعتبر است." }, { status: 400 });

  let normMobile: string | null = null;
  if (mobile) {
    normMobile = normalizeMobile(mobile);
    if (!isValidIranMobile(normMobile)) return NextResponse.json({ errorFa: "شماره موبایل ایرانی وارد کنید (۰۹XXXXXXXXX)." }, { status: 400 });
  }

  const [dupEmail, dupUsername] = await Promise.all([
    db.user.findUnique({ where: { email: normalizedEmail }, select: { id: true } }),
    db.$queryRawUnsafe<Array<{ id: string }>>("SELECT `id` FROM `User` WHERE `username` = ? LIMIT 1", normalizedUsername),
  ]);
  if (dupEmail) return NextResponse.json({ errorFa: "این ایمیل قبلاً ثبت شده است." }, { status: 409 });
  if (dupUsername.length) return NextResponse.json({ errorFa: "این نام کاربری قبلاً ثبت شده است." }, { status: 409 });
  if (normMobile) {
    const dupMobile = await db.user.findUnique({ where: { mobile: normMobile }, select: { id: true } });
    if (dupMobile) return NextResponse.json({ errorFa: "این موبایل قبلاً ثبت شده است." }, { status: 409 });
  }

  let referredById: string | undefined;
  if (referralCode) {
    const ref = await db.user.findUnique({ where: { referralCode: referralCode.toUpperCase() }, select: { id: true } });
    if (!ref) return NextResponse.json({ errorFa: "کد معرف نامعتبر است." }, { status: 400 });
    referredById = ref.id;
  }

  await ensurePlansSeeded();
  const freePlan = await db.plan.findUnique({ where: { code: "free" } });
  if (!freePlan) {
    return NextResponse.json({ errorFa: "در حال حاضر امکان ایجاد حساب وجود ندارد. پلن پایه سامانه یافت نشد." }, { status: 503 });
  }

  const passwordHash = await hashPassword(password);
  const referral = await newReferralCode();
  const userId = randomToken(18);
  const now = new Date();
  const endsAt = new Date(now);
  endsAt.setMonth(endsAt.getMonth() + 1);

  try {
    await db.$transaction(async (tx) => {
      await tx.$executeRawUnsafe(
        `INSERT INTO \`User\` (\`id\`,\`email\`,\`username\`,\`mobile\`,\`passwordHash\`,\`firstName\`,\`lastName\`,\`activityType\`,\`businessName\`,\`role\`,\`isSuperAdmin\`,\`status\`,\`referralCode\`,\`referredById\`,\`createdAt\`,\`updatedAt\`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)`,
        userId, normalizedEmail, normalizedUsername, normMobile, passwordHash, firstName, lastName,
        activityType, businessName, "user", false, "active", referral, referredById ?? null, now, now,
      );
      await tx.profile.create({ data: { userId } });
      await tx.subscription.create({
        data: { userId, planId: freePlan.id, status: "active", activeKey: `${userId}:${freePlan.id}`, startedAt: now, endsAt, usedQuota: "{}" },
      });
      await audit({
        tx,
        actor: "user",
        action: "register",
        targetType: "user",
        targetId: userId,
        ip,
        meta: { email: normalizedEmail, username: normalizedUsername, hasMobile: Boolean(normMobile), freePlanActivated: true },
      });
    });
  } catch (e) {
    // Race-safe fallback for unique email/username/mobile constraints.
    const msg = e instanceof Error ? e.message : String(e);
    if (/username/i.test(msg)) return NextResponse.json({ errorFa: "این نام کاربری قبلاً ثبت شده است." }, { status: 409 });
    if (/email/i.test(msg)) return NextResponse.json({ errorFa: "این ایمیل قبلاً ثبت شده است." }, { status: 409 });
    if (/mobile/i.test(msg)) return NextResponse.json({ errorFa: "این موبایل قبلاً ثبت شده است." }, { status: 409 });
    throw e;
  }

  await createSession(userId, ip, req.headers.get("user-agent"));
  return NextResponse.json({ ok: true, userId, user: { id: userId, firstName, role: "user", username: normalizedUsername } });
}
