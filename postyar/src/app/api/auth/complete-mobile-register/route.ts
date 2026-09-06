// Legacy endpoint intentionally disabled. Registration is now email/username + password
// with an optional mobile number; OTP is retained only for password recovery.
import { NextResponse } from "next/server";
export async function POST() {
  return NextResponse.json({ errorFa: "ثبت‌نام با کد یکبار مصرف غیرفعال است. از فرم ثبت‌نام اصلی استفاده کنید." }, { status: 410 });
}
