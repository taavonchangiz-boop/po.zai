<?php
/**
 * پیکربندی سامانه مستقل مدیریت هوشمند کانال‌ها (SaaS)
 *
 * ⚠️  این فایل الگو است. برای استفاده:
 *      ۱. این فایل را با نام config.php کپی کنید:
 *         cp config.example.php config.php
 *      ۲. مقادیر واقعی را جایگزین placeholderها کنید.
 *      ۳. هرگز config.php را در گیت‌هاب آپلود نکنید.
 *
 *      نکته مهم درباره app.url:
 *      اگر آدرس سایت را http://localhost:8000 بگذارید یا خالی بگذارید،
 *      سیستم به‌صورت خودکار آدرس واقعی را از سرور تشخیص می‌دهد.
 *      این بهترین گزینه برای هاست‌های اشتراکی (مثل LiteSpeed) است.
 *
 * @package WHCM_SaaS
 */

return [
    // تنظیمات عمومی
    'app' => [
        'name' => 'پُست‌یار',
        'url' => 'http://localhost:8000',              // خالی یا localhost = تشخیص خودکار آدرس واقعی از سرور
        'locale' => 'fa',
        'timezone' => 'Asia/Tehran',
        'env' => 'production',                              // 'production' یا 'development'
    ],

    // تنظیمات دیتابیس (پشتیبانی از SQLite و MySQL از طریق PDO)
    'database' => [
        'driver' => 'sqlite',                               // 'sqlite' یا 'mysql'
        'sqlite' => [
            'path' => __DIR__ . '/../storage/db/whcm_saas.sqlite',
        ],
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => '',                                // ← نام دیتابیس
            'username' => '',                                // ← نام کاربری
            'password' => '',                                // ← رمز عبور
            'charset' => 'utf8mb4',
        ],
    ],

    // تنظیمات امنیتی
    'security' => [
        'salt' => '',                                       // ← رشته ۶۴ کاراکتری تصادفی (bin2hex(random_bytes(32)))
        'session_lifetime' => 86400,                        // ۲۴ ساعت به ثانیه
        'trusted_proxies' => [],                            // لیست IP پروکسی‌های معتبر (برای RateLimit)
        'admin_ip_whitelist' => [],                         // ← خالی = بدون محدودیت IP. مثال: ['1.2.3.4', '5.6.7.8']
    ],

    // تنظیمات آپلود
    'upload' => [
        'max_size_mb' => 5,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    ],

    // ویژگی‌های پیش‌فرض پلتفرم
    'defaults' => [
        'gold_api_url' => 'https://api.tgju.org/v1/data/sana/home',
        'gold_currency' => 'toman',
    ],

    // تنظیمات SMTP برای ارسال ایمیل
    'mail' => [
        'enabled' => false,                                  // ← true برای فعال‌سازی
        'host' => 'smtp.example.com',                       // ← سرور SMTP
        'port' => 587,
        'username' => '',                                    // ← نام کاربری SMTP
        'password' => '',                                    // ← رمز SMTP
        'encryption' => 'tls',
        'from_address' => 'noreply@your-domain.ir',          // ← ایمیل فرستنده
        'from_name' => 'پُست‌یار',
    ],

    // تنظیمات پیامک (SMS.ir)
    'sms' => [
        'enabled' => false,                                  // ← true برای فعال‌سازی
        'provider' => 'smsir',
        'api_key' => '',                                     // ← کلید API sms.ir
        'line_number' => '',                                 // ← شماره خط ارسال
    ],

    // تنظیمات Web Push (اعلان مرورگر PWA)
    // کلیدها با ابزارهای آنلاین Web Push VAPID Generator تولید می‌شوند
    'vapid' => [
        'enabled' => false,                                  // ← true برای فعال‌سازی پوش ناتیفیکیشن
        'subject' => 'mailto:noreply@your-domain.ir',        // ← ایمیل تماس (الزامی برای VAPID)
        'public_key' => '',                                  // ← کلید عمومی VAPID (base64url)
        'private_key_pem' => '',                              // ← محتوای کامل PEM کلید خصوصی EC
    ],
];
