# PROJECT CONTRACT — پٌست‌یار (Postyar)

## 1. هدف
ساخت یک سامانه حرفه‌ای مدیریت، تولید، زمان‌بندی، انتشار و اتوماسیون محتوا برای کانال‌ها و پیام‌رسان‌ها با تمرکز بر بازار فارسی و استقرار روی cPanel.

## 2. منبع قابلیت
- `Asovin/`: مرجع قابلیت‌های موجود، UI/UX، الگوهای محصول و assetهای بصری تأییدشده.
- `postyar/`: مرجع قابلیت‌های پیشرفته، مدل داده و الگوهای معماری موجود.
- هیچ‌کدام source-of-truth کد نهایی نیستند.

## 3. خروجی نهایی
یک codebase مستقل Node.js/TypeScript، قابل build و deploy، بدون نیاز به اجرای پروژه‌های مرجع.

## 4. الزامات محصول
Authentication, Authorization, Users, Roles/Permissions, Channels, Telegram, Bale, Rubika, Posts, Scheduling, Media, Captions, Buttons, Templates, Auto Reply, Bot Builder, Workflows, Queue, Retry, Idempotency, Deduplication, Audit Log, Notifications, SMS, Email, Plans, Subscriptions, Feature Gating, Wallet, Payments, Discounts, Referral, Renewal Reminder, AI Jobs, AI Content, Gold Price, Gold Bot, Analytics, Admin, User Dashboard, API, WooCommerce, WordPress Plugin, Health Check, Monitoring, General Settings, Advanced Settings.

## 5. کیفیت پذیرش
هیچ قابلیت Done نیست مگر اینکه implementation، validation، error handling، security، tests، documentation، accessibility و performance review را گذرانده باشد.

## 6. Localization
محصول Persian-first و RTL است. تاریخ/زمان نمایشی Jalali و اعداد UI فارسی باشند. Vazirmatn default Persian font است.

## 7. Deployment
Target Node.js `22.23.2` روی cPanel، بدون وابستگی به SSH. Worker/Scheduler باید با cPanel Cron نیز قابل اجرا باشد.
