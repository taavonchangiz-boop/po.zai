// POSTYAR OTP verify API. OTP is not an authentication method for login.
// Register/reset legacy verification may issue a short-lived verification token.
import { NextResponse } from "next/server";
import { z } from "zod";
import { verifyOtp, clientIp } from "@/lib/server/auth";
import { randomToken, hashToken } from "@/lib/security/crypto";

const Schema = z.object({
  mobile: z.string(),
  code: z.string(),
  purpose: z.enum(["register", "reset"]).default("reset"),
});

export async function POST(req: Request) {
  const ip = clientIp(req);
  let body: unknown;
  try { body = await req.json(); } catch {
    return NextResponse.json({ errorFa: "بدنه درخواست نامعتبر است." }, { status: 400 });
  }
  const parsed = Schema.safeParse(body);
  if (!parsed.success) {
    return NextResponse.json({ errorFa: parsed.error.issues[0]?.message ?? "ورودی نامعتبر است." }, { status: 400 });
  }
  const { mobile, code, purpose } = parsed.data;
  const r = await verifyOtp(mobile, code, purpose, ip);
  if (!r.ok) {
    return NextResponse.json({ errorFa: r.errorFa }, { status: 400 });
  }

  // register/reset legacy verification: issue short-lived verification token (5 minutes).
  const verifyToken = randomToken(32);
  // store hash in cache keyed by mobile+purpose
  const { cache } = await import("@/lib/security/cache");
  await cache.set(`verify:${purpose}:${mobile}`, hashToken(verifyToken), 5 * 60 * 1000);
  return NextResponse.json({ ok: true, purpose, verifyToken, mobile });
}
