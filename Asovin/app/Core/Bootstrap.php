<?php
namespace WHCM\Core;

/**
 * کلاس راه‌اندازی سامانه (Bootstrap)
 *
 * @package WHCM.Core
 */
class Bootstrap {
    /** @var array */
    private static $config = [];

    /** @var \PDO|null */
    private static $db = null;

    /**
     * اجرای اولیه سیستم — فقط بارگذاری و اتصال دیتابیس
     */
    public static function run() {
        // ۱. مدیریت خطاها
        self::setupErrorReporting();

        // ۲. ریجستر کردن Autoloader سفارشی
        spl_autoload_register([self::class, 'autoload']);

        // ۳. بارگذاری پیکربندی
        self::$config = require __DIR__ . '/../../config/config.php';

        // ۴. تنظیم منطقه زمانی
        date_default_timezone_set(self::$config['app']['timezone'] ?? 'Asia/Tehran');

        // ۵. شروع سشن امن
        Session::start();

        // ۶. ایجاد دایرکتوری‌های مورد نیاز
        self::ensureDirectories();

        // ۷. راه‌اندازی دیتابیس و اجرای مایگریشن‌ها (فقط اولین بار)
        self::initDatabase();

        // ۸. اعمال Security Headers
        self::sendSecurityHeaders();
    }

    /**
     * تنظیمات گزارش خطا بر اساس محیط
     */
    private static function setupErrorReporting(): void {
        $env = self::$config['app']['env'] ?? 'production';
        if ($env === 'development') {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        } else {
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('log_errors', 1);
        }
    }

    /**
     * اعمال هدرهای امنیتی HTTP
     */
    public static function sendSecurityHeaders(): void {
        // جلوگیری از Clickjacking
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: camera=(), microphone=(), geolocation=()');

            // HSTS — فقط اگر HTTPS باشد
            $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
            if ($is_secure) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }

    /**
     * دریافت تنظیمات سیستم
     */
    public static function getConfig(?string $key = null, $default = null) {
        if ($key === 'app.url') {
            $configured = self::$config['app']['url'] ?? 'http://localhost:8000';
            if (($configured === 'http://localhost:8000' || empty($configured)) && isset($_SERVER['HTTP_HOST'])) {
                $is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
                $scheme = $is_secure ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $script = $_SERVER['SCRIPT_NAME'] ?? '';
                $dir = dirname($script);
                if ($dir === '/' || $dir === '\\') {
                    $dir = '';
                }
                if (substr($dir, -7) === '/public') {
                    $dir = substr($dir, 0, -7);
                }
                return $scheme . '://' . $host . rtrim($dir, '/');
            }
        }

        if ($key === null) {
            return self::$config;
        }

        $parts = explode('.', $key);
        $current = self::$config;

        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    /**
     * دریافت کانکشن دیتابیس PDO به صورت Singleton
     */
    public static function getDB(): \PDO {
        if (self::$db === null) {
            self::initDatabase();
        }
        return self::$db;
    }

    /**
     * مکانیزم بارگذاری خودکار کلاس‌ها (PSR-4)
     */
    private static function autoload(string $class) {
        // ۱. لود کلاس‌های WHCM
        $prefix = 'WHCM\\';
        $base_dir = __DIR__ . '/../';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }

        // ۲. لود PHPMailer
        $pm_prefix = 'PHPMailer\\PHPMailer\\';
        $pm_len = strlen($pm_prefix);
        if (strncmp($pm_prefix, $class, $pm_len) === 0) {
            $pm_base = __DIR__ . '/../../vendor/phpmailer/phpmailer/';
            $pm_relative = substr($class, $pm_len);
            $pm_file = $pm_base . str_replace('\\', '/', $pm_relative) . '.php';
            if (file_exists($pm_file)) {
                require $pm_file;
            }
        }
    }

