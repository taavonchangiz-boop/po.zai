<?php
/**
 * پیکربندی سامانه پُست‌یار
 *
 * ⚠️  این فایل حاوی اطلاعات حساس است.
 *      هرگز آن را در گیت‌هاب آپلود نکنید.
 *      برای الگو، فایل config.example.php را ببینید.
 *
 * @package WHCM_SaaS
 */

return [
    // تنظیمات عمومی
    'app' => [
        'name' => 'پُست‌یار',
        'url' => 'https://asovin.ir', // خالی = تشخیص خودکار آدرس از سرور
        'locale' => 'fa',
        'timezone' => 'Asia/Tehran',
        'env' => 'production', // 'production' یا 'development'
    ],

    // تنظیمات دیتابیس (پشتیبانی از SQLite و MySQL از طریق PDO)
    'database' => [
        'driver' => 'sqlite', // 'sqlite' یا 'mysql'
        'sqlite' => [
            'path' => __DIR__ . '/../storage/db/whcm_saas.sqlite',
        ],
        'mysql' => [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'asovinir_argawgczxgvhgaegtfxcd',
            'username' => 'asovinir_argawgczxgvhgaegtfxcd',
            'password' => 'Hoomans@8702',
            'charset' => 'utf8mb4',
        ],
    ],

    // تنظیمات امنیتی
    'security' => [
        'salt' => 'a7f3e9b2c1d4f6a8e0b3c5d7f9a1e3b5c7d9f0a2b4e6d8c0f1a3b5e7d9c1f3',
        'session_lifetime' => 86400,
        'trusted_proxies' => [],
        'admin_ip_whitelist' => [],
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
        'enabled' => true,
        'host' => 'localhost',
        'port' => 25,
        'username' => 'noreply@asovin.ir',
        'password' => 'Hoomans@8702',
        'encryption' => '',
        'from_address' => 'noreply@asovin.ir',
        'from_name' => 'پُست‌یار',
    ],

    // تنظیمات پیامک (SMS.ir)
    'sms' => [
        'enabled' => true,
        'provider' => 'smsir',
        'api_key' => '9Vy8CHLigg7djaex1gwmagOCp5sQ6v5VgOAY2Th49qjVxQCc',
        'line_number' => '30002108029721',
    ],
];
