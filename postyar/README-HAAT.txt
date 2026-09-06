پُست‌یار — بسته Production برای cPanel / Setup Node.js
========================================================

این بسته حاوی Next.js standalone آماده اجراست و برای راه‌اندازی به
npm install یا bun install نیاز ندارد.

تنظیمات cPanel / Setup Node.js
------------------------------
- Node.js: نسخه 22
- Application mode: Production
- Application root: همان پوشه‌ای که ZIP در آن Extract می‌شود
- Application startup file: app.js
- Run npm install: خیر
- Run build: خیر

Environment Variables
---------------------
در Setup Node.js → Environment Variables متغیرهای production را وارد کنید.
این متغیرها بر فایل .env اولویت دارند.
فایل .env در ZIP توزیع نمی‌شود.

حداقل متغیرهای ضروری برای استفاده کامل:
- NODE_ENV=production
- DATABASE_URL=mysql://USER:PASSWORD@HOST:3306/DATABASE
- POSTYAR_MASTER_KEY=<64 hex chars / 32 bytes>
- POSTYAR_JWT_SECRET=<long random secret>
- POSTYAR_CRON_SECRET=<long random secret>
- POSTYAR_PUBLIC_BASE_URL=https://دامنه-واقعی-شما
- POSTYAR_TRUST_PROXY=1  (در صورت قرارگیری پشت Proxy/Passenger)

نکته مهم: اگر این Variables را قبلاً در Setup Node.js ساخته‌اید، همان‌ها را
حفظ کنید و حذف نکنید. مخصوصاً DATABASE_URL باید MariaDB/MySQL باشد، نه
file:./dev.db و نه SQLite.

شروع سرویس
-----------
در cPanel فقط Start / Restart همان Application را انجام دهید.
به اجرای دستی npm install، bun install، prisma migrate یا next build نیازی نیست.

app.js قبل از Next.js یک preflight ایمن MariaDB را در پس‌زمینه اجرا می‌کند:
- User.mobile را nullable نگه می‌دارد.
- User.username را اضافه/اصلاح می‌کند و uniqueness را برقرار می‌سازد.
- Plan.features را روی TEXT نگه می‌دارد.
- پلن‌های canonical را در صورت نبودن ایجاد می‌کند.
- در صورت خرابی/قطعی دیتابیس، سایت را از بالا آمدن باز نمی‌دارد و خطا را در لاگ ثبت می‌کند.

دیتابیس مرجع پروژه
------------------
ساختار production بر اساس MariaDB 10.6 و dump واقعی این پروژه است.
SQLite برای production این بسته استفاده نمی‌شود.

Brand / UI
----------
Landing، Header، Footer و فرم‌های Login/Register با زبان طراحی مرجع Asovin
بازطراحی شده‌اند و لوگوی canonical Asovin در public/brand استفاده می‌شود.

SEO / GEO
---------
POSTYAR_PUBLIC_BASE_URL باید دامنه HTTPS واقعی سرویس باشد. boot.js در هر
restart، robots.txt و sitemap.xml را با همان origin تولید/به‌روزرسانی می‌کند.

سلامت سرویس
-----------
- /
- /robots.txt
- /sitemap.xml
- /api/health

محدودیت تست
-----------
در محیط ساخت این بسته، اتصال به MariaDB واقعی هاست در دسترس نبود؛ در نتیجه
schema و migration با SQL dump واقعی تطبیق داده و runtime standalone با HTTP
تست شده است. نتیجه live database باید پس از Restart روی همان هاست بررسی شود.