    /**
     * بررسی و ساخت پوشه‌های مورد نیاز
     */
    private static function ensureDirectories(): void {
        $dirs = [
            __DIR__ . '/../../storage',
            __DIR__ . '/../../storage/db',
            __DIR__ . '/../../storage/uploads',
            __DIR__ . '/../../storage/logs',
            __DIR__ . '/../../public/assets/uploads',
            __DIR__ . '/../../public/assets/plans',
            __DIR__ . '/../../public/assets/receipts',
        ];

        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    /**
     * راه‌اندازی دیتابیس
     */
    private static function initDatabase(): void {
        if (self::$db !== null) {
            return;
        }

        $driver = self::getConfig('database.driver', 'sqlite');

        try {
            if ($driver === 'sqlite') {
                $path = self::getConfig('database.sqlite.path');
                self::$db = new \PDO("sqlite:" . $path);
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                self::$db->exec("PRAGMA foreign_keys = ON;");
            } else {
                $host = self::getConfig('database.mysql.host');
                $port = self::getConfig('database.mysql.port', '3306');
                $dbname = self::getConfig('database.mysql.database');
                $user = self::getConfig('database.mysql.username');
                $pass = self::getConfig('database.mysql.password');
                $charset = self::getConfig('database.mysql.charset', 'utf8mb4');

                self::$db = new \PDO("mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}", $user, $pass);
                self::$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                self::$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            }

            // اجرای مایگریشن‌ها فقط در اولین اجرا
            self::checkAndRunMigrations();

            // اجرای مایگریشن‌های نسخه‌دار (ارتقای تدریجی)
            self::runVersionedMigrations();

        } catch (\PDOException $e) {
            // در production فقط لاگ کن، اطلاعات حساس را نمایش نده
            if ((self::$config['app']['env'] ?? 'production') === 'development') {
                die("خطا در اتصال به دیتابیس: " . $e->getMessage());
            } else {
                die("خطای سیستمی. لطفاً بعداً تلاش کنید.");
            }
        }
    }

    /**
     * ایجاد جدول‌ها در صورت خالی بودن دیتابیس (فقط اولین بار)
     */
    private static function checkAndRunMigrations(): void {
        $db = self::$db;
        $hasTable = false;

        if (self::getConfig('database.driver') === 'sqlite') {
            $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            $hasTable = (bool) $stmt->fetch();
        } else {
            $dbname = self::getConfig('database.mysql.database');
            $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users'");
            $stmt->execute([$dbname]);
            $hasTable = (bool) $stmt->fetch();
        }

        if (!$hasTable) {
            $driver = self::getConfig('database.driver', 'sqlite');
            $filename = ($driver === 'mysql') ? 'install_mysql.sql' : 'install.sql';
            $migration_file = __DIR__ . '/../../migrations/' . $filename;

            if (file_exists($migration_file)) {
                $sql = file_get_contents($migration_file);
                $queries = self::splitSqlQueries($sql);
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->exec($query);
                    }
                }
            }

            // پس از نصب اولیه، نسخه فعلی مایگریشن ثبت شود
            self::setMigrationVersion('schema_initial');
        }
    }

    /**
     * مایگریشن‌های نسخه‌دار — هر نسخه فقط یک‌بار اجرا می‌شود
     */
    private static function runVersionedMigrations(): void {
        $migrations = [
            'v2_add_plan_columns' => function($db) {
                $cols = ['payment_url TEXT NULL', 'image_url TEXT NULL', 'description TEXT NULL',
                         'early_renewal_discount INTEGER DEFAULT 0', 'general_discount INTEGER DEFAULT 0',
                         'discount_badge_text VARCHAR(150) NULL', 'is_featured INTEGER DEFAULT 0'];
                foreach ($cols as $col) {
                    try { $db->exec("ALTER TABLE plans ADD COLUMN $col"); } catch (\Exception $e) {}
                }
            },
            'v2_add_user_columns' => function($db) {
                try { $db->exec("ALTER TABLE users ADD COLUMN business_name VARCHAR(150) NULL"); } catch (\Exception $e) {}
                try { $db->exec("ALTER TABLE users ADD COLUMN business_type VARCHAR(150) NULL"); } catch (\Exception $e) {}
            },
            'v2_add_ticket_columns' => function($db) {
                try { $db->exec("ALTER TABLE tickets ADD COLUMN attachment TEXT NULL"); } catch (\Exception $e) {}
                try { $db->exec("ALTER TABLE tickets ADD COLUMN assigned_to INTEGER NULL"); } catch (\Exception $e) {}
                try { $db->exec("ALTER TABLE tickets ADD COLUMN priority VARCHAR(20) DEFAULT 'normal'"); } catch (\Exception $e) {}
                try { $db->exec("ALTER TABLE tickets ADD COLUMN created_by_admin INTEGER DEFAULT 0"); } catch (\Exception $e) {}
            },
            'v2_create_tickets_table' => function($db) {
                $driver = self::getConfig('database.driver', 'sqlite');
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS tickets (
                            id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
                            subject VARCHAR(255) NOT NULL, category VARCHAR(100) NOT NULL,
                            message TEXT NOT NULL, status VARCHAR(50) DEFAULT 'open',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS tickets (
                            id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL,
                            subject VARCHAR(255) NOT NULL, category VARCHAR(100) NOT NULL,
                            message TEXT NOT NULL, status VARCHAR(50) DEFAULT 'open',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                        );");
                    }
                } catch (\Exception $e) {}
            },
            'v3_referral_wallet' => function($db) {
                $driver = self::getConfig('database.driver', 'sqlite');

                // اضافه کردن ستون‌های جدید به جدول users
                $user_cols = [
                    'phone VARCHAR(15) NULL',
                    'referral_code VARCHAR(20) NULL',
                    'referred_by INTEGER NULL',
                    'referral_points DECIMAL(15,2) DEFAULT 0',
                    'wallet_balance DECIMAL(15,2) DEFAULT 0',
                ];
                foreach ($user_cols as $col) {
                    try { $db->exec("ALTER TABLE users ADD COLUMN $col"); } catch (\Exception $e) {}
                }
                // ایندکس یکتا برای referral_code (جداسازی شده برای سازگاری با SQLite قدیمی‌تر)
                try { $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_referral_code ON users(referral_code) WHERE referral_code IS NOT NULL"); } catch (\Exception $e) {}

                // ایجاد جدول referrals
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            referrer_id INT NOT NULL,
                            referred_id INT NOT NULL UNIQUE,
                            referral_code VARCHAR(20) NOT NULL,
                            reward_type VARCHAR(20) DEFAULT 'points',
                            reward_value DECIMAL(10,2) DEFAULT 0,
                            status VARCHAR(20) DEFAULT 'pending',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            rewarded_at DATETIME NULL,
                            FOREIGN KEY (referrer_id) REFERENCES users(id),
                            FOREIGN KEY (referred_id) REFERENCES users(id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS referrals (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            referrer_id INTEGER NOT NULL,
                            referred_id INTEGER NOT NULL UNIQUE,
                            referral_code VARCHAR(20) NOT NULL,
                            reward_type VARCHAR(20) DEFAULT 'points',
                            reward_value DECIMAL(10,2) DEFAULT 0,
                            status VARCHAR(20) DEFAULT 'pending',
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            rewarded_at DATETIME NULL,
                            FOREIGN KEY (referrer_id) REFERENCES users(id),
                            FOREIGN KEY (referred_id) REFERENCES users(id)
                        );");
                    }
                } catch (\Exception $e) {}

                // ایجاد جدول wallet_transactions
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            user_id INT NOT NULL,
                            type VARCHAR(30) NOT NULL,
                            amount DECIMAL(15,2) NOT NULL,
                            balance_after DECIMAL(15,2) NOT NULL,
                            description TEXT,
                            reference_type VARCHAR(50),
                            reference_id INT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS wallet_transactions (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER NOT NULL,
                            type VARCHAR(30) NOT NULL,
                            amount DECIMAL(15,2) NOT NULL,
                            balance_after DECIMAL(15,2) NOT NULL,
                            description TEXT,
                            reference_type VARCHAR(50),
                            reference_id INTEGER,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id)
                        );");
                    }
                } catch (\Exception $e) {}

                // ایجاد جدول referral_settings
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS referral_settings (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            setting_key VARCHAR(50) NOT NULL UNIQUE,
                            setting_value TEXT NOT NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS referral_settings (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            setting_key VARCHAR(50) NOT NULL UNIQUE,
                            setting_value TEXT NOT NULL
                        );");
                    }
                } catch (\Exception $e) {}

                // درج تنظیمات پیش‌فرض سیستم زیرمجموعه‌گیری
                $defaults = [
                    ['enabled', '1'],
                    ['register_reward_type', 'points'],
                    ['register_reward_value', '100'],
                    ['first_purchase_reward_type', 'percent'],
                    ['first_purchase_reward_value', '10'],
                    ['max_referrals_per_user', '100'],
                    ['monthly_reward_cap', '500000'],
                ];
                foreach ($defaults as [$key, $value]) {
                    try {
                        $stmt = $db->prepare("INSERT OR IGNORE INTO referral_settings (setting_key, setting_value) VALUES (?, ?)");
                        $stmt->execute([$key, $value]);
                    } catch (\Exception $e) {
                        try {
                            $stmt = $db->prepare("INSERT IGNORE INTO referral_settings (setting_key, setting_value) VALUES (?, ?)");
                            $stmt->execute([$key, $value]);
                        } catch (\Exception $e2) {}
                    }
                }
            },
            'v4_sms_system' => function($db) {
                $driver = self::getConfig('database.driver', 'sqlite');

                // ایجاد جدول sms_templates
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS sms_templates (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            event_key VARCHAR(50) NOT NULL UNIQUE,
                            template_name VARCHAR(100) NOT NULL,
                            template_id INT NOT NULL,
                            parameters TEXT DEFAULT '[]',
                            is_active INT DEFAULT 1,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS sms_templates (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            event_key VARCHAR(50) NOT NULL UNIQUE,
                            template_name VARCHAR(100) NOT NULL,
                            template_id INTEGER NOT NULL,
                            parameters TEXT DEFAULT '[]',
                            is_active INTEGER DEFAULT 1,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );");
                    }
                } catch (\Exception $e) {}

                // ایجاد جدول sms_log
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS sms_log (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            template_id INT,
                            phone VARCHAR(15) NOT NULL,
                            user_id INT NULL,
                            status VARCHAR(20) DEFAULT 'pending',
                            response_code VARCHAR(20),
                            error_message TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS sms_log (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            template_id INTEGER,
                            phone VARCHAR(15) NOT NULL,
                            user_id INTEGER NULL,
                            status VARCHAR(20) DEFAULT 'pending',
                            response_code VARCHAR(20),
                            error_message TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );");
                    }
                } catch (\Exception $e) {}

                // درج قالب‌های پیش‌فرض
                $defaults = [
                    ['registration',        'ثبت‌نام کاربر جدید',          0, '[]',  1],
                    ['payment_confirm',     'تایید تراکنش پرداخت',         0, '[]',  1],
                    ['subscription_expiry', 'یادآوری انقضای اشتراک',       0, '[]',  1],
                    ['password_reset',      'بازنشانی رمز عبور',           0, '[]',  1],
                    ['bulk_notification',   'اطلاع‌رسانی عمومی',            0, '[]',  1],
                ];
                foreach ($defaults as $row) {
                    try {
                        $stmt = $db->prepare("INSERT OR IGNORE INTO sms_templates (event_key, template_name, template_id, parameters, is_active) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute($row);
                    } catch (\Exception $e) {
                        try {
                            $stmt = $db->prepare("INSERT IGNORE INTO sms_templates (event_key, template_name, template_id, parameters, is_active) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute($row);
                        } catch (\Exception $e2) {}
                    }
                }
            },
            'v5_email_templates' => function($db) {
                $driver = self::getConfig('database.driver', 'sqlite');

                // ایجاد جدول email_templates
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            event_key VARCHAR(50) NOT NULL UNIQUE,
                            template_name VARCHAR(100) NOT NULL,
                            subject VARCHAR(255) NOT NULL,
                            body_html TEXT NOT NULL,
                            variables TEXT DEFAULT '[]',
                            is_active INT DEFAULT 1,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS email_templates (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            event_key VARCHAR(50) NOT NULL UNIQUE,
                            template_name VARCHAR(100) NOT NULL,
                            subject VARCHAR(255) NOT NULL,
                            body_html TEXT NOT NULL,
                            variables TEXT DEFAULT '[]',
                            is_active INTEGER DEFAULT 1,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );");
                    }
                } catch (\Exception $e) {}

                // ایجاد جدول email_log
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS email_log (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            template_id INT NULL,
                            to_address VARCHAR(255) NOT NULL,
                            user_id INT NULL,
                            subject VARCHAR(255),
                            status VARCHAR(20) DEFAULT 'pending',
                            error_message TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS email_log (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            template_id INTEGER NULL,
                            to_address VARCHAR(255) NOT NULL,
                            user_id INTEGER NULL,
                            subject VARCHAR(255),
                            status VARCHAR(20) DEFAULT 'pending',
                            error_message TEXT,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );");
                    }
                } catch (\Exception $e) {}

                // تابع کمکی برای ساخت هدر ایمیل
                $emailHeader = function($title, $preheader = '') {
                    return "<!DOCTYPE html><html dir='rtl' lang='fa'><head><meta charset='UTF-8'><meta name='viewport' content='width=device-width, initial-scale=1.0'>" .
                        "<title>$title</title>" .
                        "<!--[if mso]><noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript><![endif]-->" .
                        "</head><body style='margin:0; padding:0; background:#f1f5f9;'>";
                };

                $emailFooter = function($app_name) {
                    return "<tr><td style='background:#f1f5f9; padding:20px 30px; text-align:center;'>" .
                        "<p style='margin:0; color:#94a3b8; font-size:12px; font-family:Tahoma,Arial,sans-serif;'>" .
                        "این ایمیل توسط <strong style='color:#4f46e5;'>" . htmlspecialchars($app_name) . "</strong> ارسال شده است.<br>" .
                        "اگر شما این درخواست را نداده‌اید، لطفاً این ایمیل را نادیده بگیرید.</p>" .
                        "</td></tr></table></body></html>";
                };

                $emailBodyOpen = function($preheader = '') {
                    return ($preheader ? "<div style='display:none; font-size:1px; color:#f1f5f9; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;'>$preheader</div>" : '') .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#f1f5f9; font-family:Tahoma,Arial,sans-serif; padding:20px 0;'>" .
                        "<tr><td align='center' style='padding:20px 10px;'>" .
                        "<table role='presentation' width='600' cellpadding='0' cellspacing='0' style='max-width:600px; width:100%; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,0.08);'>";
                };

                $emailBodyClose = function() {
                    return "</table></td></tr>";
                };

                $ctaButton = function($url, $text) {
                    return "<tr><td style='padding:10px 0 30px; text-align:center;'>" .
                        "<a href='" . htmlspecialchars($url) . "' target='_blank' style='display:inline-block; background:linear-gradient(135deg,#10b981 0%,#059669 100%); color:#ffffff!important; padding:14px 40px; border-radius:10px; text-decoration:none; font-weight:bold; font-size:15px; font-family:Tahoma,Arial,sans-serif; box-shadow:0 4px 14px rgba(16,185,129,0.35);'>" . htmlspecialchars($text) . "</a></td></tr>";
                };

                // درج ۷ قالب ایمیل پیش‌فرض با طراحی حرفه‌ای
                $defaults = [
                    [
                        'welcome',
                        'خوش‌آمدگویی ثبت‌نام',
                        'خوش آمدید به {{app_name}} {{name}} عزیز! 🎉',
                        $emailHeader('خوش‌آمدید') .
                        $emailBodyOpen('به پلتفرم مدیریت هوشمند کانال‌ها خوش آمدید') .
                        "<tr><td style='background:linear-gradient(135deg,#312e81 0%,#4f46e5 50%,#6366f1 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>🎉 خوش آمدید!</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>" . "{{app_name}}" . " — سامانه مستقل مدیریت هوشمند کانال‌ها</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>ثبت‌نام شما با موفقیت انجام شد! از اینکه <strong style='color:#4f46e5;'>{{app_name}}</strong> را انتخاب کردید سپاسگزاریم.</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:15px 30px;'>" .
                        "<p style='margin:0 0 12px; color:#475569; font-size:14px; font-family:Tahoma,Arial,sans-serif;'>با <strong style='color:#1e293b;'>{{app_name}}</strong> می‌توانید از امکانات زیر بهره‌مند شوید:</p>" .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='margin-top:8px;'>" .
                        "<tr><td style='padding:8px 0;'><span style='display:inline-block; width:28px; height:28px; background:linear-gradient(135deg,#4f46e5,#6366f1); color:white; text-align:center; line-height:28px; border-radius:8px; font-size:13px; margin-left:10px;'>📱</span><span style='color:#334155; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>مدیریت چندگانه کانال‌های تلگرام و اینستاگرام</span></td></tr>" .
                        "<tr><td style='padding:8px 0;'><span style='display:inline-block; width:28px; height:28px; background:linear-gradient(135deg,#10b981,#059669); color:white; text-align:center; line-height:28px; border-radius:8px; font-size:13px; margin-left:10px;'>🤖</span><span style='color:#334155; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>ارسال هوشمند پست با هوش مصنوعی</span></td></tr>" .
                        "<tr><td style='padding:8px 0;'><span style='display:inline-block; width:28px; height:28px; background:linear-gradient(135deg,#f59e0b,#d97706); color:white; text-align:center; line-height:28px; border-radius:8px; font-size:13px; margin-left:10px;'>📊</span><span style='color:#334155; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>آمار دقیق کلیک و بازدید پست‌ها</span></td></tr>" .
                        "<tr><td style='padding:8px 0;'><span style='display:inline-block; width:28px; height:28px; background:linear-gradient(135deg,#ec4899,#db2777); color:white; text-align:center; line-height:28px; border-radius:8px; font-size:13px; margin-left:10px;'>🎫</span><span style='color:#334155; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>پشتیبانی آنلاین و سیستم تیکت</span></td></tr>" .
                        "</table></td></tr>" .
                        "<tr><td style='padding:20px 30px 10px; text-align:center;'><p style='margin:0; color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>برای شروع کار، روی دکمه زیر کلیک کنید:</p></td></tr>" .
                        $ctaButton('{{app_url}}', 'ورود به پنل کاربری') .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'app_url', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'payment_confirm',
                        'تاییدیه پرداخت',
                        'تاییدیه پرداخت اشتراک {{plan_name}} — {{app_name}}',
                        $emailHeader('تاییدیه پرداخت') .
                        $emailBodyOpen('پرداخت شما با موفقیت تایید شد') .
                        "<tr><td style='background:linear-gradient(135deg,#064e3b 0%,#047857 50%,#10b981 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>✅ تاییدیه پرداخت</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>تراکنش مالی شما با موفقیت انجام شد</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>پرداخت شما با موفقیت تایید و ثبت شد. جزئیات تراکنش:</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:15px 30px;'>" .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0;'>" .
                        "<tr><td style='padding:14px 20px; border-bottom:1px solid #e2e8f0;'><span style='color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>پلن اشتراک:</span><br><strong style='color:#1e293b; font-size:15px; font-family:Tahoma,Arial,sans-serif;'>{{plan_name}}</strong></td></tr>" .
                        "<tr><td style='padding:14px 20px; border-bottom:1px solid #e2e8f0;'><span style='color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>مبلغ پرداختی:</span><br><strong style='color:#059669; font-size:18px; font-family:Tahoma,Arial,sans-serif;'>{{amount}} تومان</strong></td></tr>" .
                        "<tr><td style='padding:14px 20px;'><span style='color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>تاریخ تراکنش:</span><br><strong style='color:#1e293b; font-size:15px; font-family:Tahoma,Arial,sans-serif;'>{{date}}</strong></td></tr>" .
                        "</table></td></tr>" .
                        "<tr><td style='padding:20px 30px; text-align:center;'><p style='margin:0; color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>از اعتماد شما سپاسگزاریم. 🙏</p></td></tr>" .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'plan_name', 'amount', 'date', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'subscription_expiry',
                        'یادآوری انقضای اشتراک',
                        'یادآوری: اشتراک {{plan_name}} شما تا {{days_left}} روز دیگر منقضی می‌شود',
                        $emailHeader('یادآوری انقضای اشتراک') .
                        $emailBodyOpen('اشتراک شما در حال انقضا است') .
                        "<tr><td style='background:linear-gradient(135deg,#78350f 0%,#b45309 50%,#f59e0b 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>⏰ یادآوری انقضای اشتراک</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>اشتراک شما به زودی به پایان می‌رسد</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>اشتراک <strong style='color:#d97706;'>{{plan_name}}</strong> شما تنها <strong style='color:#dc2626; font-size:18px;'>{{days_left}} روز</strong> دیگر اعتبار دارد.</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:10px 30px; text-align:center;'>" .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0'><tr><td style='padding:15px; background:#fffbeb; border:1px solid #fde68a; border-radius:10px; text-align:center;'>" .
                        "<p style='margin:0; color:#92400e; font-size:14px; font-family:Tahoma,Arial,sans-serif;'>⚠️ برای جلوگیری از قطع سرویس، لطفاً اشتراک خود را تمدید کنید.</p>" .
                        "</td></tr></table></td></tr>" .
                        $ctaButton('{{app_url}}', 'تمدید اشتراک') .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'days_left', 'plan_name', 'app_url', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'subscription_expired',
                        'انقضای اشتراک',
                        'اشتراک شما در {{app_name}} منقضی شده است',
                        $emailHeader('انقضای اشتراک') .
                        $emailBodyOpen('اشتراک شما به پایان رسیده است') .
                        "<tr><td style='background:linear-gradient(135deg,#7f1d1d 0%,#b91c1c 50%,#ef4444 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>🔴 انقضای اشتراک</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>اشتراک شما به پایان رسیده است</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>متأسفانه اشتراک شما در <strong style='color:#4f46e5;'>{{app_name}}</strong> به پایان رسیده است. برای ادامه استفاده از امکانات پلتفرم، لطفاً اشتراک خود را تمدید کنید.</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:10px 30px; text-align:center;'>" .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0'><tr><td style='padding:15px; background:#fef2f2; border:1px solid #fecaca; border-radius:10px; text-align:center;'>" .
                        "<p style='margin:0; color:#991b1b; font-size:14px; font-family:Tahoma,Arial,sans-serif;'>🔒 در حال حاضر دسترسی شما به امکانات پلتفرم محدود شده است.</p>" .
                        "</td></tr></table></td></tr>" .
                        $ctaButton('{{app_url}}', 'تمدید فوری اشتراک') .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'app_url', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'password_reset',
                        'بازنشانی رمز عبور',
                        'بازنشانی رمز عبور — {{app_name}}',
                        $emailHeader('بازنشانی رمز عبور') .
                        $emailBodyOpen('درخواست بازنشانی رمز عبور') .
                        "<tr><td style='background:linear-gradient(135deg,#312e81 0%,#4f46e5 50%,#6366f1 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>🔑 بازنشانی رمز عبور</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>درخواست تغییر کلمه عبور شما دریافت شد</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>برای تنظیم رمز عبور جدید، روی دکمه زیر کلیک کنید:</p>" .
                        "</td></tr>" .
                        $ctaButton('{{reset_link}}', 'بازنشانی کلمه عبور') .
                        "<tr><td style='padding:10px 30px 20px; text-align:center;'>" .
                        "<table role='presentation' width='100%' cellpadding='0' cellspacing='0'><tr><td style='padding:15px; background:#fefce8; border:1px solid #fde68a; border-radius:10px; text-align:center;'>" .
                        "<p style='margin:0; color:#854d0e; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>⚠️ این لینک فقط ۱ ساعت اعتبار دارد. اگر شما درخواست نکرده‌اید، این پیام را نادیده بگیرید.</p>" .
                        "</td></tr></table></td></tr>" .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'reset_link', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'ticket_reply',
                        'پاسخ جدید به تیکت',
                        'پاسخ جدید به تیکت شما: {{ticket_subject}}',
                        $emailHeader('پاسخ به تیکت') .
                        $emailBodyOpen('پاسخ جدید به تیکت پشتیبانی شما') .
                        "<tr><td style='background:linear-gradient(135deg,#312e81 0%,#4f46e5 50%,#6366f1 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>🎫 پاسخ جدید به تیکت</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>تیکت پشتیبانی شما پاسخ داده شده است</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>تیکت شما با موضوع <strong style='color:#4f46e5;'>{{ticket_subject}}</strong> پاسخ داده شده است.</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:10px 30px; text-align:center;'><p style='margin:0; color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>برای مشاهده پاسخ و ادامه گفتگو، روی دکمه زیر کلیک کنید:</p></td></tr>" .
                        $ctaButton('{{app_url}}', 'مشاهده تیکت') .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'ticket_subject', 'app_url', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                    [
                        'custom_notification',
                        'اعلان عمومی',
                        'اعلان جدید از {{app_name}}',
                        $emailHeader('اعلان جدید') .
                        $emailBodyOpen('اعلان جدید از سوی مدیریت پلتفرم') .
                        "<tr><td style='background:linear-gradient(135deg,#312e81 0%,#4f46e5 50%,#6366f1 100%); padding:40px 30px; text-align:center;'>" .
                        "<h1 style='margin:0; color:#ffffff; font-size:26px; font-family:Tahoma,Arial,sans-serif;'>📢 اعلان جدید</h1>" .
                        "<p style='margin:8px 0 0; color:rgba(255,255,255,0.85); font-size:14px; font-family:Tahoma,Arial,sans-serif;'>اعلان جدیدی از {{app_name}} دریافت کرده‌اید</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:35px 30px 10px;'>" .
                        "<h2 style='margin:0 0 8px; color:#1e293b; font-size:20px; font-family:Tahoma,Arial,sans-serif;'>سلام {{name}} عزیز،</h2>" .
                        "<p style='margin:0; color:#475569; font-size:14px; line-height:2; font-family:Tahoma,Arial,sans-serif;'>{{message}}</p>" .
                        "</td></tr>" .
                        "<tr><td style='padding:10px 30px; text-align:center;'><p style='margin:0; color:#64748b; font-size:13px; font-family:Tahoma,Arial,sans-serif;'>برای اطلاعات بیشتر، وارد پنل کاربری خود شوید:</p></td></tr>" .
                        $ctaButton('{{app_url}}', 'ورود به پنل کاربری') .
                        $emailBodyClose() .
                        $emailFooter('{{app_name}}'),
                        json_encode(['name', 'message', 'app_url', 'app_name'], JSON_UNESCAPED_UNICODE),
                        1,
                    ],
                ];

                foreach ($defaults as $row) {
                    try {
                        $stmt = $db->prepare("INSERT OR IGNORE INTO email_templates (event_key, template_name, subject, body_html, variables, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute($row);
                    } catch (\Exception $e) {
                        try {
                            $stmt = $db->prepare("INSERT IGNORE INTO email_templates (event_key, template_name, subject, body_html, variables, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute($row);
                        } catch (\Exception $e2) {}
                    }
                }
            },

            'v6_link_tracking' => function($db) {
                $driver = self::getConfig('database.driver', 'sqlite');

                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS link_tracking (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(20) NOT NULL UNIQUE, original_url TEXT NOT NULL, post_id INT NOT NULL, channel_id INT NOT NULL, tenant_id INT NOT NULL, total_clicks INT DEFAULT 0, unique_clicks INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (post_id) REFERENCES posts(id), FOREIGN KEY (channel_id) REFERENCES channels(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        $db->exec("CREATE TABLE IF NOT EXISTS link_clicks (id INT AUTO_INCREMENT PRIMARY KEY, link_id INT NOT NULL, ip_address VARCHAR(45), user_agent TEXT, referer TEXT, is_unique INT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (link_id) REFERENCES link_tracking(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                        $db->exec("CREATE TABLE IF NOT EXISTS verification_codes (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, type VARCHAR(20) NOT NULL, code VARCHAR(10) NOT NULL, expires_at DATETIME NOT NULL, used INT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS link_tracking (id INTEGER PRIMARY KEY AUTOINCREMENT, code VARCHAR(20) NOT NULL UNIQUE, original_url TEXT NOT NULL, post_id INTEGER NOT NULL, channel_id INTEGER NOT NULL, tenant_id INTEGER NOT NULL, total_clicks INTEGER DEFAULT 0, unique_clicks INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (post_id) REFERENCES posts(id), FOREIGN KEY (channel_id) REFERENCES channels(id));");
                        $db->exec("CREATE TABLE IF NOT EXISTS link_clicks (id INTEGER PRIMARY KEY AUTOINCREMENT, link_id INTEGER NOT NULL, ip_address VARCHAR(45), user_agent TEXT, referer TEXT, is_unique INTEGER DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (link_id) REFERENCES link_tracking(id));");
                        $db->exec("CREATE TABLE IF NOT EXISTS verification_codes (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, type VARCHAR(20) NOT NULL, code VARCHAR(10) NOT NULL, expires_at DATETIME NOT NULL, used INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id));");
                    }
                } catch (\Exception $e) {}
            },

            'v7_birthday_column' => function($db) {
                try { $db->exec("ALTER TABLE users ADD COLUMN birthday VARCHAR(10) NULL"); } catch (\Exception $e) {}
            },

            'v8_scheduled_posts_target_channels' => function($db) {
                // افزودن ستون target_channels برای ذخیره لیست کانال‌های هدف پست‌های زمان‌بندی‌شده
                try { $db->exec("ALTER TABLE posts ADD COLUMN target_channels TEXT"); } catch (\Exception $e) {}
                // افزودن ستون expiry_reminder_sent برای جلوگیری از ارسال تکراری یادآوری انقضا
                try { $db->exec("ALTER TABLE subscriptions ADD COLUMN expiry_reminder_sent INTEGER DEFAULT 0"); } catch (\Exception $e) {}
            },
            'v9_create_plans_table' => function($db) {
                // ایجاد جدول plans در صورتی که از ابتدا وجود نداشته باشد
                $driver = self::getConfig('database.driver', 'sqlite');
                try {
                    if ($driver === 'mysql') {
                        $db->exec("CREATE TABLE IF NOT EXISTS plans (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            title VARCHAR(100) NOT NULL,
                            price DECIMAL(12,2) DEFAULT 0.00,
                            duration_days INT DEFAULT 30,
                            max_channels INT DEFAULT 1,
                            max_posts INT DEFAULT 10,
                            features TEXT,
                            payment_url TEXT NULL,
                            image_url TEXT NULL,
                            description TEXT NULL,
                            early_renewal_discount INT DEFAULT 0,
                            general_discount INT DEFAULT 0,
                            discount_badge_text VARCHAR(150) NULL,
                            is_featured INT DEFAULT 0,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
                    } else {
                        $db->exec("CREATE TABLE IF NOT EXISTS plans (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            title VARCHAR(100) NOT NULL,
                            price DECIMAL(12,2) DEFAULT 0.00,
                            duration_days INTEGER DEFAULT 30,
                            max_channels INTEGER DEFAULT 1,
                            max_posts INTEGER DEFAULT 10,
                            features TEXT,
                            payment_url TEXT NULL,
                            image_url TEXT NULL,
                            description TEXT NULL,
                            early_renewal_discount INTEGER DEFAULT 0,
                            general_discount INTEGER DEFAULT 0,
                            discount_badge_text VARCHAR(150) NULL,
                            is_featured INTEGER DEFAULT 0,
                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                        );");
                    }
                } catch (\Exception $e) {}
            },

            'v10_ticket_categories_and_agents' => function($db) {
                // ایجاد جدول دسته‌بندی تیکت‌ها
                try {
                    $db->exec("CREATE TABLE IF NOT EXISTS ticket_categories (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        slug VARCHAR(100) NOT NULL UNIQUE,
                        title VARCHAR(150) NOT NULL,
                        icon VARCHAR(50) DEFAULT '🌐',
                        assigned_agent_id INTEGER NULL,
                        sort_order INTEGER DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )");
                } catch (\Exception $e) {}

                // ستون‌های جدید tickets: priority و created_by_admin
                try { $db->exec("ALTER TABLE tickets ADD COLUMN priority VARCHAR(20) DEFAULT 'normal'"); } catch (\Exception $e) {}
                try { $db->exec("ALTER TABLE tickets ADD COLUMN created_by_admin INTEGER DEFAULT 0"); } catch (\Exception $e) {}

                // دسته‌بندی‌های پیش‌فرض اگر خالی بود
                try {
                    $count = $db->query("SELECT COUNT(*) FROM ticket_categories")->fetchColumn();
                    if ($count == 0) {
                        $db->exec("INSERT INTO ticket_categories (slug, title, icon, sort_order) VALUES ('technical', 'فنی و ربات‌ها 🤖', '🤖', 1)");
                        $db->exec("INSERT INTO ticket_categories (slug, title, icon, sort_order) VALUES ('billing', 'مالی و فیش واریزی 💳', '💳', 2)");
                        $db->exec("INSERT INTO ticket_categories (slug, title, icon, sort_order) VALUES ('general', 'سوال عمومی 🌐', '🌐', 3)");
                    }
                } catch (\Exception $e) {}
            },

            'v11_responder_logs_table' => function($db) {
                try {
                    $db->exec("CREATE TABLE IF NOT EXISTS responder_logs (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        tenant_id INTEGER NOT NULL,
                        channel_id INTEGER NULL,
                        sender_id VARCHAR(100) DEFAULT '',
                        sender_name VARCHAR(200) DEFAULT '',
                        message_text TEXT DEFAULT '',
                        matched_keyword VARCHAR(255) DEFAULT '',
                        reply_sent INTEGER DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                    )");
                } catch (\Exception $e) {}
            },
            'v12_notifications_table' => function($db) {
                try {
                    $db->exec("CREATE TABLE IF NOT EXISTS notifications (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        user_id INTEGER NOT NULL,
                        type VARCHAR(50) NOT NULL DEFAULT 'general',
                        title TEXT NOT NULL,
                        message TEXT DEFAULT '',
                        target_section VARCHAR(100) DEFAULT '',
                        is_read INTEGER DEFAULT 0,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )");
                    $db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read)");
                    $db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_user_created ON notifications(user_id, created_at DESC)");
                } catch (\Exception $e) {}
            },
        ];

        foreach ($migrations as $version => $callback) {
            if (!self::hasMigrationRun($version)) {
                $callback(self::$db);
                self::setMigrationVersion($version);
            }
        }
    }

    /**
     * بررسی اینکه آیا مایگریشن خاصی قبلاً اجرا شده یا خیر
     */
    private static function hasMigrationRun(string $version): bool {
        try {
            $db = self::$db;
            // بررسی وجود جدول schema_migrations
            if (self::getConfig('database.driver') === 'sqlite') {
                $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='schema_migrations'");
            } else {
                $stmt = $db->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'schema_migrations'");
            }
            if (!$stmt->fetch()) {
                return false;
            }

            $stmt = $db->prepare("SELECT 1 FROM schema_migrations WHERE version = ? LIMIT 1");
            $stmt->execute([$version]);
            return (bool)$stmt->fetch();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * ثبت نسخه مایگریشن به عنوان اجرا شده
     */
    private static function setMigrationVersion(string $version): void {
        $db = self::$db;
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
                version VARCHAR(100) PRIMARY KEY, executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $stmt = $db->prepare("INSERT OR IGNORE INTO schema_migrations (version) VALUES (?)");
            $stmt->execute([$version]);
        } catch (\Exception $e) {
            // در MySQL از ON DUPLICATE KEY استفاده می‌شود
            try {
                $stmt = $db->prepare("INSERT IGNORE INTO schema_migrations (version) VALUES (?)");
                $stmt->execute([$version]);
            } catch (\Exception $e2) {}
        }
    }

    /**
     * دریافت آدرس هوشمند و کاملاً پویا برای دارایی‌های عمومی (Assets)
     */
    public static function getAssetsUrl() {
        $app_url = rtrim(self::getConfig('app.url'), '/');
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $filename = $_SERVER['SCRIPT_FILENAME'] ?? '';

        // حالت ۱: فایل‌ها در ساب‌دایرکتوری public/ هستند و مستقیم اجرا می‌شوند
        if (strpos($script, '/public/') !== false) {
            return $app_url . '/public/assets';
        }

        // حالت ۲: index.php از روت public/ را include کرده
        if (strpos($filename, 'public/index.php') !== false && strpos($script, '/public/') === false) {
            return $app_url . '/public/assets';
        }

        // حالت ۳: public/ خودش document root است (مثل cPanel — فایل‌ها در public_html/ قرار دارند)
        // SCRIPT_NAME = /index.php و فایل‌ها کنار index.php هستند
        return $app_url . '/assets';
    }

    /**
     * تولید آدرس پایدار با ساختار پارامتری
     */
    public static function getRouteUrl(string $path) {
        $app_url = rtrim(self::getConfig('app.url'), '/');
        $parts = explode('?', ltrim($path, '/'), 2);
        $route = '/' . $parts[0];
        $query = isset($parts[1]) ? '&' . $parts[1] : '';
        return $app_url . '/index.php?route=' . urlencode($route) . $query;
    }

    /**
     * تولید آدرس تصویر پلن اشتراک
     */
    public static function getPlanImageUrl($url) {
        if (empty($url)) {
            return '';
        }
        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
            $parts = explode('/assets/', $url);
            if (count($parts) > 1) {
                return self::getAssetsUrl() . '/' . $parts[1];
            }
            return $url;
        }

        $parts = explode('/assets/', $url);
        if (count($parts) > 1) {
            return self::getAssetsUrl() . '/' . $parts[1];
        }

        return self::getAssetsUrl() . '/' . ltrim($url, '/');
    }

    /**
     * پارسر هوشمند SQL: تقسیم کوئری‌ها با در نظر گرفتن commentها و stringها
     */
    private static function splitSqlQueries(string $sql): array {
        $queries = [];
        $current = '';
        $in_string = false;
        $string_char = '';
        $in_line_comment = false;
        $in_block_comment = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $char = $sql[$i];
            $next = ($i + 1 < $len) ? $sql[$i + 1] : '';
            $prev = ($i > 0) ? $sql[$i - 1] : '';

            if (!$in_line_comment && !$in_block_comment) {
                if (!$in_string && ($char === "'" || $char === '"') ) {
                    $in_string = true;
                    $string_char = $char;
                } elseif ($in_string && $char === $string_char && $prev !== '\\') {
                    $in_string = false;
                    $string_char = '';
                }
            }

            if (!$in_string && !$in_block_comment && $char === '-' && $next === '-') {
                $in_line_comment = true;
            }
            if ($in_line_comment && $char === "\n") {
                $in_line_comment = false;
            }

            if (!$in_string && !$in_line_comment && $char === '/' && $next === '*') {
                $in_block_comment = true;
            }
            if ($in_block_comment && $prev === '*' && $char === '/') {
                $in_block_comment = false;
            }

            if ($char === ';' && !$in_string && !$in_line_comment && !$in_block_comment) {
                $queries[] = $current;
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $queries[] = $current;
        }

        return $queries;
    }

    /**
     * پاکسازی دیسک — فقط از طریق Cron Job فراخوانی شود
     * تصاویر قدیمی‌تر از N روز حذف می‌شوند.
     */
    public static function cleanupOldUploads(int $days = 30): int {
        $count = 0;
        $uploads_dir = __DIR__ . '/../../public/assets/uploads/';
        $now = time();
        $max_age = $days * 86400;

        if (!file_exists($uploads_dir)) {
            return 0;
        }

        $files = glob($uploads_dir . '*.{webp,jpg,jpeg,png,gif}', GLOB_BRACE);
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $max_age) {
                unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
