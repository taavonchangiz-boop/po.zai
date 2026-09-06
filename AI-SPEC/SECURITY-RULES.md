# SECURITY RULES — پٌست‌یار

## اصل
Security by default و deny by default. هیچ کنترل امنیتی نباید فقط در UI اعمال شود.

## الزامی
- اعتبارسنجی ورودی با schema در مرزهای سیستم
- خروجی‌گذاری امن و کنترل XSS
- ORM/parameterized queries؛ raw SQL فقط با توجیه و پارامتر امن
- CSRF برای cookie-authenticated state changes
- secure, httpOnly, sameSite cookies
- session/token hashing در persistence
- password hashing استاندارد و مقاوم
- OTP hashing، expiry، attempt limit و lockout
- rate limit و brute-force protection
- RBAC و authorization در server-side handler/service
- tenant isolation در query layer
- SSRF protection برای URL-fetching
- upload validation: size, MIME sniffing, extension, filename, storage isolation
- عدم اجرای فایل آپلودی
- security headers و CSP تا حد امکان
- secret فقط environment/secret store
- webhook signature verification
- replay protection/idempotency برای webhook و payment
- transaction و uniqueness برای عملیات مالی
- audit log برای عملیات حساس
- structured logging بدون secret/PII غیرضروری
- dependency audit و حذف dependency بلااستفاده

## ممنوع
- secret در source، fixture، test artifact یا log
- mutation با GET
- اعتماد به role یا amount ارسالی client
- trust به MIME/filename client
- ذخیره plaintext password/token/OTP/API key
- bypass کردن authorization برای تست
- disable کردن security check برای سبز شدن تست

## Legacy findings
کدهای مرجع شامل الگوهای پرریسک مانند credential در config، bootstrap race، GET mutation، upload validation ضعیف، plaintext reset/OTP و rate-limit غیراتمی است. هیچ‌کدام عیناً منتقل نشوند.

## Gate
هر finding با شدت Critical یا High که پیش از release باقی بماند = RELEASE BLOCKED، مگر اینکه صراحتاً توسط کارفرما و با risk acceptance مستند تأیید شود.
