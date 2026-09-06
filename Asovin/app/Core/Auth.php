<?php
namespace WHCM\Core;

/**
 * مدیریت احراز هویت و دسترسی‌های کاربران پلتفرم
 *
 * @package WHCM\Core
 */
class Auth {
    /** @var array|null */
    private static $currentUser = null;

    /**
     * ثبت نام کاربر جدید با تخصیص پیش‌فرض پلن رایگان
     *
     * @param string $name نام و نام خانوادگی
     * @param string $email ایمیل
     * @param string $password رمز عبور خام
     * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
     */
    public static function register(string $name, string $email, string $password, string $business_name = '', string $business_type = ''): array {
        $db = Bootstrap::getDB();

        // بررسی یکتایی ایمیل
        $stmt = $db->prepare("SELECT id, role, status, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing) {
            return [
                'success' => false,
                'message' => 'کاربری با این نشانی ایمیل قبلاً در سامانه ثبت‌نام کرده است.'
            ];
        }

        // هش کردن امن رمز عبور
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $db->beginTransaction();
        try {
            // ۱. ایجاد کاربر — تنها اولین ثبت‌نام‌کننده سوپرادمین می‌شود
            $countStmt = $db->query("SELECT COUNT(*) as cnt FROM users");
            $user_count = (int)$countStmt->fetch()['cnt'];
            
            $role = 'user';
            if ($user_count === 0) {
                $role = 'superadmin';
            }

            $stmt = $db->prepare("INSERT INTO users (name, email, password, role, status, business_name, business_type) VALUES (?, ?, ?, ?, 'active', ?, ?)");
            $stmt->execute([$name, $email, $hashed_password, $role, $business_name, $business_type]);
            $user_id = (int)$db->lastInsertId();

            // ۲. بررسی وجود پلن رایگان یا ایجاد پلن رایگان پیش‌فرض
            $stmt = $db->prepare("SELECT id, duration_days FROM plans WHERE price = 0 LIMIT 1");
            $stmt->execute();
            $free_plan = $stmt->fetch();

            if (!$free_plan) {
                // ساخت پلن رایگان پیش‌فرض طبق خواسته‌ی کاربر (۱ تلگرام، ۱ بله، حداکثر ۱۰ پست)
                $features = json_encode([
                    'gold_ticker' => false,
                    'auto_responder' => false,
                    'woocommerce' => false,
                    'stats' => true
                ], JSON_UNESCAPED_UNICODE);

                $stmt = $db->prepare("INSERT INTO plans (title, price, duration_days, max_channels, max_posts, features) VALUES ('پلن آزمایشی رایگان', 0, 0, 2, 10, ?)");
                $stmt->execute([$features]);
                $plan_id = (int)$db->lastInsertId();
                $duration_days = 0; // بدون انقضا (محدودیت تعداد کل پست = ۱۰)
            } else {
                $plan_id = (int)$free_plan['id'];
                $duration_days = (int)$free_plan['duration_days'];
            }

            // ۳. انتساب اشتراک آزمایشی رایگان
            $start_date = date('Y-m-d H:i:s');
            $end_date = $duration_days > 0 
                ? date('Y-m-d H:i:s', strtotime("+{$duration_days} days"))
                : '2099-12-30 00:00:00'; // نامحدود فرضی برای پلن رایگان

            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$user_id, $plan_id, $start_date, $end_date]);

            $db->commit();
            return [
                'success' => true,
                'message' => 'ثبت‌نام با موفقیت انجام شد. اکنون می‌توانید وارد حساب خود شوید.',
                'user_id' => $user_id
            ];

        } catch (\Exception $e) {
            $db->rollBack();
            return [
                'success' => false,
                'message' => 'خطایی رخ داد: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ورود کاربر به سامانه
     *
     * @param string $email ایمیل
     * @param string $password رمز عبور خام
     * @return array ['success' => bool, 'message' => string]
     */
    public static function login(string $email, string $password): array {
        $db = Bootstrap::getDB();

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'مشخصات ورود نامعتبر است.'
            ];
        }

        if ($user['status'] !== 'active') {
            return [
                'success' => false,
                'message' => 'حساب کاربری شما معلق یا غیرفعال شده است. با پشتیبانی تماس بگیرید.'
            ];
        }

        // بررسی رمز عبور با متد ایمن و مدرن
        if (password_verify($password, $user['password'])) {
            // بازنشانی شناسه سشن برای امنیت بیشتر
            Session::regenerate();
            Session::set('user_id', (int)$user['id']);

            self::$currentUser = $user;

            return [
                'success' => true,
                'message' => 'ورود با موفقیت انجام شد.'
            ];
        }

        return [
            'success' => false,
            'message' => 'مشخصات ورود نامعتبر است.'
        ];
    }

    /**
     * خروج کامل کاربر
     */
    public static function logout() {
        Session::destroy();
        self::$currentUser = null;
    }

    /**
     * بازیابی مشخصات کاربر فعلی
     */
    public static function user() {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $user_id = Session::get('user_id');
        if (!$user_id) {
            return null;
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT id, name, email, role, status, birthday, created_at FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active') {
            self::$currentUser = $user;
            return self::$currentUser;
        }

        // اگر کاربر معلق شده باشد، سشن منقضی می‌شود
        self::logout();
        return null;
    }

    /**
     * بررسی ورود کاربر
     */
    public static function check(): bool {
        return self::user() !== null;
    }

    /**
     * بررسی اینکه آیا کاربر مدیر کل است
     */
    public static function isSuperAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'superadmin';
    }

    /**
     * بررسی اینکه آیا کاربر پشتیبان است
     */
    public static function isSupportAgent(): bool {
        $user = self::user();
        return $user && $user['role'] === 'support_agent';
    }

    /**
     * بررسی اینکه آیا کاربر مدیر کل یا پشتیبان است
     */
    public static function isAdminOrSupport(): bool {
        return self::isSuperAdmin() || self::isSupportAgent();
    }

    /**
     * شناسه مستاجر جاری (Tenant ID) برای فیلتر دیتابیس
     */
    public static function tenantId() {
        $user = self::user();
        return $user ? (int)$user['id'] : null;
    }
}
