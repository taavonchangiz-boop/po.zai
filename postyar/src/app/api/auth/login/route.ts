// POSTYAR login API — email OR username + password only.
import { NextResponse } from "next/server";
import { z } from "zod";
import { db } from "@/lib/db";
import { verifyPassword, createSession, clientIp, audit } from "@/lib/server/auth";
import { rateLimit } from "@/lib/security/cache";

const Schema = z.object({
  identifier: z.string().trim().min(1, "ایمیل یا نام کاربری را وارد کنید.").max(190),
  password: z.string().min(1, "رمز عبور را وارد کنید.").max(128),
});

export async function POST(req: Request) {
  const ip = clientIp(req);
  const rl = await rateLimit({ key: `login:v2:${ip}`, limit: 10, windowMs: 15 * 60 * 1000, critical: true });
  if (!rl.ok) return NextResponse.json({ errorFa: "تلاش بیش از حد. ۱۵ دقیقه بعد امتحان کنید." }, { status: 429 });

  let body: unknown;
  try { body = await req.json(); } catch {
    return NextResponse.json({ errorFa: "بدنه درخواست نامعتبر است." }, { status: 400 });
  }
  const parsed = Schema.safeParse(body);
  if (!parsed.success) return NextResponse.json({ errorFa: parsed.error.issues[0]?.message ?? "ورودی نامعتبر است." }, { status: 400 });
  const { identifier, password } = parsed.data;
  let user = await db.user.findUnique({ where: { email: identifier.toLowerCase() } });
  if (!user) {
    const rows = await db.$queryRawUnsafe<Array<{ id: string }>>("SELECT `id` FROM `User` WHERE LOWER(`username`) = LOWER(?) LIMIT 1", identifier);
    if (rows.length) user = await db.user.findUnique({ where: { id: rows[0].id } });
  }
  if (!user || !user.passwordHash) return NextResponse.json({ errorFa: "ایمیل/نام کاربری یا رمز عبور نادرست است." }, { status: 401 });
  if (user.status === "suspended") return NextResponse.json({ errorFa: "حساب شما معلق شده است. با پشتیبانی تماس بگیرید." }, { status: 403 });
  const ok = await verifyPassword(password, user.passwordHash);
  if (!ok) {
    await audit({ actor: "user", action: "login_failed", targetType: "user", targetId: user.id, ip, meta: { identifier } });
    return NextResponse.json({ errorFa: "ایمیل/نام کاربری یا رمز عبور نادرست است." }, { status: 401 });
  }
  await createSession(user.id, ip, req.headers.get("user-agent"));
  await audit({ userId: user.id, actor: "user", action: "login", targetType: "user", targetId: user.id, ip });
  const usernameRow = await db.$queryRawUnsafe<Array<{ username: string | null }>>("SELECT `username` FROM `User` WHERE `id` = ? LIMIT 1", user.id);
  return NextResponse.json({ ok: true, user: { id: user.id, firstName: user.firstName, role: user.role, username: usernameRow[0]?.username ?? null } });
}
