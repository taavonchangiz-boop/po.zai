<?php
namespace WHCM\Api\Controllers;

use WHCM\Api\MobileApiResponse;
use WHCM\Api\MobileApiAuth;
use WHCM\Api\MobileApiRouter;
use WHCM\Core\Bootstrap;
use WHCM\Core\Auth;
use WHCM\Core\RateLimit;
use WHCM\Domain\Referral;
use WHCM\Core\EmailTemplate;
use WHCM\Domain\VerificationCode;
use WHCM\Core\Sms;

/**
 * کنترلر احراز هویت API موبایل
 *
 * شامل: ورود، ثبت‌نام، خروج، پروفایل، تغییر رمز، بازیابی رمز (ایمیل و پیامک)
 *
 * @package WHCM\Api\Controllers
 */
class AuthApiController extends \WHCM\Api\MobileApiController {

    /**
     * ورود کاربر و دریافت توکن API
     * POST /api/v1/auth/login
     */
    public function login(): void {
        $data = $this->input();
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $deviceName = $data['device_name'] ?? 'android';

        // اعتبارسنجی
        $errors = $this->validate([
            'email'    => 'required',
            'password' => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        // احراز هویت و دریافت توکن
        $result = MobileApiAuth::authenticate($email, $password, $deviceName);

        if (!$result['success']) {
            MobileApiResponse::error($result['message'], 401);
        }

        $response = [
            'token' => $result['token'],
            'user'  => $result['user'],
        ];

        // پردازش کد معرف (در صورت وجود)
        $refCode = $data['ref'] ?? null;
        if (!empty($refCode)) {
            Referral::processRegistration($result['user']['id'], $refCode);
        }

        MobileApiResponse::success($response, $result['message']);
    }

    /**
     * ثبت‌نام کاربر جدید
     * POST /api/v1/auth/register
     */
    public function register(): void {
        $data = $this->input();

        $name     = $data['name'] ?? null;
        $email    = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $passwordConfirm = $data['password_confirm'] ?? null;
        $businessName = $data['business_name'] ?? '';
        $businessType = $data['business_type'] ?? '';
        $refCode  = $data['ref'] ?? null;

        // اعتبارسنجی
        $errors = $this->validate([
            'name'            => 'required',
            'email'           => 'required|email',
            'password'        => 'required|min:6',
            'password_confirm' => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        // بررسی تطابق رمز عبور
        if ($password !== $passwordConfirm) {
            MobileApiResponse::validationError([
                'password_confirm' => 'رمز عبور و تکرار آن مطابقت ندارند.'
            ]);
        }

        // بررسی یکتایی ایمیل
        $db = $this->db();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            MobileApiResponse::validationError([
                'email' => 'کاربری با این ایمیل قبلاً ثبت‌نام کرده است.'
            ]);
        }

        // ثبت‌نام از طریق Auth::register
        $regResult = Auth::register($name, $email, $password, $businessName, $businessType);

        if (!$regResult['success']) {
            MobileApiResponse::error($regResult['message'], 400);
        }

        $userId = $regResult['user_id'];

        // پردازش سیستم زیرمجموعه‌گیری
        if (!empty($refCode)) {
            Referral::processRegistration($userId, $refCode);
        }

        // ارسال ایمیل خوش‌آمدگویی (غیرمسدودکننده)
        try {
            EmailTemplate::sendByEvent('welcome', $userId);
        } catch (\Throwable $e) {}

        // ورود خودکار و دریافت توکن API
        $deviceName = $data['device_name'] ?? 'android';
        $authResult = MobileApiAuth::authenticate($email, $password, $deviceName);

        if (!$authResult['success']) {
            // ثبت‌نام موفق بود اما تولید توکن شکست خورد
            MobileApiResponse::success([
                'user' => [
                    'id'    => $userId,
                    'name'  => $name,
                    'email' => $email,
                ],
            ], 'ثبت‌نام با موفقیت انجام شد. لطفاً وارد شوید.');
        }

        MobileApiResponse::success([
            'token' => $authResult['token'],
            'user'  => $authResult['user'],
        ], 'ثبت‌نام با موفقیت انجام شد.');
    }

    /**
     * خروج از حساب کاربری (ابطال توکن)
     * POST /api/v1/auth/logout
     */
    public function logout(): void {
        MobileApiAuth::revokeCurrentToken();
        MobileApiResponse::success(null, 'با موفقیت خارج شدید.');
    }

    /**
     * دریافت اطلاعات کاربر فعلی
     * GET /api/v1/auth/me
     */
    public function me(): void {
        $user = $this->user();
        if (!$user) {
            MobileApiResponse::unauthorized();
        }

        $db = $this->db();

        // دریافت اطلاعات اشتراک فعال
        $stmt = $db->prepare("
            SELECT s.id, s.plan_id, s.start_date, s.end_date, s.status,
                   p.title as plan_title, p.price as plan_price,
                   p.max_channels, p.max_posts, p.features
            FROM subscriptions s
            JOIN plans p ON s.plan_id = p.id
            WHERE s.user_id = ? AND s.status = 'active'
            ORDER BY s.id DESC
            LIMIT 1
        ");
        $stmt->execute([$user['id']]);
        $subscription = $stmt->fetch();

        $subscriptionInfo = null;
        if ($subscription) {
            $features = json_decode($subscription['features'] ?? '[]', true) ?: [];
            $subscriptionInfo = [
                'id'           => (int)$subscription['id'],
                'plan_id'      => (int)$subscription['plan_id'],
                'plan_title'   => $subscription['plan_title'],
                'plan_price'   => (float)$subscription['plan_price'],
                'max_channels' => (int)$subscription['max_channels'],
                'max_posts'    => (int)$subscription['max_posts'],
                'features'     => $features,
                'start_date'   => $subscription['start_date'],
                'end_date'     => $subscription['end_date'],
                'status'       => $subscription['status'],
            ];
        }

        MobileApiResponse::success([
            'user'        => $user,
            'subscription' => $subscriptionInfo,
        ]);
    }

    /**
     * بروزرسانی پروفایل کاربر
     * PUT /api/v1/auth/profile
     */
    public function updateProfile(): void {
        $data = $this->input();
        $userId = $this->userId();

        // اعتبارسنجی
        $errors = $this->validate([
            'name'  => 'required',
            'email' => 'required|email',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $name     = $data['name'];
        $email    = $data['email'];
        $birthday = $data['birthday'] ?? null;

        // بررسی یکتایی ایمیل (به‌جز کاربر فعلی)
        $db = $this->db();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetch()) {
            MobileApiResponse::validationError([
                'email' => 'این ایمیل توسط کاربر دیگری استفاده شده است.'
            ]);
        }

        // بروزرسانی
        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, birthday = ? WHERE id = ?");
        $stmt->execute([$name, $email, $birthday, $userId]);

        // بازگرداندن اطلاعات بروزرسانی‌شده
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $updatedUser = $stmt->fetch();

        MobileApiResponse::success(
            MobileApiAuth::sanitizeUser($updatedUser),
            'پروفایل با موفقیت بروزرسانی شد.'
        );
    }

    /**
     * تغییر رمز عبور
     * POST /api/v1/auth/change-password
     */
    public function changePassword(): void {
        $data = $this->input();
        $userId = $this->userId();

        // اعتبارسنجی
        $errors = $this->validate([
            'current_password'  => 'required',
            'new_password'      => 'required|min:6',
            'confirm_password'  => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $currentPassword  = $data['current_password'];
        $newPassword      = $data['new_password'];
        $confirmPassword  = $data['confirm_password'];

        // بررسی تطابق رمز جدید
        if ($newPassword !== $confirmPassword) {
            MobileApiResponse::validationError([
                'confirm_password' => 'رمز عبور جدید و تکرار آن مطابقت ندارند.'
            ]);
        }

        $db = $this->db();

        // دریافت رمز فعلی کاربر
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password'])) {
            MobileApiResponse::error('رمز عبور فعلی نادرست است.', 400);
        }

        // هش کردن رمز جدید
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // بروزرسانی رمز عبور
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        MobileApiResponse::success(null, 'رمز عبور با موفقیت تغییر کرد.');
    }

    /**
     * درخواست بازیابی رمز عبور از طریق ایمیل
     * POST /api/v1/auth/reset-password
     */
    public function requestResetEmail(): void {
        $data = $this->input();

        // اعتبارسنجی
        $errors = $this->validate([
            'email' => 'required|email',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $email = $data['email'];
        $db = $this->db();

        // جستجوی کاربر
        $stmt = $db->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $userId = (int)$user['id'];

            // تولید توکن تصادفی ۶۴ کاراکتری
            $token = bin2hex(random_bytes(32));

            // ذخیره توکن در جدول settings (شامل زمان برای بررسی انقضا)
            $tokenData = json_encode([
                'token'      => $token,
                'created_at' => date('Y-m-d H:i:s'),
            ], JSON_UNESCAPED_UNICODE);

            $keyName = 'password_reset_token_' . $userId;

            // Upsert: حذف قبلی و درج جدید
            $stmt = $db->prepare("DELETE FROM settings WHERE key_name = ?");
            $stmt->execute([$keyName]);

            $stmt = $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, ?, ?)");
            $stmt->execute([$keyName, $tokenData]);

            // ارسال ایمیل بازیابی (غیرمسدودکننده)
            try {
                EmailTemplate::sendByEvent('password_reset', $userId, [
                    'token' => $token,
                ]);
            } catch (\Throwable $e) {}
        }

        // همیشه پاسخ موفق برمی‌گرداند (برای جلوگیری از افشای اطلاعات)
        MobileApiResponse::success(null, 'اگر ایمیل در سامانه ثبت شده باشد، لینک بازیابی ارسال خواهد شد.');
    }

    /**
     * تایید بازیابی رمز عبور از طریق ایمیل
     * POST /api/v1/auth/reset-password/confirm
     */
    public function confirmResetEmail(): void {
        $data = $this->input();

        // اعتبارسنجی
        $errors = $this->validate([
            'token'            => 'required',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $token           = $data['token'];
        $newPassword     = $data['new_password'];
        $confirmPassword = $data['confirm_password'];

        // بررسی تطابق رمز جدید
        if ($newPassword !== $confirmPassword) {
            MobileApiResponse::validationError([
                'confirm_password' => 'رمز عبور جدید و تکرار آن مطابقت ندارند.'
            ]);
        }

        $db = $this->db();

        // جستجوی توکن در جدول settings
        $stmt = $db->prepare("
            SELECT * FROM settings 
            WHERE key_name LIKE 'password_reset_token_%'
        ");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $matchedRow = null;
        $matchedUserId = null;

        foreach ($rows as $row) {
            $tokenData = json_decode($row['key_value'], true);
            if (!$tokenData || !isset($tokenData['token']) || !isset($tokenData['created_at'])) {
                continue;
            }

            if (!hash_equals($tokenData['token'], $token)) {
                continue;
            }

            // بررسی انقضا (۶۰ دقیقه)
            $createdAt = strtotime($tokenData['created_at']);
            if (time() - $createdAt > 3600) {
                continue;
            }

            // استخراج user_id از key_name
            $prefix = 'password_reset_token_';
            if (strpos($row['key_name'], $prefix) === 0) {
                $matchedUserId = (int)substr($row['key_name'], strlen($prefix));
                $matchedRow = $row;
                break;
            }
        }

        if (!$matchedRow || !$matchedUserId) {
            MobileApiResponse::error('توکن بازیابی نامعتبر یا منقضی شده است.', 400);
        }

        // هش کردن رمز جدید
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // بروزرسانی رمز عبور
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $matchedUserId]);

        // حذف توکن استفاده‌شده
        $stmt = $db->prepare("DELETE FROM settings WHERE key_name = ?");
        $stmt->execute([$matchedRow['key_name']]);

        // ابطال تمام توکن‌های API کاربر
        MobileApiAuth::revokeAllUserTokens($matchedUserId);

        MobileApiResponse::success(null, 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.');
    }

    /**
     * درخواست بازیابی رمز عبور از طریق پیامک
     * POST /api/v1/auth/reset-password-sms
     */
    public function requestResetSms(): void {
        $data = $this->input();

        // اعتبارسنجی
        $errors = $this->validate([
            'phone' => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $phone = $data['phone'];
        $db = $this->db();

        // جستجوی کاربر بر اساس شماره تلفن
        $stmt = $db->prepare("SELECT id, phone FROM users WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();

        if (!$user) {
            // همیشه پاسخ موفق (امنیت: عدم افشای وجود کاربر)
            MobileApiResponse::success(null, 'اگر شماره تلفن در سامانه ثبت شده باشد، کد تایید ارسال خواهد شد.');
            return;
        }

        $userId = (int)$user['id'];

        // Rate Limiting
        if (!RateLimit::check('api_sms_reset', 3, 300)) {
            MobileApiResponse::tooManyRequests('تعداد درخواست پیامک بیش از حد مجاز است. ۵ دقیقه صبر کنید.');
        }

        // تولید کد تایید
        $code = VerificationCode::generate($userId, 'password_reset', 5);

        // ارسال پیامک
        $smsResult = ['success' => false];
        try {
            // دریافت قالب پیامک برای بازیابی رمز
            $stmt = $db->prepare("SELECT template_id FROM sms_templates WHERE event_key = ? AND is_active = 1 LIMIT 1");
            $stmt->execute(['password_reset']);
            $smsTemplate = $stmt->fetch();

            if ($smsTemplate && !empty($smsTemplate['template_id'])) {
                $smsResult = Sms::send($phone, (int)$smsTemplate['template_id'], [
                    'code' => $code,
                ], $userId);
            }
        } catch (\Throwable $e) {}

        // ثبت rate limit (حتی اگر ارسال پیامک موفق نبود)
        RateLimit::hit('api_sms_reset', 300);

        MobileApiResponse::success(null, 'اگر شماره تلفن در سامانه ثبت شده باشد، کد تایید ارسال خواهد شد.');
    }

    /**
     * تایید کد پیامکی و تغییر رمز عبور
     * POST /api/v1/auth/verify-sms-code
     */
    public function verifySmsCode(): void {
        $data = $this->input();

        // اعتبارسنجی
        $errors = $this->validate([
            'code'             => 'required',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required',
        ], $data);

        if (!empty($errors)) {
            MobileApiResponse::validationError($errors);
        }

        $code           = $data['code'];
        $newPassword     = $data['new_password'];
        $confirmPassword = $data['confirm_password'];

        // بررسی تطابق رمز جدید
        if ($newPassword !== $confirmPassword) {
            MobileApiResponse::validationError([
                'confirm_password' => 'رمز عبور جدید و تکرار آن مطابقت ندارند.'
            ]);
        }

        $db = $this->db();

        // یافتن کاربر از طریق کد تایید
        $stmt = $db->prepare("
            SELECT vc.user_id
            FROM verification_codes vc
            WHERE vc.code = ? AND vc.type = 'password_reset' AND vc.used = 0
            ORDER BY vc.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$code]);
        $vcRecord = $stmt->fetch();

        if (!$vcRecord) {
            MobileApiResponse::error('کد تایید نامعتبر یا استفاده‌شده است.', 400);
        }

        $userId = (int)$vcRecord['user_id'];

        // تایید کد از طریق VerificationCode
        if (!VerificationCode::verify($userId, 'password_reset', $code)) {
            MobileApiResponse::error('کد تایید منقضی شده یا نامعتبر است.', 400);
        }

        // هش کردن رمز جدید
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        // بروزرسانی رمز عبور
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        // ابطال تمام توکن‌های API کاربر
        MobileApiAuth::revokeAllUserTokens($userId);

        MobileApiResponse::success(null, 'رمز عبور با موفقیت تغییر کرد. اکنون می‌توانید وارد شوید.');
    }
}
