# MASTER PROMPT — پٌست‌یار (Postyar)

## فرمان حاکم
این مخزن مرجع اجرایی ساخت محصول «پٌست‌یار (Postyar)» است. دو شاخه `Asovin/` و `postyar/` فقط منابع مرجع قابلیت، کد، داده، UI/UX و asset هستند. محصول نهایی از صفر و به‌صورت مستقل، با معماری جدید و امن ساخته می‌شود.

نام محصول فقط «پٌست‌یار» و «Postyar» است. نام‌های Asovin/آسوین، Bale، Rubika یا هر برند ثالث به‌عنوان نام محصول یا هویت برند ممنوع‌اند.

## محیط هدف
- Node.js: 22.23.2
- Hosting: cPanel
- Terminal: دارد
- SSH: ندارد
- Database: MySQL/MariaDB
- Runtime/Framework هدف: Node.js + TypeScript + Next.js + Prisma

## قوانین غیرقابل مذاکره
1. هیچ فایل، dependency، API، route یا قابلیت موجود را بدون بررسی واقعی فرض نکن.
2. قبل از تغییر هر فایل آن را بخوان و اثر تغییر را تحلیل کن.
3. ساخت محصول باید file-by-file و test-driven باشد.
4. در هر اجرا فقط یک Phase مجاز است.
5. عبور به Phase بعد فقط بعد از PASS واقعی مرحله مجاز است.
6. تست اجرا نشده = PASS ممنوع.
7. خطای Build/Typecheck/Lint/Test/Migration/Security باید همان Phase را متوقف کند.
8. هیچ secret واقعی داخل repository قرار نده.
9. عملیات state-changing با GET ممنوع.
10. داده مالی باید integer، atomic، idempotent و audit-able باشد.
11. Brand asset باید visually audit شود.
12. logo جدید، redraw، recreation یا تغییر شکل logo ممنوع است.
13. Hero/Mascot فقط asset تأییدشده Asovin است.
14. Vazirmatn موجود در منابع باید فونت پیش‌فرض فارسی باشد.
15. تمام UI فارسی، RTL و Jalali-first باشد.
16. پروژه باید برای cPanel و بدون SSH قابل استقرار باشد.

## روش اجرا
هر Phase این چرخه را طی کند:
DISCOVER → PLAN → IMPLEMENT → TEST → AUDIT → REPORT → GATE

گزارش هر اجرا باید شامل:
- Phase
- هدف
- فایل‌های خوانده‌شده
- فایل‌های ایجادشده
- فایل‌های اصلاح‌شده
- فایل‌های حذف‌شده
- تست‌های اجراشده
- خروجی واقعی تست
- ریسک‌های باقی‌مانده
- وضعیت PASS/FAIL/BLOCKED

## شروع
در شروع فقط Phase 0 را انجام بده و تا Gate بعدی هیچ قابلیت production جدیدی نساز. جزئیات مراحل و معیارها در سایر فایل‌های `AI-SPEC/` الزام‌آور است.
