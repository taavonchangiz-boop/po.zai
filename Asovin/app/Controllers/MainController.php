<?php
namespace WHCM\Controllers;

use WHCM\Core\Bootstrap;
use WHCM\Core\Auth;
use WHCM\Core\Csrf;
use WHCM\Core\RateLimit;
use WHCM\Core\Sms;
use WHCM\Domain\TextFormat;
use WHCM\Domain\Quota;
use WHCM\Domain\ChannelManager;
use WHCM\Domain\GoldTicker;
use WHCM\Domain\Inbox;
use WHCM\Domain\Sender;
use WHCM\Domain\LinkTracker;
use WHCM\Domain\VerificationCode;
use WHCM\Domain\Referral;
use WHCM\Domain\Wallet;
use WHCM\Core\EmailTemplate;
use WHCM\Domain\ScheduledPost;

use WHCM\Core\WebPush;

/**
 * کنترلر اصلی هدایت‌کننده و پردازشگر درخواست‌های وب
 *
 * تمام متدهای مشترک (redirect، render، flash، checkAuth، uploadAndConvertToWebp،
 * jalaliToGregorian) در BaseController قرار دارند.
 *
 * @package WHCM\Controllers
 */
class MainController extends BaseController {

    /**
     * صفحه اصلی یا فرم ورود/ثبت‌نام (همراه با کپچای محاسباتی پویا)
     */
    public function index() {
        // جلوگیری از کش شدن لندینگ پیج و فرم‌ها در هاست اشتراکی و مرورگر کاربر
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        if (Auth::check()) {
            if (Auth::isSuperAdmin()) {
                $this->redirect('/hnnh');
            } else {
                $this->redirect('/dashboard');
            }
        }

        // تولید کپچای ریاضی پویا (random_int — مقاوم در برابر پیش‌بینی)
        $num1 = random_int(1, 9);
        $num2 = random_int(1, 9);
        $_SESSION['captcha_answer'] = $num1 + $num2;
        $captcha_question = "حاصل جمع " . TextFormat::fa_digits($num1) . " + " . TextFormat::fa_digits($num2) . " چقدر می‌شود؟";

        // دریافت لیست پلن‌ها جهت نمایش در لندینگ پیج
        $db = Bootstrap::getDB();
        $plans = [];
        try {
            $plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();
        } catch (\Throwable $e) {
            $plans = [];
        }

        $this->render('home', [
            'title' => 'پُست‌یار | سامانه هوشمند مدیریت و انتشار کانال‌ها',
            'plans' => $plans,
            'captcha_question' => $captcha_question,
            'csrf_field' => Csrf::field(),
            'message' => $this->getFlashMessage()
        ]);
    }

    /**
     * عملیات ورود کاربر
     */
    public function handleLogin() {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/');
        }

        // بررسی کپچای ریاضی ضد ربات
        $captcha = (int)($_POST['captcha'] ?? 0);
        $saved_captcha = isset($_SESSION['captcha_answer']) ? (int)$_SESSION['captcha_answer'] : null;
        if ($saved_captcha === null || $captcha !== $saved_captcha) {
            $this->setFlashMessage('پاسخ سوال امنیتی (کپچا) نادرست است.');
            $this->redirect('/');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!RateLimit::check('login_web', 5, 60)) {
            $this->setFlashMessage('تعداد تلاش‌های ناموفق شما بیش از حد مجاز است. لطفاً ۱ دقیقه صبر کنید.');
            $this->redirect('/');
        }

        $res = Auth::login($email, $password);
        if ($res['success']) {
            RateLimit::clear('login_web');
            if (Auth::isSuperAdmin()) {
                $this->redirect('/hnnh');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            RateLimit::hit('login_web', 60);
            $this->setFlashMessage($res['message']);
            $this->redirect('/');
        }
    }

    /**
     * عملیات ثبت‌نام کاربر
     */
    public function handleRegister() {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/');
        }

        // پذیرش سیاست حریم خصوصی برای ثبت‌نام الزامی است (اعتبارسنجی سمت سرور)
        if (($_POST['privacy_consent'] ?? '') !== '1') {
            $this->setFlashMessage('برای ایجاد حساب، مطالعه و تأیید سیاست حریم خصوصی الزامی است.');
            $this->redirect('/');
        }

        // بررسی کپچای ریاضی ضد ربات
        $captcha = (int)($_POST['captcha'] ?? 0);
        $saved_captcha = isset($_SESSION['captcha_answer']) ? (int)$_SESSION['captcha_answer'] : null;
        if ($saved_captcha === null || $captcha !== $saved_captcha) {
            $this->setFlashMessage('پاسخ سوال امنیتی (کپچا) نادرست است.');
            $this->redirect('/');
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $business_name = trim($_POST['business_name'] ?? '');
        $business_type = trim($_POST['business_type'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            $this->setFlashMessage('لطفاً تمامی فیلدها را با دقت تکمیل کنید.');
            $this->redirect('/');
        }

        if ($password !== $password_confirm) {
            $this->setFlashMessage('کلمه عبور با تکرار آن مطابقت ندارد.');
            $this->redirect('/');
        }

        $res = Auth::register($name, $email, $password, $business_name, $business_type);
        if ($res['success']) {
            // ---- ورود خودکار کاربر بلافاصله پس از ثبت‌نام موفق ----
            // استفاده از Auth::login() به جای ست مستقیم سشن — سازگاری کامل با LiteSpeed
            Auth::login($email, $password);
            RateLimit::clear('login_web');

            // ---- پردازش‌های پس از ثبت‌نام (غیرمسدودکننده) ----
            if (!empty($res['user_id'])) {
                try {
                    // اولویت خواندن از POST (فرم) و سپس GET (لینک مستقیم)
                    $referralCode = trim($_POST['ref'] ?? $_GET['ref'] ?? '');
                    Referral::processRegistration((int)$res['user_id'], !empty($referralCode) ? $referralCode : null);
                    Referral::getUserReferralCode((int)$res['user_id']);
                } catch (\Throwable $e) {
                    error_log('[Postyar] Post-register referral error: ' . $e->getMessage());
                }

                try {
                    EmailTemplate::sendByEvent('welcome', (int)$res['user_id']);
                } catch (\Throwable $e) {
                    error_log('[Postyar] Welcome email failed for user #' . $res['user_id'] . ': ' . $e->getMessage());
                }
            }

            $this->setFlashMessage('ثبت‌نام شما با موفقیت انجام شد و به صورت خودکار وارد حساب شدید! ✨');
            if (Auth::isSuperAdmin()) {
                $this->redirect('/hnnh');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            $this->setFlashMessage($res['message']);
            $this->redirect('/');
        }
    }

    /**
     * خروج از سیستم
     */
    public function logout() {
        Auth::logout();
        $this->setFlashMessage('شما با موفقیت از سیستم خارج شدید.');
        $this->redirect('/');
    }

    /**
     * پنل کاربری (داشبورد مستاجر)
     */
    public function dashboard() {
        // جلوگیری از کش شدن پیشخوان در هاست اشتراکی و مرورگر کاربر
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $this->checkAuth();

        $tenant_id = Auth::tenantId();
        $quota = Quota::getTenantQuota($tenant_id);
        $channels = ChannelManager::getTenantChannels($tenant_id);

        $db = Bootstrap::getDB();

        // دریافت اعلان‌های کاربر از جدول notifications
        $user_notifications = \WHCM\Domain\Notification::getRecentUnread($tenant_id, 20);
        $unread_count = \WHCM\Domain\Notification::getUnreadCount($tenant_id);

        // دریافت اطلاعات یک کانال خاص جهت ویرایش در صورت انتخاب
        $edit_channel = null;
        $edit_channel_id = (int)($_GET['edit_channel'] ?? 0);
        if ($edit_channel_id > 0) {
            $edit_channel = ChannelManager::getChannel($edit_channel_id, $tenant_id);
        }

        // دریافت تنظیمات اختصاصی مستأجر (مثلا تنظیمات ربات طلا)
        $stmt = $db->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = ?");
        $stmt->execute([$tenant_id]);
        $settings_rows = $stmt->fetchAll();
        $settings = [];
        foreach ($settings_rows as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }

        // دریافت لیست کامل کلمات کلیدی پاسخگوی خودکار مستأجر
        $stmt = $db->prepare("SELECT ar.*, c.name as channel_name, c.platform as channel_platform FROM auto_replies ar JOIN channels c ON ar.channel_id = c.id WHERE ar.tenant_id = ? ORDER BY ar.id DESC");
        $stmt->execute([$tenant_id]);
        $auto_replies = $stmt->fetchAll();

        // دریافت وضعیت فعال/غیرفعال پاسخگوی خودکار هر کانال
        $responder_settings = [];
        try {
            $stmt2 = $db->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = ? AND key_name LIKE 'responder_enabled_%'");
            $stmt2->execute([$tenant_id]);
            $rs_rows = $stmt2->fetchAll();
            foreach ($rs_rows as $r) {
                $responder_settings[$r['key_name']] = $r['key_value'];
            }
        } catch (\Throwable $e) {}

        // دریافت تاریخچه پست‌های ارسالی مستأجر — LIMIT 50
        $stmt = $db->prepare("
            SELECT p.* FROM posts p 
            WHERE p.tenant_id = ? 
            ORDER BY p.id DESC
            LIMIT 50
        ");
        $stmt->execute([$tenant_id]);
        $posts = $stmt->fetchAll();

        // دریافت آمار کلیک فقط برای همین ۵۰ پست (بهینه‌تر از JOIN روی کل جدول)
        $post_clicks = [];
        if (!empty($posts)) {
            $post_ids = array_column($posts, 'id');
            $placeholders = implode(',', array_fill(0, count($post_ids), '?'));
            $stmt_cl = $db->prepare("SELECT post_id, COUNT(*) as clicks, COUNT(DISTINCT ip) as unique_clicks FROM clicks_log WHERE post_id IN ($placeholders) GROUP BY post_id");
            $stmt_cl->execute($post_ids);
            $cl_rows = $stmt_cl->fetchAll();
            foreach ($cl_rows as $cl) {
                $post_clicks[(int)$cl['post_id']] = ['clicks' => (int)$cl['clicks'], 'unique_clicks' => (int)$cl['unique_clicks']];
            }
            // ادغام آمار کلیک با آرایه پست‌ها
            foreach ($posts as &$post) {
                $pid = (int)$post['id'];
                $post['clicks'] = $post_clicks[$pid]['clicks'] ?? 0;
                $post['unique_clicks'] = $post_clicks[$pid]['unique_clicks'] ?? 0;
            }
            unset($post);
        }

        // دریافت کدهای تخفیف اختصاصی کاربر
        $stmt = $db->prepare("SELECT do.*, p.title as plan_title FROM discount_offers do JOIN plans p ON do.plan_id = p.id WHERE do.user_id = ? AND do.used = 0");
        $stmt->execute([$tenant_id]);
        $offers = $stmt->fetchAll();

        // دریافت صندوق پیام
        $stmt = $db->prepare("SELECT i.*, c.name as channel_name FROM inbox i JOIN channels c ON i.channel_id = c.id WHERE i.tenant_id = ? ORDER BY i.id DESC LIMIT 15");
        $stmt->execute([$tenant_id]);
        $inbox = $stmt->fetchAll();

        // دریافت لیست تیکت‌های پشتیبانی کاربر — LIMIT 50
        $stmt = $db->prepare("SELECT * FROM tickets WHERE user_id = ? ORDER BY id DESC LIMIT 50");
        $stmt->execute([$tenant_id]);
        $tickets = $stmt->fetchAll();

        // دریافت دسته‌بندی‌های تیکت از دیتابیس (مرتب‌شده)
        $ticket_categories = $db->query("SELECT * FROM ticket_categories ORDER BY sort_order ASC, id ASC")->fetchAll();
        // ساخت مپ slug => title برای نمایش سریع در لیست
        $category_map = [];
        foreach ($ticket_categories as $cat) {
            $category_map[$cat['slug']] = $cat['title'];
        }

        // دریافت اعلان همگانی درون‌برنامه‌ای ادمین ارشد پُست‌یار
        $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = 'global_announcement' LIMIT 1");
        $stmt->execute();
        $announcement_json = $stmt->fetchColumn();
        $announcement = $announcement_json ? json_decode($announcement_json, true) : null;

        // بررسی خوانده‌نشده بودن اعلان
        $announcement_unread = false;
        if ($announcement) {
            $ann_id = $announcement['id'] ?? ($announcement['title'] ?? '');
            $stmt_r = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'last_read_announcement_id' LIMIT 1");
            $stmt_r->execute([$tenant_id]);
            $last_read = $stmt_r->fetchColumn();
            $announcement_unread = ($last_read !== $ann_id);
        }

        // دریافت لیست پلن‌ها جهت خرید/ارتقا
        $plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();

        // دریافت سوابق اشتراک‌ها و پرداخت‌های کاربر
        $stmt_sub_hist = $db->prepare("SELECT s.*, p.title as plan_title, p.price as plan_price FROM subscriptions s LEFT JOIN plans p ON s.plan_id = p.id WHERE s.user_id = ? ORDER BY s.id DESC LIMIT 20");
        $stmt_sub_hist->execute([$tenant_id]);
        $subscription_history = $stmt_sub_hist->fetchAll();

        $stmt_pay_hist = $db->prepare("SELECT pay.*, p.title as plan_title FROM payments pay LEFT JOIN plans p ON pay.plan_id = p.id WHERE pay.user_id = ? ORDER BY pay.id DESC LIMIT 20");
        $stmt_pay_hist->execute([$tenant_id]);
        $payment_history = $stmt_pay_hist->fetchAll();

        $this->render('dashboard', [
            'title' => 'داشبورد کاربری',
            'user' => Auth::user(),
            'quota' => $quota,
            'channels' => $channels,
            'edit_channel' => $edit_channel,
            'settings' => $settings,
            'auto_replies' => $auto_replies,
            'responder_settings' => $responder_settings,
            'posts' => $posts,
            'offers' => $offers,
            'inbox' => $inbox,
            'tickets' => $tickets,
            'ticket_categories' => $ticket_categories,
            'category_map' => $category_map,
            'announcement' => $announcement,
            'announcement_unread' => $announcement_unread,
            'user_notifications' => $user_notifications,
            'unread_count' => $unread_count,
            'plans' => $plans,
            'subscription_history' => $subscription_history,
            'payment_history' => $payment_history,
            'csrf_field' => Csrf::field(),
            'message' => $this->getFlashMessage()
        ]);
    }

    /**
     * بروزرسانی مشخصات کاربری (نام و ایمیل)
     */
    public function handleUpdateProfile() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $this->setFlashMessage('تمامی فیلدها الزامی هستند.');
            $this->redirect('/dashboard');
        }

        $birthday = trim($_POST['birthday'] ?? '');

        $db = Bootstrap::getDB();
        // چک کردن یکتایی ایمیل برای دیگران
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $tenant_id]);
        if ($stmt->fetch()) {
            $this->setFlashMessage('این نشانی ایمیل قبلاً توسط کاربر دیگری ثبت شده است.');
            $this->redirect('/dashboard');
        }

        $stmt = $db->prepare("UPDATE users SET name = ?, email = ?, birthday = ? WHERE id = ?");
        $stmt->execute([$name, $email, $birthday, $tenant_id]);

        $this->setFlashMessage('پروفایل کاربری شما با موفقیت بروزرسانی شد. ✔');
        $this->redirect('/dashboard');
    }

    /**
     * تغییر رمز عبور کاربر با تایید رمز فعلی
     */
    public function handleChangePassword() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $current_pass = $_POST['current_password'] ?? '';
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $this->setFlashMessage('پر کردن تمامی فیلدهای کلمه عبور الزامی است.');
            $this->redirect('/dashboard');
        }

        if ($new_pass !== $confirm_pass) {
            $this->setFlashMessage('رمز عبور جدید با تکرار آن مطابقت ندارد.');
            $this->redirect('/dashboard');
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$tenant_id]);
        $user_pass = $stmt->fetchColumn();

        if (!password_verify($current_pass, $user_pass)) {
            $this->setFlashMessage('کلمه عبور فعلی شما نادرست است.');
            $this->redirect('/dashboard');
        }

        $hashed = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed, $tenant_id]);

        $this->setFlashMessage('کلمه عبور شما با موفقیت تغییر یافت. ✔');
        $this->redirect('/dashboard');
    }

    /**
     * ویرایش کامل کانال و لینک‌ها و دکمه‌های شیشه‌ای تعاملی
     */
    public function handleEditChannel() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $id = (int)($_POST['channel_id'] ?? 0);

        $channel = ChannelManager::getChannel($id, $tenant_id);
        if (!$channel) {
            $this->setFlashMessage('کانال مورد نظر یافت نشد.');
            $this->redirect('/dashboard');
        }

        $name = trim($_POST['name'] ?? '');
        $platform = trim($_POST['platform'] ?? '');
        $channel_id = trim($_POST['channel_id_val'] ?? '');
        $token = trim($_POST['token'] ?? '');

        if (empty($name) || empty($channel_id) || empty($token)) {
            $this->setFlashMessage('تمامی فیلدهای اصلی کانال را تکمیل کنید.');
            $this->redirect('/dashboard?edit_channel=' . $id);
        }

        // پردازش ساختار لینک‌های سه‌گانه
        $links = [
            ['name' => trim($_POST['link_name_1'] ?? ''), 'url' => trim($_POST['link_url_1'] ?? '')],
            ['name' => trim($_POST['link_name_2'] ?? ''), 'url' => trim($_POST['link_url_2'] ?? '')],
            ['name' => trim($_POST['link_name_3'] ?? ''), 'url' => trim($_POST['link_url_3'] ?? '')]
        ];

        // پردازش دکمه‌های شیشه‌ای تعاملی
        $buttons_active = isset($_POST['buttons_active']) ? true : false;
        $buttons = [
            ['text' => trim($_POST['btn_text_1'] ?? ''), 'url' => trim($_POST['btn_url_1'] ?? '')],
            ['text' => trim($_POST['btn_text_2'] ?? ''), 'url' => trim($_POST['btn_url_2'] ?? '')]
        ];

        $button_config = [
            'active' => $buttons_active,
            'buttons' => $buttons
        ];

        $db = Bootstrap::getDB();

        // اگر آیدی کانال یا پلتفرم عوض شده باشد، باید چک ضد تقلب بکنیم
        if ($channel['channel_id'] !== $channel_id || $channel['platform'] !== $platform) {
            $stmt = $db->prepare("SELECT owner_user_id FROM channel_registry WHERE platform = ? AND channel_id = ? LIMIT 1");
            $stmt->execute([$platform, $channel_id]);
            $reg = $stmt->fetch();
            if ($reg && (int)$reg['owner_user_id'] !== $tenant_id) {
                $this->setFlashMessage('این شناسه کانال قبلاً توسط کاربر دیگری ثبت شده و قفل است.');
                $this->redirect('/dashboard?edit_channel=' . $id);
            }
            if (!$reg) {
                // ثبت در رجیستری جهانی
                $stmt = $db->prepare("INSERT INTO channel_registry (platform, channel_id, owner_user_id) VALUES (?, ?, ?)");
                $stmt->execute([$platform, $channel_id, $tenant_id]);
            }
        }

        // بروزرسانی کانال در جدول مستاجر
        $stmt = $db->prepare("
            UPDATE channels 
            SET name = ?, platform = ?, channel_id = ?, token = ?, link_config = ?, button_config = ? 
            WHERE id = ? AND tenant_id = ?
        ");
        $stmt->execute([
            $name,
            $platform,
            $channel_id,
            $token,
            json_encode($links, JSON_UNESCAPED_UNICODE),
            json_encode($button_config, JSON_UNESCAPED_UNICODE),
            $id,
            $tenant_id
        ]);

        $this->setFlashMessage('تنظیمات کانال با موفقیت بروزرسانی شد. ✔');
        $this->redirect('/dashboard');
    }

    /**
     * عملیات افزودن کانال جدید
     */
    public function handleAddChannel() {
        $this->checkAuth();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $name = trim($_POST['name'] ?? '');
        $platform = trim($_POST['platform'] ?? '');
        $channel_id = trim($_POST['channel_id'] ?? '');
        $token = trim($_POST['token'] ?? '');

        $res = ChannelManager::addChannel($name, $platform, $channel_id, $token);
        $this->setFlashMessage($res['message']);
        $this->redirect('/dashboard');
    }

    /**
     * عملیات حذف کانال (POST با CSRF)
     */
    public function handleDeleteChannel() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $id = (int)($_POST['channel_id'] ?? 0);

        if (ChannelManager::deleteChannel($id)) {
            $this->setFlashMessage('کانال با موفقیت حذف گردید (شناسه کانال به جهت قوانین ضدتقلب قفل باقی می‌ماند).');
        } else {
            $this->setFlashMessage('امکان حذف کانال وجود ندارد یا کانال متعلق به شما نیست.');
        }

        $this->redirect('/dashboard');
    }

    /**
     * عملیات ثبت پرداخت رسید مستقیم (کارت به کارت / بلو بانک)
     */
    public function handlePaymentSubmit() {
        $this->checkAuth();
        set_time_limit(60);

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $plan_id = (int)($_POST['plan_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $ref_num = trim($_POST['reference_num'] ?? '');

        // اعتبارسنجی وجود پلن قبل از ثبت پرداخت (جلوگیری از خطای FOREIGN KEY)
        if ($plan_id <= 0) {
            $this->setFlashMessage('خطا: پلن انتخابی نامعتبر است. لطفاً دوباره تلاش کنید.');
            $this->redirect('/dashboard');
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT id, price FROM plans WHERE id = ? LIMIT 1");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        if (!$plan) {
            $this->setFlashMessage('خطا: پلن انتخابی یافت نشد. ممکن است حذف شده باشد.');
            $this->redirect('/dashboard');
        }

        // اعتبارسنجی مبلغ
        if ($amount <= 0) {
            $this->setFlashMessage('خطا: مبلغ پرداخت نامعتبر است.');
            $this->redirect('/dashboard');
        }

        // آپلود خودکار عکس رسید پرداخت به فرمت وب‌پی
        $receipt_photo = $this->uploadAndConvertToWebp('receipt_photo', 'receipts');

        // ثبت پرداخت با وضعیت در انتظار تایید به همراه عکس رسید
        try {
            $stmt = $db->prepare("INSERT INTO payments (user_id, plan_id, amount, reference_num, payment_method, status, receipt_photo) VALUES (?, ?, ?, ?, 'card_to_card', 'pending', ?)");
            $stmt->execute([$tenant_id, $plan_id, $amount, $ref_num, $receipt_photo]);
        } catch (\PDOException $e) {
            error_log('[Postyar] Payment insert failed for user #' . $tenant_id . ', plan #' . $plan_id . ': ' . $e->getMessage());
            $this->setFlashMessage('خطا در ثبت رسید پرداخت. لطفاً دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.');
            $this->redirect('/dashboard');
        }

        $this->setFlashMessage('رسید پرداخت شما با موفقیت ثبت شد و در صف تایید مدیر قرار گرفت. پس از بررسی، اشتراک شما فعال خواهد شد.');
        $this->redirect('/dashboard');
    }

    /**
     * پنل مدیریت کل (Super Admin) و پشتیبان
     */
    public function admin() {
        // جلوگیری از کش شدن پنل مدیریت در هاست اشتراکی و مرورگر کاربر
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // پشتیبان فقط دسترسی تیکت دارد
        $is_support = Auth::isSupportAgent();
        if ($is_support) {
            $this->checkAdminOrSupport();
        } else {
            $this->checkSuperAdmin();
        }

        $db = Bootstrap::getDB();

        // سیستم Pagination — تعداد آیتم در هر صفحه
        $per_page = 20;
        $current_page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        // ۱. لیست کاربران با JOIN بهینه + Pagination
        $admin_id = Auth::tenantId() ?: 0;
        $stmt_users = $db->prepare("
            SELECT u.id, u.name, u.email, u.role, u.status, u.created_at,
                   u.business_name, u.business_type,
                   COALESCE(c.cnt, 0) as channel_count,
                   COALESCE(pc.cnt, 0) as posts_count,
                   COALESCE(tc.cnt, 0) as tickets_count,
                   COALESCE(ps.total_spent, 0) as total_spent,
                   s.end_date,
                   p.title as plan_title
            FROM users u
            LEFT JOIN (SELECT tenant_id, COUNT(*) as cnt FROM channels GROUP BY tenant_id) c ON c.tenant_id = u.id
            LEFT JOIN (SELECT tenant_id, COUNT(*) as cnt FROM posts GROUP BY tenant_id) pc ON pc.tenant_id = u.id
            LEFT JOIN (SELECT user_id, COUNT(*) as cnt FROM tickets GROUP BY user_id) tc ON tc.user_id = u.id
            LEFT JOIN (SELECT user_id, COALESCE(SUM(amount), 0) as total_spent FROM payments WHERE status = 'approved' GROUP BY user_id) ps ON ps.user_id = u.id
            LEFT JOIN (SELECT user_id, plan_id, end_date FROM subscriptions WHERE status = 'active' GROUP BY user_id) s ON s.user_id = u.id
            LEFT JOIN plans p ON s.plan_id = p.id
            WHERE u.id != ?
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt_users->bindValue(1, $admin_id, \PDO::PARAM_INT);
        $stmt_users->bindValue(2, $per_page, \PDO::PARAM_INT);
        $stmt_users->bindValue(3, $offset, \PDO::PARAM_INT);
        $stmt_users->execute();
        $users = $stmt_users->fetchAll();

        // اضافه کردن تاریخ شمسی برای modal پروفایل ۳۶۰ درجه
        foreach ($users as &$u) {
            $u['created_at_fa'] = TextFormat::mysql_to_jalali($u['created_at']);
            $u['end_date_fa'] = (!empty($u['end_date']) && $u['end_date'] !== '2099-12-31 23:59:59')
                ? TextFormat::mysql_to_jalali($u['end_date'], false)
                : 'بدون انقضا / دائمی';
        }
        unset($u);

        // تعداد کل کاربران برای pagination (prepared statement — جلوگیری از SQL injection)
        $stmt_count = $db->prepare("SELECT COUNT(*) FROM users WHERE id != ?");
        $stmt_count->execute([$admin_id]);
        $total_users = (int)$stmt_count->fetchColumn();
        $total_user_pages = max(1, (int)ceil($total_users / $per_page));

        // ۲. پرداخت‌ها — فقط ۵۰ رکورد آخر (با pagination مشابه)
        $stmt_payments = $db->prepare("
            SELECT p.*, u.name as user_name, u.email as user_email, pl.title as plan_title 
            FROM payments p 
            JOIN users u ON p.user_id = u.id 
            JOIN plans pl ON p.plan_id = pl.id 
            ORDER BY p.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt_payments->bindValue(1, $per_page, \PDO::PARAM_INT);
        $stmt_payments->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt_payments->execute();
        $payments = $stmt_payments->fetchAll();

        $stmt_count_payments = $db->prepare("SELECT COUNT(*) FROM payments");
        $stmt_count_payments->execute();
        $total_payments = (int)$stmt_count_payments->fetchColumn();
        $total_payment_pages = max(1, (int)ceil($total_payments / $per_page));

        // ۳. لیست پلن‌های فعال
        $plans = $db->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll();

        // ۴. بررسی وجود درخواست ویرایش یک پلن اشتراکی خاص
        $edit_plan = null;
        $edit_plan_id = (int)($_GET['edit_plan'] ?? 0);
        if ($edit_plan_id > 0) {
            $stmt = $db->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
            $stmt->execute([$edit_plan_id]);
            $edit_plan = $stmt->fetch() ?: null;
        }

        // ۵. تیکت‌ها — فقط ۵۰ رکورد آخر
        $stmt_tickets = $db->prepare("
            SELECT t.*, u.name as user_name, u.email as user_email 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            ORDER BY (t.status = 'open') DESC, t.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt_tickets->bindValue(1, $per_page, \PDO::PARAM_INT);
        $stmt_tickets->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt_tickets->execute();
        $tickets = $stmt_tickets->fetchAll();

        $stmt_count_tickets = $db->prepare("SELECT COUNT(*) FROM tickets");
        $stmt_count_tickets->execute();
        $total_tickets = (int)$stmt_count_tickets->fetchColumn();
        $total_ticket_pages = max(1, (int)ceil($total_tickets / $per_page));

        // ۶. آمار داشبورد مدیریت
        $active_users_count = (int)$db->query("SELECT COUNT(*) FROM users WHERE id != " . (int)$admin_id . " AND status = 'active'")->fetchColumn();
        $pending_p_count = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
        $open_t_count = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn();
        $active_subs_count = (int)$db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'")->fetchColumn();
        $total_channels = (int)$db->query("SELECT COUNT(*) FROM channels")->fetchColumn();
        $total_revenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'approved'")->fetchColumn();

        // دریافت دسته‌بندی‌های تیکت و لیست پشتیبان‌ها
        try {
            $ticket_categories = $db->query("SELECT * FROM ticket_categories ORDER BY sort_order ASC, id ASC")->fetchAll();
        } catch (\Throwable $e) { $ticket_categories = []; }
        try {
            $support_agents = $db->query("SELECT id, name, email FROM users WHERE role = 'support_agent' AND status = 'active' ORDER BY id ASC")->fetchAll();
        } catch (\Throwable $e) { $support_agents = []; }

        // ۷. دریافت تنظیمات سراسری (tenant_id=0) برای فرم‌های ادمین
        $admin_settings = [];
        $stmt_settings = $db->prepare("SELECT key_name, key_value FROM settings WHERE tenant_id = 0");
        $stmt_settings->execute([]);
        foreach ($stmt_settings->fetchAll() as $row) {
            $admin_settings[$row['key_name']] = $row['key_value'];
        }

        // ۸. دریافت کدهای تخفیف فعال
        $discounts = $db->query("SELECT * FROM discount_codes ORDER BY id DESC")->fetchAll();

        $this->render('admin', [
            'title' => $is_support ? 'پنل پشتیبانی' : 'پنل مدیریت ارشد کل',
            'is_support' => $is_support,
            'users' => $users,
            'total_user_pages' => $total_user_pages,
            'current_page' => $current_page,
            'payments' => $payments,
            'plans' => $plans,
            'edit_plan' => $edit_plan,
            'tickets' => $tickets,
            'ticket_categories' => $ticket_categories,
            'support_agents' => $support_agents,
            'admin_settings' => $admin_settings,
            'discounts' => $discounts ?? [],
            'csrf_field' => Csrf::field(),
            'message' => $this->getFlashMessage(),
            'total_users' => $total_users,
            'active_users_count' => $active_users_count,
            'pending_p_count' => $pending_p_count,
            'open_t_count' => $open_t_count,
            'active_subs_count' => $active_subs_count,
            'total_channels' => $total_channels,
            'total_revenue' => $total_revenue
        ]);
    }

    public function handleApprovePayment(){ return (new \WHCM\Modules\Billing\Controllers\PaymentController)->approve(); }
    public function handleCreatePlan(){ return (new \WHCM\Modules\Billing\Controllers\PlanController)->create(); }
    /**
     * ردیابی کلیک و انتقال به لینک مقصد نهایی
     */
    public function handleClick() {
        $post_id = (int)($_GET['p'] ?? 0);
        $channel_id = (int)($_GET['c'] ?? 0);

        if ($post_id > 0 && $channel_id > 0) {
            $db = Bootstrap::getDB();

            // ثبت لاگ کلیک برای آنالیتیکس فوق‌حرفه‌ای تفکیکی
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmt = $db->prepare("INSERT INTO clicks_log (post_id, channel_id, ip, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$post_id, $channel_id, $ip, $ua]);

            // بروزرسانی آمار تجمیعی کلیک‌ها
            $stmt = $db->prepare("UPDATE post_channel_stats SET clicks = clicks + 1 WHERE post_id = ? AND channel_id = ?");
            $stmt->execute([$post_id, $channel_id]);

            // دریافت لینک هدف متناظر با کانال
            $stmt = $db->prepare("SELECT link_config FROM channels WHERE id = ? LIMIT 1");
            $stmt->execute([$channel_id]);
            $channel = $stmt->fetch();

            if ($channel) {
                $links = json_decode($channel['link_config'] ?? '[]', true);
                // لینک دوم معمولاً لینک مستقیم فروشگاه یا ارجاعی اول است
                $url = !empty($links[0]['url']) ? $links[0]['url'] : '/';
                $this->redirect($url);
            }
        }

        $this->redirect('/');
    }

    /**
     * دریافت اطلاعات وبهوک‌های ربات‌ها (با اعتبارسنجی امنیتی secret_token)
     */
    public function handleApiWebhook() {
        $channel_id = (int)($_GET['channel_id'] ?? 0);
        if ($channel_id <= 0) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'شناسه کانال نامعتبر است.']);
            exit;
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = ? LIMIT 1");
        $stmt->execute([$channel_id]);
        $channel = $stmt->fetch();

        if (!$channel) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'کانال یافت نشد.']);
            exit;
        }

        // ---- اعتبارسنجی secret_token تلگرام ----
        if ($channel['platform'] === 'telegram' && !empty($channel['webhook_secret'])) {
            $header_secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if (!hash_equals($channel['webhook_secret'], $header_secret)) {
                http_response_code(403);
                echo json_encode(['ok' => false, 'error' => 'توکن امنیتی نامعتبر است.']);
                exit;
            }
        }

        Inbox::handleWebhook($channel);
        echo json_encode(['ok' => true]);
        exit;
    }

    /**
     * ذخیره تنظیمات ربات هوشمند طلا و سکه مستأجر
     */
    public function handleSaveGoldSettings() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            if ($this->isAjax()) { echo json_encode(['success'=>false,'message'=>'خطای امنیتی'],JSON_UNESCAPED_UNICODE); return; }
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $db = Bootstrap::getDB();

        $schedule = trim($_POST['gold_schedule'] ?? 'manual');
        $api_url = trim($_POST['gold_api_url'] ?? '');
        $currency = trim($_POST['gold_currency'] ?? 'toman');
        $template = trim($_POST['gold_template'] ?? '');
        
        // آپلود فیزیکی عکس طلا به صورت خودکار به وب‌پی
        $image_url = $this->uploadAndConvertToWebp('gold_image', 'uploads');
        if (empty($image_url)) {
            // حفظ تصویر قبلی در صورت عدم آپلود تصویر جدید
            $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'gold_image_url' LIMIT 1");
            $stmt->execute([$tenant_id]);
            $image_url = $stmt->fetchColumn() ?: '';
        }

        $channel_ids = isset($_POST['gold_channels']) ? array_map('intval', $_POST['gold_channels']) : [];

        // تراکنش ایمن جهت ذخیره‌سازی گروهی تنظیمات مستأجر
        $settings_to_save = [
            'gold_schedule' => $schedule,
            'gold_api_url' => $api_url,
            'gold_currency' => $currency,
            'gold_template' => $template,
            'gold_image_url' => $image_url,
            'gold_auto_channels' => json_encode($channel_ids)
        ];

        $this->saveSettingsBatch($tenant_id, $settings_to_save);

        if ($this->isAjax()) {
            echo json_encode(['success'=>true,'message'=>'تنظیمات ربات نرخ طلا با موفقیت ذخیره گردید. 🪙'],JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->setFlashMessage('تنظیمات ربات نرخ طلا با موفقیت ذخیره گردید. 🪙');
        $this->redirect('/dashboard');
    }

    /**
     * شبیه‌سازی و تست انتشار دستی و آنی نرخ طلا توسط مستأجر
     */
    public function handleTriggerGoldPublish() {
        $this->checkAuth();
        $tenant_id = Auth::tenantId();

        $db = Bootstrap::getDB();
        // دریافت آدرس API مستأجر
        $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'gold_api_url' LIMIT 1");
        $stmt->execute([$tenant_id]);
        $custom_api = $stmt->fetchColumn();

        $vals = GoldTicker::fetchValues($custom_api ?: '');
        if (!$vals['success']) {
            $this->setFlashMessage('خطا در دریافت نرخ از API: ' . $vals['message']);
            $this->redirect('/dashboard');
        }

        // دریافت کانال‌های هدف
        $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'gold_auto_channels' LIMIT 1");
        $stmt->execute([$tenant_id]);
        $channels_json = $stmt->fetchColumn();
        $channel_ids = $channels_json ? json_decode($channels_json, true) : [];

        if (empty($channel_ids)) {
            // ارسال به همه کانال‌های فعال در صورت عدم انتخاب اختصاصی
            $stmt = $db->prepare("SELECT id FROM channels WHERE tenant_id = ?");
            $stmt->execute([$tenant_id]);
            $channel_ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        if (empty($channel_ids)) {
            $this->setFlashMessage('خطا: هیچ کانالی برای ارسال خودکار طلا متصل یا انتخاب نشده است.');
            $this->redirect('/dashboard');
        }

        $title = 'اعلام نرخ لحظه‌ای بازار طلا و سکه';
        $content = GoldTicker::buildMessage($tenant_id, $vals);
        
        // دریافت عکس پیش‌فرض طلا
        $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'gold_image_url' LIMIT 1");
        $stmt->execute([$tenant_id]);
        $image = $stmt->fetchColumn() ?: '';

        $res = Sender::sendPostToChannels($tenant_id, $channel_ids, $title, $content, $image);

        if ($res['success']) {
            $this->setFlashMessage('انتشار آنی و موفقیت‌آمیز نرخ طلا به تمام کانال‌های هدف انجام گردید! ⚡🪙');
        } else {
            $this->setFlashMessage('ارسال پیام با خطا مواجه شد. جزئیات کانال‌ها را بررسی کنید.');
        }

        $this->redirect('/dashboard');
    }

    /**
     * افزودن پاسخ خودکار جدید
     */
    public function handleAddAutoReply() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $channel_id = (int)$_POST['channel_id'];
        $keyword = trim($_POST['keyword'] ?? '');
        $reply_text = trim($_POST['reply_text'] ?? '');

        if (empty($keyword) || empty($reply_text) || $channel_id <= 0) {
            $this->setFlashMessage('تمامی فیلدها الزامی هستند.');
            $this->redirect('/dashboard');
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("INSERT INTO auto_replies (tenant_id, channel_id, keyword, reply_text, active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$tenant_id, $channel_id, $keyword, $reply_text]);

        $this->setFlashMessage('پاسخ خودکار جدید با موفقیت اضافه شد. 🤖');
        $this->redirect('/dashboard');
    }

    /**
     * حذف کلمه کلیدی پاسخگو (POST با CSRF)
     */
    public function handleDeleteAutoReply() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        $id = (int)($_POST['reply_id'] ?? 0);

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM auto_replies WHERE id = ? AND tenant_id = ?");
        $stmt->execute([$id, $tenant_id]);

        $this->setFlashMessage('پاسخ خودکار کلمه کلیدی با موفقیت حذف گردید.');
        $this->redirect('/dashboard');
    }

    /**
     * علامت‌گذاری اعلان همگانی به عنوان خوانده‌شده (AJAX)
     */
    public function handleMarkAnnouncementRead() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkAuth();
        $tenant_id = Auth::tenantId();
        $db = Bootstrap::getDB();

        // ذخیره شناسه اعلان خوانده‌شده در تنظیمات کاربر
        $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = ? AND key_name = 'last_read_announcement_id' LIMIT 1");
        $stmt->execute([$tenant_id]);

        // دریافت شناسه اعلان فعلی
        $stmt_a = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = 'global_announcement' LIMIT 1");
        $stmt_a->execute();
        $ann_json = $stmt_a->fetchColumn();
        $ann_id = '';
        if ($ann_json) {
            $ann = json_decode($ann_json, true);
            $ann_id = $ann['id'] ?? ($ann['title'] ?? '');
        }

        if ($stmt->fetch()) {
            $db->prepare("UPDATE settings SET key_value = ? WHERE tenant_id = ? AND key_name = 'last_read_announcement_id'")->execute([$ann_id, $tenant_id]);
        } else {
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (?, 'last_read_announcement_id', ?)")->execute([$tenant_id, $ann_id]);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
    }

    /**
     * علامت‌گذاری یک اعلان خاص به‌عنوان خوانده‌شده (AJAX)
     */
    public function handleMarkNotificationRead() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkAuth();
        $tenant_id = Auth::tenantId();
        $notification_id = (int)($_POST['notification_id'] ?? 0);
        if ($notification_id <= 0) {
            echo json_encode(['success' => false], JSON_UNESCAPED_UNICODE);
            return;
        }
        $result = \WHCM\Domain\Notification::markAsRead($notification_id, $tenant_id);
        $remaining = \WHCM\Domain\Notification::getUnreadCount($tenant_id);
        echo json_encode(['success' => true, 'remaining' => $remaining], JSON_UNESCAPED_UNICODE);
    }

    /**
     * علامت‌گذاری تمام اعلان‌ها به‌عنوان خوانده‌شده (AJAX)
     */
    public function handleMarkAllNotificationsRead() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkAuth();
        $tenant_id = Auth::tenantId();
        \WHCM\Domain\Notification::markAllAsRead($tenant_id);
        echo json_encode(['success' => true, 'remaining' => 0], JSON_UNESCAPED_UNICODE);
    }

    /**
     * تغییر وضعیت روشن/خاموش پاسخگوی خودکار کانال (AJAX)
     */
    public function handleToggleResponder() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            echo json_encode(['success' => false, 'message' => 'خطای امنیتی'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $tenant_id = Auth::tenantId();
        $channel_id = (int)($_POST['channel_id'] ?? 0);
        $enabled = (int)($_POST['enabled'] ?? 0);
        if ($channel_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'شناسه کانال نامعتبر'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $db = Bootstrap::getDB();
        $key_name = 'responder_enabled_' . $channel_id;
        $stmt = $db->prepare("SELECT id FROM settings WHERE tenant_id = ? AND key_name = ? LIMIT 1");
        $stmt->execute([$tenant_id, $key_name]);
        if ($stmt->fetch()) {
            $db->prepare("UPDATE settings SET key_value = ? WHERE tenant_id = ? AND key_name = ?")->execute([$enabled ? '1' : '0', $tenant_id, $key_name]);
        } else {
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (?, ?, ?)")->execute([$tenant_id, $key_name, $enabled ? '1' : '0']);
        }
        echo json_encode(['success' => true, 'message' => $enabled ? 'پاسخگوی خودکار فعال شد ✅' : 'پاسخگوی خودکار غیرفعال شد ⏸'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * ایجاد، ارسال آنی یا زمان‌بندی پیام‌ها به شبکه‌های اجتماعی هدف (تلگرام و بله)
     */
    public function handleCreatePost() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();
        
        // بررسی سهمیه پست باقیمانده مستأجر
        $quota = Quota::getTenantQuota($tenant_id);
        if (!$quota['can_send_post']) {
            $this->setFlashMessage('خطا: سهمیه ارسال پست شما در این دوره به اتمام رسیده است. لطفا اشتراک خود را تمدید یا ارتقا دهید.');
            $this->redirect('/dashboard');
        }

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $send_type = trim($_POST['send_type'] ?? 'instant');
        
        // دریافت تاریخ شمسی بازشو و تبدیل به ساختار دیتابیس
        $scheduled_at = '';
        if ($send_type === 'scheduled') {
            $sched_date = trim($_POST['sched_date'] ?? '');
            if (!empty($sched_date)) {
                list($s_year, $s_month, $s_day) = explode('/', $sched_date);
                $s_year = (int)$s_year;
                $s_month = (int)$s_month;
                $s_day = (int)$s_day;
            } else {
                $s_year = 1405;
                $s_month = 1;
                $s_day = 1;
            }
            $s_hour = trim($_POST['sched_hour'] ?? '00');
            $s_minute = trim($_POST['sched_minute'] ?? '00');
            
            // ابتدا تاریخ شمسی منتخب را به میلادی تبدیل می‌کنیم تا در دیتابیس به صورت کاملاً استاندارد ذخیره شود!
            // پُست‌یار مجهز به تبدیل معکوس جلالی به میلادی فوق‌حرفه‌ای است:
            $g_date = self::jalaliToGregorian($s_year, $s_month, $s_day);
            $scheduled_at = $g_date['year'] . '-' . str_pad($g_date['month'], 2, '0', STR_PAD_LEFT) . '-' . str_pad($g_date['day'], 2, '0', STR_PAD_LEFT) . ' ' . $s_hour . ':' . $s_minute . ':00';
        }

        // آپلود خودکار و فیزیکی فایل تصویر پست و تبدیل به فرمت بهینه وب‌پی
        $media_url = $this->uploadAndConvertToWebp('media_file', 'uploads');
        
        $channel_ids = isset($_POST['post_channels']) ? array_map('intval', $_POST['post_channels']) : [];

        if (empty($title) || empty($content)) {
            $this->setFlashMessage('تمامی فیلدهای عنوان و محتوای پست الزامی هستند.');
            $this->redirect('/dashboard');
        }

        if (empty($channel_ids)) {
            $this->setFlashMessage('خطا: حداقل یک کانال هدف جهت انتشار پست انتخاب کنید.');
            $this->redirect('/dashboard');
        }

        $db = Bootstrap::getDB();

        // ثبت رکورد اولیه پست در پایگاه داده مستأجر
        $status = ($send_type === 'scheduled') ? 'scheduled' : 'draft';
        $sched_date = !empty($scheduled_at) ? $scheduled_at : null;
        $target_channels_json = json_encode($channel_ids);

        // ثبت رکورد اولیه پست — محتوا با لینک‌های ردیابی ذخیره می‌شود
        $firstChannelId = (int)($channel_ids[0] ?? 0);
        $trackedContent = $content;
        if ($firstChannelId > 0) {
            $trackedContent = LinkTracker::processContent($content, 0, $firstChannelId, $tenant_id);
        }

        $stmt = $db->prepare("INSERT INTO posts (tenant_id, title, content, media_url, status, scheduled_at, target_channels) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $title, $trackedContent, $media_url, $status, $sched_date, $target_channels_json]);
        $post_id = (int)$db->lastInsertId();

        // آپدیت post_id در لینک‌های ردیابی
        if ($firstChannelId > 0 && $trackedContent !== $content) {
            try { $db->prepare("UPDATE link_tracking SET post_id = ? WHERE post_id = 0 AND channel_id = ? AND tenant_id = ?")->execute([$post_id, $firstChannelId, $tenant_id]); } catch (\Exception $e) {}
        }

        if ($send_type === 'instant') {
            // ذخیره وضعیت «در صف ارسال» و ریدایرکت فوری
            // ارسال واقعی از طریق درخواست AJAX مجزا انجام می‌شود (پردازش صف)
            $db->prepare("UPDATE posts SET status = 'queued' WHERE id = ?")->execute([$post_id]);

            $this->setFlashMessage('پست شما با موفقیت ثبت شد و در صف ارسال قرار گرفت. ارسال به کانال‌ها به‌زودی انجام خواهد شد. ⚡');
            $this->redirect('/dashboard');
        } else {
            $this->setFlashMessage('پست شما با موفقیت برای تاریخ شمسی تعیین شده زمان‌بندی گردید. ⏰');
        }

        $this->redirect('/dashboard');
    }

    /**
     * لغو/حذف پست زمان‌بندی‌شده یا در صف ارسال
     */
    public function handleCancelPost() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'خطای امنیتی! توکن نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $tenant_id = Auth::tenantId();
        $post_id = (int)($_POST['post_id'] ?? 0);

        if ($post_id <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'شناسه پست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = Bootstrap::getDB();

        // بررسی وجود پست و تعلق آن به این مستأجر و وضعیت قابل لغو
        $stmt = $db->prepare("SELECT id, status, title FROM posts WHERE id = ? AND tenant_id = ? AND status IN ('scheduled', 'queued', 'draft')");
        $stmt->execute([$post_id, $tenant_id]);
        $post = $stmt->fetch();

        if (!$post) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'پست یافت نشد یا قبلاً ارسال شده و قابل لغو نیست.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db->prepare("DELETE FROM posts WHERE id = ? AND tenant_id = ?")->execute([$post_id, $tenant_id]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'پست «' . $post['title'] . '» با موفقیت لغو و حذف شد.'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * پردازش صف پست‌های در انتظار ارسال (AJAX)
     *
     * این متد از طریق JavaScript در داشبورد فراخوانی می‌شود
     * و در هر بار فقط یک پست را پردازش می‌کند تا از تایم‌اوت جلوگیری شود.
     */
    public function processPostQueue() {
        header('Content-Type: application/json; charset=utf-8');

        try {
            $this->checkAuth();
            $tenant_id = Auth::tenantId();

            $db = Bootstrap::getDB();
            set_time_limit(30);

            // دریافت اولین پست در صف ارسال این مستأجر
            $stmt = $db->prepare("SELECT id, title, content, media_url, target_channels, tenant_id FROM posts WHERE tenant_id = ? AND status = 'queued' ORDER BY id ASC LIMIT 1");
            $stmt->execute([$tenant_id]);
            $post = $stmt->fetch();

            if (!$post) {
                echo json_encode(['success' => true, 'message' => 'no_queued_posts'], JSON_UNESCAPED_UNICODE);
                return;
            }

            $post_id = (int)$post['id'];
            $channel_ids = json_decode($post['target_channels'], true) ?: [];

            if (empty($channel_ids)) {
                $db->prepare("UPDATE posts SET status = 'failed' WHERE id = ?")->execute([$post_id]);
                echo json_encode(['success' => false, 'message' => 'کانال هدف یافت نشد'], JSON_UNESCAPED_UNICODE);
                return;
            }

            // تغییر وضعیت به sending
            $db->prepare("UPDATE posts SET status = 'sending' WHERE id = ?")->execute([$post_id]);

            $res = Sender::sendPostToChannels(
                (int)$post['tenant_id'],
                $channel_ids,
                $post['title'],
                $post['content'],
                $post['media_url'] ?? '',
                $post_id
            );

            if ($res['success']) {
                $db->prepare("UPDATE posts SET status = 'sent' WHERE id = ?")->execute([$post_id]);
                echo json_encode(['success' => true, 'post_id' => $post_id, 'message' => 'پست با موفقیت ارسال شد'], JSON_UNESCAPED_UNICODE);
            } else {
                $db->prepare("UPDATE posts SET status = 'failed' WHERE id = ?")->execute([$post_id]);
                $errors = [];
                foreach ($res['channels'] ?? [] as $ch) {
                    if (!$ch['success']) {
                        $errors[] = $ch['name'] . ': ' . ($ch['message'] ?? '');
                    }
                }
                error_log('[Postyar] Queue send failed for post #' . $post_id . ': ' . implode(' | ', $errors));
                echo json_encode(['success' => false, 'post_id' => $post_id, 'message' => 'خطا در ارسال به برخی کانال‌ها', 'errors' => $errors], JSON_UNESCAPED_UNICODE);
            }
        } catch (\Throwable $e) {
            error_log('[Postyar] Queue process error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'خطای سیستمی'], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * قلب تپنده — پردازش پست‌های زمان‌بندی‌شده (فقط دیتابیس، بدون HTTP)
     * ⚠️ Polling پیام‌ها و پاسخگوی خودکار فقط از طریق cron.php انجام می‌شود
     */
    public function handleHeartbeat() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkAuth();

        $sent = 0;

        try {
            set_time_limit(10);

            // فقط پردازش پست‌های زمان‌بندی‌شده (بدون HTTP — فقط دیتابیس)
            // ⚠️ Polling پیام‌ها و پاسخگوی خودکار فقط از طریق cron.php انجام می‌شود
            $sent = ScheduledPost::processAll();

        } catch (\Throwable $e) {
            error_log('[Postyar Heartbeat] Error: ' . $e->getMessage());
        }

        echo json_encode(['success' => true, 'sent' => $sent], JSON_UNESCAPED_UNICODE);
    }

    public function handleEditPlan(){ return (new \WHCM\Modules\Billing\Controllers\PlanController)->edit(); }
    public function handleDeletePlan(){ return (new \WHCM\Modules\Billing\Controllers\PlanController)->delete(); }
    public function handleCreateTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->create(); }
    public function handleReplyTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->reply(); }
    public function handleAdminCreateTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->adminCreate(); }
    public function handleReopenTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->reopenAdmin(); }
    public function handleDeleteTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->deleteAdmin(); }

    public function handleCloseTicketUser(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->closeUser(); }

    public function handleUserReplyTicket(){ return (new \WHCM\Modules\Support\Controllers\TicketController)->userReply(); }
    public function handleAssignTicket(){ 
        $this->checkSuperAdmin();
        $tid=(int)($_POST['ticket_id'] ?? 0);
        $aid=(int)($_POST['assigned_to'] ?? 0);
        if($tid>0){
            $db=\WHCM\Core\Bootstrap::getDB();
            $db->prepare("UPDATE tickets SET assigned_to=? WHERE id=?")->execute([$aid?:null,$tid]);
            $this->setFlashMessage('تیکت با موفقیت ارجاع داده شد. ✔');
        }
        $this->redirect('/hnnh');
    }


    public function handleResetPassword() {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/');
        }

        $email = trim($_POST['email'] ?? '');
        if (empty($email)) {
            $this->setFlashMessage('نشانی ایمیل را وارد کنید.');
            $this->redirect('/');
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user_id = $stmt->fetchColumn();

        if (!$user_id) {
            // برای جلوگیری از افشای وجود ایمیل، پیام یکسان برمی‌گردانیم
            $this->setFlashMessage('در صورت وجود حساب، دستورالعمل بازنشانی ارسال شد.');
            $this->redirect('/');
            return;
        }

        // تولید توکن یکبار مصرف با اعتبار ۱ ساعت
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);
        
        // ذخیره توکن در تنظیمات کاربر
        $stmt = $db->prepare("SELECT id FROM settings WHERE tenant_id = ? AND key_name = 'password_reset_token' LIMIT 1");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("UPDATE settings SET key_value = ? WHERE tenant_id = ? AND key_name = 'password_reset_token'");
            $stmt->execute([$token . '|' . $expires, $user_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (?, 'password_reset_token', ?)");
            $stmt->execute([$user_id, $token . '|' . $expires]);
        }

        // ارسال ایمیل با لینک بازنشانی رمز عبور
        $reset_link = Bootstrap::getConfig('app.url') . '/index.php?route=/reset-password&token=' . $token;

        // دریافت نام کاربر برای قالب ایمیل
        $stmt = $db->prepare("SELECT name FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$user_id]);
        $user_name = $stmt->fetchColumn() ?: 'کاربر';

        // ارسال ایمیل (اگر SMTP تنظیم نشده باشد، حداقل لاگ می‌شود)
        $html_body = \WHCM\Core\Mail::buildPasswordResetTemplate($user_name, $reset_link);
        $sent = \WHCM\Core\Mail::send($email, 'بازنشانی رمز عبور — پُست‌یار', $html_body);

        if (!$sent) {
            error_log('[Postyar] Failed to send password reset email to: ' . $email);
        }

        $this->setFlashMessage('در صورت وجود حساب، دستورالعمل بازنشانی به ایمیل شما ارسال شد.');
        $this->redirect('/');
    }

    /**
     * صفحه و فرم بازنشانی رمز عبور با توکن
     */
    public function showResetPasswordForm() {
        $token = trim($_GET['token'] ?? '');
        if (empty($token)) {
            $this->setFlashMessage('لینک بازنشانی نامعتبر است.');
            $this->redirect('/');
        }

        $this->render('home', [
            'title' => 'بازنشانی رمز عبور | پُست‌یار',
            'csrf_field' => Csrf::field(),
            'message' => $this->getFlashMessage(),
            'reset_token' => $token,
            'show_reset_form' => true,
        ]);
    }

    /**
     * اعمال بازنشانی رمز عبور
     */
    public function handleResetPasswordConfirm() {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/');
        }

        $token = trim($_POST['token'] ?? '');
        $new_pass = $_POST['new_password'] ?? '';
        $confirm_pass = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($new_pass)) {
            $this->setFlashMessage('تمامی فیلدها الزامی هستند.');
            $this->redirect('/');
        }

        if ($new_pass !== $confirm_pass) {
            $this->setFlashMessage('کلمه عبور جدید با تکرار آن مطابقت ندارد.');
            $this->redirect('/');
        }

        if (strlen($new_pass) < 6) {
            $this->setFlashMessage('کلمه عبور باید حداقل ۶ کاراکتر باشد.');
            $this->redirect('/');
        }

        $db = Bootstrap::getDB();

        // جستجوی توکن معتبر
        $stmt = $db->prepare("SELECT tenant_id, key_value FROM settings WHERE key_name = 'password_reset_token' LIMIT 1");
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $parts = explode('|', $row['key_value'], 2);
            if (isset($parts[0]) && hash_equals($parts[0], $token)) {
                // بررسی انقضای توکن
                $expires = $parts[1] ?? '';
                if (!empty($expires) && strtotime($expires) < time()) {
                    $this->setFlashMessage('لینک بازنشانی منقضی شده است. لطفاً دوباره درخواست دهید.');
                    $this->redirect('/');
                }

                // تغییر رمز عبور
                $hashed = password_hash($new_pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $user_id = (int)$row['tenant_id'];
                $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);

                // حذف توکن استفاده شده
                $stmt = $db->prepare("DELETE FROM settings WHERE tenant_id = ? AND key_name = 'password_reset_token'");
                $stmt->execute([$user_id]);

                $this->setFlashMessage('کلمه عبور شما با موفقیت تغییر یافت. اکنون وارد شوید.');
                $this->redirect('/');
                return;
            }
        }

        $this->setFlashMessage('لینک بازنشانی نامعتبر یا منقضی شده است.');
        $this->redirect('/');
    }

    public function handleSuspendUser(){ return (new \WHCM\Modules\Users\Controllers\UserController)->suspend(); }
    public function handleActivateUser(){ return (new \WHCM\Modules\Users\Controllers\UserController)->activate(); }
    public function handleDeleteUser(){ return (new \WHCM\Modules\Users\Controllers\UserController)->delete(); }
    public function handleBroadcastAnnouncement(){ return (new \WHCM\Modules\Support\Controllers\BroadcastController)->announce(); }
    public function handleWipeTestData(){ return (new \WHCM\Modules\Users\Controllers\UserController)->wipeTestData(); }

    // === هندلرهای GET برای لینک‌های سریع ادمین ===
    public function handleSuspendUserGet(){
        $this->checkSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("UPDATE users SET status = 'suspended' WHERE id = ? AND role != 'superadmin'");
        $stmt->execute([$id]);
        $this->setFlashMessage('حساب کاربری مستأجر با موفقیت معلق و مسدود گردید. 🚫');
        $this->redirect('/hnnh');
    }
    public function handleActivateUserGet(){
        $this->checkSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        $this->setFlashMessage('حساب کاربری مستأجر با موفقیت مجدداً فعال شد. ✔');
        $this->redirect('/hnnh');
    }
    public function handleDeleteUserGet(){
        $this->checkSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role != 'superadmin'");
        $stmt->execute([$id]);
        $this->setFlashMessage('حساب کاربری مستأجر با موفقیت به طور کامل حذف گردید.');
        $this->redirect('/hnnh');
    }
    public function handleApprovePaymentGet(){
        $this->checkSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$id]);
        $payment = $stmt->fetch();
        if (!$payment) { $this->setFlashMessage('تراکنش مورد نظر یافت نشد یا قبلاً پردازش شده است.'); $this->redirect('/hnnh'); return; }
        $user_id = (int)$payment['user_id'];
        $plan_id = (int)$payment['plan_id'];
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        if (!$plan) { $this->setFlashMessage('پلن مربوطه یافت نشد.'); $this->redirect('/hnnh'); return; }
        $db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE payments SET status = 'approved', verified_at = ? WHERE id = ?");
            $stmt->execute([$now, $id]);
            $stmt = $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $duration = (int)$plan['duration_days'];
            $end_date = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : '2099-12-30 00:00:00';
            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$user_id, $plan_id, $now, $end_date]);
            $db->commit();
            $this->setFlashMessage('پرداخت با موفقیت تایید و اشتراک کاربر بلافاصله فعال گردید. ✔');
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->setFlashMessage('بروز خطا در پردازش تایید تراکنش: ' . $e->getMessage());
        }
        $this->redirect('/hnnh');
    }
    public function handleDeletePlanGet(){
        $this->checkSuperAdmin();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM plans WHERE id = ?");
        $stmt->execute([$id]);
        $this->setFlashMessage('پلن اشتراک با موفقیت حذف گردید.');
        $this->redirect('/hnnh');
    }

    // === تنظیمات سراسری ادمین ===
    public function handleSaveGoldSettingsAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $fields = ['gold_api_source', 'gold_interval', 'gold_default_template'];
        foreach ($fields as $f) {
            $val = $_POST[$f] ?? '';
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, ?, ?) ON CONFLICT(tenant_id, key_name) DO UPDATE SET key_value = ?")->execute([$f, $val, $val]);
        }
        // ذخیره آدرس API دستی طلا (کلید اختصاصی مدیر)
        $custom_url = trim($_POST['gold_custom_api_url'] ?? '');
        if (!empty($custom_url)) {
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, 'gold_custom_api_url', ?) ON CONFLICT(tenant_id, key_name) DO UPDATE SET key_value = ?")->execute([$custom_url, $custom_url]);
        }
        $this->setFlashMessage('تنظیمات ربات طلا و سکه با موفقیت ذخیره شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleSaveAiSettingsAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $fields = ['ai_global_provider', 'ai_global_model', 'ai_global_key', 'ai_global_url', 'ai_active_by_default'];
        foreach ($fields as $f) {
            $val = $_POST[$f] ?? '';
            if ($f === 'ai_active_by_default') { $val = isset($_POST[$f]) ? '1' : '0'; }
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, ?, ?) ON CONFLICT(tenant_id, key_name) DO UPDATE SET key_value = ?")->execute([$f, $val, $val]);
        }
        $this->setFlashMessage('تنظیمات سراسری هوش مصنوعی با موفقیت ذخیره شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleDeleteDiscount(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $id = (int)($_POST['discount_id'] ?? 0);
        $db = Bootstrap::getDB();
        $db->prepare("DELETE FROM discount_codes WHERE id = ?")->execute([$id]);
        $this->setFlashMessage('کد تخفیف با موفقیت حذف شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleAddDiscount(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $percentage = (int)($_POST['percentage'] ?? 0);
        $max_uses = (int)($_POST['max_uses'] ?? 0);
        $expires_at = trim($_POST['expires_at'] ?? '');
        if (empty($code) || $percentage <= 0 || $percentage > 100) {
            $this->setFlashMessage('لطفاً کد تخفیف (معتبر) و درصد (۱ تا ۱۰۰) را وارد کنید.'); $this->redirect('/hnnh'); return;
        }
        $db = Bootstrap::getDB();
        try {
            $stmt = $db->prepare("INSERT INTO discount_codes (code, type, amount, max_uses, expires_at, active) VALUES (?, 'percent', ?, ?, ?, 1)");
            $stmt->execute([$code, $percentage, $max_uses > 0 ? $max_uses : 0, $expires_at ?: null]);
            $this->setFlashMessage('کد تخفیف جدید با موفقیت ایجاد شد! ✔');
        } catch (\Throwable $e) {
            $this->setFlashMessage('خطا در ایجاد کد تخفیف: احتمالاً کد تکراری است.');
        }
        $this->redirect('/hnnh');
    }
    public function handleSaveResponderSettingsAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $fields = ['responder_max_keywords', 'responder_delay', 'responder_fallback'];
        foreach ($fields as $f) {
            $val = $_POST[$f] ?? '';
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, ?, ?) ON CONFLICT(tenant_id, key_name) DO UPDATE SET key_value = ?")->execute([$f, $val, $val]);
        }
        $this->setFlashMessage('تنظیمات پاسخگوی هوشمند با موفقیت ذخیره شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleSaveWooSettingsAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $db = Bootstrap::getDB();
        $fields = ['woo_help_text', 'woo_max_stores', 'woo_require_ssl'];
        foreach ($fields as $f) {
            $val = $_POST[$f] ?? '';
            if ($f === 'woo_require_ssl') { $val = isset($_POST[$f]) ? '1' : '0'; }
            $db->prepare("INSERT INTO settings (tenant_id, key_name, key_value) VALUES (0, ?, ?) ON CONFLICT(tenant_id, key_name) DO UPDATE SET key_value = ?")->execute([$f, $val, $val]);
        }
        $this->setFlashMessage('تنظیمات ووکامرس با موفقیت ذخیره شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleReopenTicketAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $id = (int)($_POST['ticket_id'] ?? 0);
        $db = Bootstrap::getDB();
        $db->prepare("UPDATE tickets SET status = 'open' WHERE id = ?")->execute([$id]);
        $this->setFlashMessage('تیکت با موفقیت مجدداً باز شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleDeleteTicketAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $id = (int)($_POST['ticket_id'] ?? 0);
        $db = Bootstrap::getDB();
        $db->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM tickets WHERE id = ?")->execute([$id]);
        $this->setFlashMessage('تیکت با موفقیت حذف شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleCloseTicketAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $id = (int)($_POST['ticket_id'] ?? 0);
        $db = Bootstrap::getDB();
        $db->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?")->execute([$id]);
        $this->setFlashMessage('تیکت با موفقیت بسته شد! ✔');
        $this->redirect('/hnnh');
    }
    public function handleCreateTicketAdmin(){
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/hnnh'); return; }
        $user_id = (int)($_POST['target_user_id'] ?? 0);
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? 'general');
        $priority = trim($_POST['priority'] ?? 'normal');
        $message = trim($_POST['message'] ?? '');
        if ($user_id <= 0 || empty($subject) || empty($message)) {
            $this->setFlashMessage('لطفاً کاربر، موضوع و پیام را وارد کنید.'); $this->redirect('/hnnh'); return;
        }
        $db = Bootstrap::getDB();
        try {
            $stmt = $db->prepare("INSERT INTO tickets (user_id, subject, category, message, status, priority, created_by_admin) VALUES (?, ?, ?, ?, 'replied', ?, 1)");
            $stmt->execute([$user_id, $subject, $category, $message, $priority]);
            $this->setFlashMessage('تیکت پشتیبانی با موفقیت برای کاربر ایجاد شد! ✔');
        } catch (\Throwable $e) {
            $this->setFlashMessage('خطا در ایجاد تیکت: ' . $e->getMessage());
        }
        $this->redirect('/hnnh');
    }

    /**
     * ذخیره تنظیمات شماره کارت بانکی عمومی توسط سوپر ادمین
     */
    public function handleSaveBankSettings() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $card_number = trim($_POST['card_number'] ?? '');
        $card_holder = trim($_POST['card_holder'] ?? '');
        $bank_name = trim($_POST['bank_name'] ?? '');
        
        $support_tele = trim($_POST['support_telegram_url'] ?? '');
        $support_bale = trim($_POST['support_bale_url'] ?? '');
        $support_email = trim($_POST['support_email'] ?? '');

        if (empty($card_number) || empty($card_holder)) {
            $this->setFlashMessage('شماره کارت و نام صاحب حساب الزامی هستند.');
            $this->redirect('/hnnh');
        }

        $bank_settings = [
            'admin_card_number' => $card_number,
            'admin_card_holder' => $card_holder,
            'admin_bank_name' => $bank_name,
            'support_telegram_url' => $support_tele,
            'support_bale_url' => $support_bale,
            'support_email' => $support_email
        ];

        $this->saveSettingsBatch(0, $bank_settings);

        $this->setFlashMessage('تنظیمات کارت بانکی و راه‌های ارتباطی با موفقیت بروزرسانی شد! 💳✔');
        $this->redirect('/hnnh');
    }

    public function handleAddUserManual(){ return (new \WHCM\Modules\Users\Controllers\UserController)->addManual(); }
    public function handleGrantSubscriptionManual(){ return (new \WHCM\Modules\Users\Controllers\UserController)->grantSubscription(); }
    /**
     * ذخیره‌سازی تنظیمات اتوماسیون پیشرفته توسط کاربر (مستأجر)
     */
    public function handleSaveAdvancedSettings() {
        $this->checkAuth();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            if ($this->isAjax()) { echo json_encode(['success'=>false,'message'=>'خطای امنیتی'],JSON_UNESCAPED_UNICODE); return; }
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $tenant_id = Auth::tenantId();

        // ذخیره‌سازی فیلدهای دریافتی در جدول تنظیمات اختصاصی مستأجر
        $fields = [
            'ai_provider' => trim($_POST['ai_provider'] ?? ''),
            'ai_api_key' => trim($_POST['ai_api_key'] ?? ''),
            'ai_model' => trim($_POST['ai_model'] ?? 'gpt-4o'),
            'ai_api_url' => trim($_POST['ai_api_url'] ?? 'https://api.openai.com/v1/chat/completions'),
            'auto_publish_woo' => isset($_POST['auto_publish_woo']) ? 'yes' : 'no',
            'watermark_active' => isset($_POST['watermark_active']) ? 'yes' : 'no',
            'caption_format' => trim($_POST['caption_format'] ?? 'plain'),
            'inbound_method' => trim($_POST['inbound_method'] ?? 'polling'),
            'poll_interval' => trim($_POST['poll_interval'] ?? 'every_1_minute'),
            
            'link_1_name' => trim($_POST['link_1_name'] ?? '📢 کانال تلگرام'),
            'link_1_url' => trim($_POST['link_1_url'] ?? ''),
            'link_2_name' => trim($_POST['link_2_name'] ?? '💬 کانال بله'),
            'link_2_url' => trim($_POST['link_2_url'] ?? ''),
            'link_3_name' => trim($_POST['link_3_name'] ?? '🌐 خرید آنلاین از سایت'),
            'link_3_url' => trim($_POST['link_3_url'] ?? ''),
            
            'btn_1_text' => trim($_POST['btn_1_text'] ?? '🛒 خرید آنلاین از سایت'),
            'btn_2_text' => trim($_POST['btn_2_text'] ?? '💎 پشتیبانی VIP'),
            'btn_2_url' => trim($_POST['btn_2_url'] ?? ''),
            'btn_3_text' => trim($_POST['btn_3_text'] ?? '📢 هومن وب'),
            'btn_3_url' => trim($_POST['btn_3_url'] ?? '')
        ];

        $this->saveSettingsBatch($tenant_id, $fields);

        if ($this->isAjax()) {
            echo json_encode(['success'=>true,'message'=>'تنظیمات با موفقیت ذخیره شد! ✔🤖'],JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->setFlashMessage('تنظیمات اتوماسیون و پیوند‌های اختصاصی با موفقیت بروزرسانی شد! ✔🤖');
        $this->redirect('/dashboard');
    }

    // =============================================================
    // بخش سیستم زیرمجموعه‌گیری (Referral System)
    // =============================================================

    /**
     * بخش زیرمجموعه‌گیری در داشبورد (بخش جزئی — AJAX)
     */
    public function referralSection() {
        $this->checkAuth();
        $userId = Auth::tenantId();

        $referralCode = Referral::getUserReferralCode($userId);
        $referralLink = Referral::getReferralLink($userId);
        $stats = Referral::getReferralStats($userId);
        $history = Referral::getReferralHistory($userId);
        $points = $this->getUserPoints($userId);
        $settings = Referral::getAdminSettings();
        $enabled = ($settings['enabled'] ?? '0') === '1';

        include __DIR__ . '/../Views/partials/referral-section.php';
        exit;
    }

    // =============================================================
    // بخش کیف پول (Wallet System)
    // =============================================================

    /**
     * بخش کیف پول در داشبورد (بخش جزئی — AJAX)
     */
    public function walletSection() {
        $this->checkAuth();
        $userId = Auth::tenantId();

        $balance = Wallet::getBalance($userId);
        $transactions = Wallet::getTransactions($userId, 50, 0);
        $points = $this->getUserPoints($userId);

        include __DIR__ . '/../Views/partials/wallet-section.php';
        exit;
    }

    /**
     * تبدیل امتیاز به موجودی کیف پول
     */
    public function handleConvertPoints() {
        $this->checkAuth();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/dashboard');
        }

        $userId = Auth::tenantId();
        $points = (float)TextFormat::en_num($_POST['points'] ?? '0');
        $rate = 10; // نرخ تبدیل: هر امتیاز = ۱۰ تومان

        if ($points <= 0) {
            $this->setFlashMessage('مقدار امتیاز وارد شده نامعتبر است.');
            $this->redirect('/dashboard');
        }

        if (Wallet::convertPointsToWallet($userId, $points, $rate)) {
            $this->setFlashMessage(TextFormat::fa_num($points) . ' امتیاز با موفقیت به ' . TextFormat::fa_num($points * $rate) . ' تومان در کیف پول شما تبدیل شد! 💰');
        } else {
            $this->setFlashMessage('خطا در تبدیل امتیاز. لطفاً موجودی امتیاز خود را بررسی کنید.');
        }

        $this->redirect('/dashboard');
    }

    // =============================================================
    // بخش مدیریت ادمین — تنظیمات زیرمجموعه‌گیری
    // =============================================================

    /**
     * صفحه تنظیمات زیرمجموعه‌گیری ادمین (بخش جزئی — AJAX)
     */
    public function adminReferralSettings() {
        $this->checkSuperAdmin();
        $settings = Referral::getAdminSettings();
        include __DIR__ . '/../Views/partials/admin-referral-settings.php';
        exit;
    }

    /**
     * ذخیره تنظیمات زیرمجموعه‌گیری (POST)
     */
    public function handleSaveReferralSettings() {
        $this->checkSuperAdmin();

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $settings = [
            'enabled'                  => isset($_POST['enabled']) ? '1' : '0',
            'register_reward_type'     => trim($_POST['register_reward_type'] ?? 'points'),
            'register_reward_value'    => trim($_POST['register_reward_value'] ?? '100'),
            'first_purchase_reward_type'  => trim($_POST['first_purchase_reward_type'] ?? 'percent'),
            'first_purchase_reward_value' => trim($_POST['first_purchase_reward_value'] ?? '10'),
            'max_referrals_per_user'   => trim($_POST['max_referrals_per_user'] ?? '100'),
            'monthly_reward_cap'       => trim($_POST['monthly_reward_cap'] ?? '500000'),
        ];

        Referral::saveAdminSettings($settings);
        $this->setFlashMessage('تنظیمات سیستم زیرمجموعه‌گیری با موفقیت ذخیره شد! 🎯');
        $this->redirect('/hnnh');
    }

    /**
     * آمار کیف پول‌ها برای ادمین (JSON)
     */
    public function adminWalletStats() {
        $this->checkSuperAdmin();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(Wallet::getAdminWalletStats(), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =============================================================
    // بخش مدیریت پیامک (SMS.ir)
    // =============================================================

    /**
     * نمایش صفحه تنظیمات پیامک ادمین
     */
    public function adminSmsSettings() {
        $this->checkSuperAdmin();
        $db = Bootstrap::getDB();

        // دریافت تنظیمات ذخیره شده
        $sms_settings = [];
        $keys = ['sms_enabled', 'sms_api_key', 'sms_line_number'];
        foreach ($keys as $key) {
            $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = ? LIMIT 1");
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            $sms_settings[$key] = $row !== false ? $row['key_value'] : '';
        }

        // دریافت قالب‌ها
        $templates = $db->query("SELECT * FROM sms_templates ORDER BY id ASC")->fetchAll();

        // دریافت لاگ‌ها (۵۰ مورد آخر)
        $filter_status = $_GET['filter_status'] ?? '';
        $filter_phone = trim($_GET['filter_phone'] ?? '');

        $sql = "SELECT sl.*, st.template_name, st.event_key FROM sms_log sl LEFT JOIN sms_templates st ON sl.template_id = st.template_id WHERE 1=1";
        $params = [];

        if (!empty($filter_status) && in_array($filter_status, ['success', 'failed', 'rate_limited', 'pending'], true)) {
            $sql .= " AND sl.status = ?";
            $params[] = $filter_status;
        }
        if (!empty($filter_phone)) {
            $sql .= " AND sl.phone LIKE ?";
            $params[] = '%' . $filter_phone . '%';
        }

        $sql .= " ORDER BY sl.id DESC LIMIT 50";

        if (!empty($params)) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $logs = $stmt->fetchAll();
        } else {
            $logs = $db->query($sql)->fetchAll();
        }

        // دریافت کاربران برای ارسال انبوه
        $active_users = $db->query("SELECT id, name, phone FROM users WHERE status = 'active' AND role != 'superadmin' ORDER BY id DESC")->fetchAll();

        include __DIR__ . '/../Views/partials/admin-sms-settings.php';
        exit;
    }

    /**
     * ذخیره تنظیمات پیامک (API Key و شماره خط)
     */
    public function handleSaveSmsConfig() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $enabled = isset($_POST['sms_enabled']) ? '1' : '0';
        $apiKey = trim($_POST['sms_api_key'] ?? '');
        $lineNumber = trim($_POST['sms_line_number'] ?? '');

        $this->saveSettingsBatch(0, [
            'sms_enabled'    => $enabled,
            'sms_api_key'    => $apiKey,
            'sms_line_number' => $lineNumber,
        ]);

        $this->setFlashMessage('تنظیمات پیامک با موفقیت ذخیره شد! 📱✔');
        $this->redirect('/hnnh');
    }

    /**
     * ذخیره قالب پیامک (ایجاد یا به‌روزرسانی)
     */
    public function handleSaveSmsTemplate() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $id = (int)($_POST['template_db_id'] ?? 0);
        $eventKey = trim($_POST['event_key'] ?? '');
        $templateName = trim($_POST['template_name'] ?? '');
        $templateId = (int)($_POST['template_id'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $parameters = trim($_POST['parameters'] ?? '[]');

        // اعتبارسنجی JSON پارامترها
        json_decode($parameters);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->setFlashMessage('فرمت پارامترها نامعتبر است. لطفاً JSON معتبر وارد کنید.');
            $this->redirect('/hnnh');
        }

        if (empty($eventKey) || empty($templateName) || $templateId <= 0) {
            $this->setFlashMessage('فیلدهای کلید رویداد، نام قالب و شناسه قالب الزامی هستند.');
            $this->redirect('/hnnh');
        }

        $db = Bootstrap::getDB();

        if ($id > 0) {
            // به‌روزرسانی
            $stmt = $db->prepare("UPDATE sms_templates SET event_key = ?, template_name = ?, template_id = ?, parameters = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$eventKey, $templateName, $templateId, $parameters, $isActive, $id]);
            $this->setFlashMessage('قالب پیامک با موفقیت بروزرسانی شد! ✏️✔');
        } else {
            // ایجاد جدید
            try {
                $stmt = $db->prepare("INSERT INTO sms_templates (event_key, template_name, template_id, parameters, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$eventKey, $templateName, $templateId, $parameters, $isActive]);
                $this->setFlashMessage('قالب پیامک جدید با موفقیت ایجاد شد! 📱✔');
            } catch (\Exception $e) {
                $this->setFlashMessage('خطا در ایجاد قالب. ممکن است کلید رویداد تکراری باشد.');
            }
        }

        $this->redirect('/hnnh');
    }

    /**
     * حذف قالب پیامک
     */
    public function handleDeleteSmsTemplate() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $id = (int)($_POST['template_db_id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('شناسه قالب نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM sms_templates WHERE id = ?");
        $stmt->execute([$id]);

        $this->setFlashMessage('قالب پیامک حذف شد! 🗑️');
        $this->redirect('/hnnh');
    }

    /**
     * ارسال پیامک تستی
     */
    public function handleTestSms() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $phone = trim($_POST['test_phone'] ?? '');
        if (empty($phone)) {
            $this->setFlashMessage('شماره موبایل برای تست را وارد کنید.');
            $this->redirect('/hnnh');
        }

        $result = Sms::testConnection($phone);
        if ($result['success']) {
            $this->setFlashMessage($result['message']);
        } else {
            $this->setFlashMessage('❌ ' . $result['message']);
        }

        $this->redirect('/hnnh');
    }

    /**
     * ارسال پیامک انبوه
     */
    public function handleSendBulkSms() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $recipientType = trim($_POST['recipient_type'] ?? '');
        $templateId = (int)($_POST['bulk_template_id'] ?? 0);
        $manualPhones = trim($_POST['manual_phones'] ?? '');
        $param1Name = trim($_POST['param1_name'] ?? '');
        $param1Value = trim($_POST['param1_value'] ?? '');
        $param2Name = trim($_POST['param2_name'] ?? '');
        $param2Value = trim($_POST['param2_value'] ?? '');

        if ($templateId <= 0) {
            $this->setFlashMessage('لطفاً یک قالب پیامک انتخاب کنید.');
            $this->redirect('/hnnh');
        }

        // آماده‌سازی پارامترها
        $params = [];
        if (!empty($param1Name)) {
            $params[$param1Name] = $param1Value;
        }
        if (!empty($param2Name)) {
            $params[$param2Name] = $param2Value;
        }

        // جمع‌آوری شماره‌ها
        $phones = [];
        $db = Bootstrap::getDB();

        if ($recipientType === 'all') {
            $rows = $db->query("SELECT phone FROM users WHERE phone IS NOT NULL AND phone != '' AND role != 'superadmin'")->fetchAll();
            foreach ($rows as $row) {
                $phones[] = $row['phone'];
            }
        } elseif ($recipientType === 'active') {
            $rows = $db->query("SELECT phone FROM users WHERE phone IS NOT NULL AND phone != '' AND status = 'active' AND role != 'superadmin'")->fetchAll();
            foreach ($rows as $row) {
                $phones[] = $row['phone'];
            }
        } elseif ($recipientType === 'manual') {
            // شکستن شماره‌ها از خطوط جدید
            $lines = preg_split('/[\r\n,;]+/', $manualPhones);
            foreach ($lines as $line) {
                $p = trim($line);
                if (!empty($p)) {
                    $phones[] = $p;
                }
            }
        } else {
            $this->setFlashMessage('نوع گیرندگان نامعتبر است.');
            $this->redirect('/hnnh');
        }

        if (empty($phones)) {
            $this->setFlashMessage('هیچ شماره موبایلی یافت نشد.');
            $this->redirect('/hnnh');
        }

        $result = Sms::sendBulk($phones, $templateId, $params);

        if ($result['success']) {
            $msg = '✅ پیامک انبوه ارسال شد! ارسال موفق: ' . TextFormat::fa_digits($result['sent_count']) . ' | ناموفق: ' . TextFormat::fa_digits($result['failed_count']);
            if (!empty($result['errors'])) {
                $msg .= ' | خطاها: ' . implode('، ', array_slice($result['errors'], 0, 3));
            }
            $this->setFlashMessage($msg);
        } else {
            $msg = '❌ خطا در ارسال پیامک انبوه. ';
            if (!empty($result['errors'])) {
                $msg .= implode('، ', array_slice($result['errors'], 0, 3));
            }
            $this->setFlashMessage($msg);
        }

        $this->redirect('/hnnh');
    }

    // =============================================================
    // متدهای کمکی داخلی
    // =============================================================

    /**
     * دریافت امتیازهای کاربر
     *
     * @param int $userId
     * @return float
     */
    private function getUserPoints(int $userId): float {
        $db = Bootstrap::getDB();
        try {
            $stmt = $db->prepare("SELECT referral_points FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            return (float)($row['referral_points'] ?? 0);
        } catch (\Exception $e) {
            return 0.0;
        }
    }

    /* =============================================================
     * متدهای مشترک (checkAuth، checkSuperAdmin، redirect، setFlashMessage،
     * getFlashMessage، render، uploadAndConvertToWebp، jalaliToGregorian، saveSetting)
     * در BaseController قرار دارند.
     * ============================================================= */

    // =============================================================
    // سیستم ایمیل — تنظیمات، قالب‌ها، ارسال انبوه، لاگ
    // =============================================================

    /**
     * بخش تنظیمات ایمیل در پنل مدیریت (بازگردانی پارشیال)
     */
    public function adminEmailSettings() {
        $this->checkSuperAdmin();
        $db = Bootstrap::getDB();

        // دریافت تنظیمات SMTP ذخیره شده
        $smtp_keys = ['smtp_enabled', 'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name'];
        $email_settings = [];
        foreach ($smtp_keys as $ek) {
            $stmt = $db->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = ? LIMIT 1");
            $stmt->execute([$ek]);
            $erow = $stmt->fetch();
            $email_settings[$ek] = $erow !== false ? $erow['key_value'] : '';
        }

        // دریافت قالب‌ها
        $templates = EmailTemplate::getAllTemplates();

        // دریافت لاگ‌ها
        $filter_status = $_GET['filter_status'] ?? '';
        $logs = EmailTemplate::getLog(50, 0, !empty($filter_status) ? $filter_status : null);

        // آمار
        $email_stats = EmailTemplate::getAdminEmailStats();

        // دریافت کاربران برای ارسال انبوه
        $active_users = $db->query("SELECT id, name, email FROM users WHERE status = 'active' AND role != 'superadmin' ORDER BY id DESC")->fetchAll();
        $all_users = $db->query("SELECT id, name, email FROM users WHERE role != 'superadmin' ORDER BY id DESC")->fetchAll();

        include __DIR__ . '/../Views/partials/admin-email-settings.php';
        exit;
    }

    /**
     * ذخیره تنظیمات SMTP
     */
    public function handleSaveEmailConfig() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $enabled = isset($_POST['smtp_enabled']) ? '1' : '0';
        $host = trim($_POST['smtp_host'] ?? '');
        $port = trim($_POST['smtp_port'] ?? '587');
        $username = trim($_POST['smtp_username'] ?? '');
        $password = trim($_POST['smtp_password'] ?? '');
        $encryption = trim($_POST['smtp_encryption'] ?? 'tls');
        $fromAddress = trim($_POST['smtp_from_address'] ?? '');
        $fromName = trim($_POST['smtp_from_name'] ?? '');

        // اگر رمز عبور خالی بود و قبلاً ذخیره شده، نگه‌داشتن رمز قبلی
        if (empty($password)) {
            $stmt = Bootstrap::getDB()->prepare("SELECT key_value FROM settings WHERE tenant_id = 0 AND key_name = 'smtp_password' LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch();
            if ($existing) {
                $password = $existing['key_value'];
            }
        }

        $this->saveSettingsBatch(0, [
            'smtp_enabled'      => $enabled,
            'smtp_host'         => $host,
            'smtp_port'         => $port,
            'smtp_username'     => $username,
            'smtp_password'     => $password,
            'smtp_encryption'   => $encryption,
            'smtp_from_address' => $fromAddress,
            'smtp_from_name'    => $fromName,
        ]);

        $this->setFlashMessage('تنظیمات SMTP با موفقیت ذخیره شد! 📧✔');
        $this->redirect('/hnnh');
    }

    /**
     * ذخیره قالب ایمیل (ایجاد یا به‌روزرسانی)
     */
    public function handleSaveEmailTemplate() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $eventKey = trim($_POST['event_key'] ?? '');
        $name = trim($_POST['template_name'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $bodyHtml = trim($_POST['body_html'] ?? '');
        $variablesStr = trim($_POST['variables'] ?? '[]');
        $isActive = isset($_POST['is_active']) ? true : false;

        if (empty($eventKey) || empty($name) || empty($subject)) {
            $this->setFlashMessage('فیلدهای کلید رویداد، نام قالب و موضوع الزامی هستند.');
            $this->redirect('/hnnh');
        }

        $variables = json_decode($variablesStr, true);
        if (!is_array($variables)) {
            $variables = [];
        }

        EmailTemplate::saveTemplate($eventKey, $name, $subject, $bodyHtml, $variables, $isActive);
        $this->setFlashMessage('قالب ایمیل با موفقیت ذخیره شد! ✏️📧✔');
        $this->redirect('/hnnh');
    }

    /**
     * حذف قالب ایمیل
     */
    public function handleDeleteEmailTemplate() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $id = (int)($_POST['template_db_id'] ?? 0);
        if ($id <= 0) {
            $this->setFlashMessage('شناسه قالب نامعتبر است.');
            $this->redirect('/hnnh');
        }

        EmailTemplate::deleteTemplate($id);
        $this->setFlashMessage('قالب ایمیل حذف شد! 🗑️📧');
        $this->redirect('/hnnh');
    }

    /**
     * ارسال ایمیل تستی
     */
    public function handleTestEmail() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $adminId = Auth::tenantId();
        $ok = EmailTemplate::sendByEvent('welcome', $adminId, [
            'name' => Auth::user()['name'] ?? 'مدیر سیستم',
        ]);

        if ($ok) {
            $this->setFlashMessage('ایمیل تستی با موفقیت ارسال شد! 📧✔');
        } else {
            $this->setFlashMessage('❌ خطا در ارسال ایمیل تستی. تنظیمات SMTP را بررسی کنید.');
        }
        $this->redirect('/hnnh');
    }

    /**
     * ارسال ایمیل انبوه
     */
    public function handleSendBulkEmail() {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }

        $recipientType = trim($_POST['recipient_type'] ?? '');
        $templateId = (int)($_POST['bulk_template_id'] ?? 0);

        if ($templateId <= 0) {
            $this->setFlashMessage('لطفاً یک قالب ایمیل انتخاب کنید.');
            $this->redirect('/hnnh');
        }

        $db = Bootstrap::getDB();

        // دریافت قالب
        $stmt = $db->prepare("SELECT event_key FROM email_templates WHERE id = ? LIMIT 1");
        $stmt->execute([$templateId]);
        $tpl = $stmt->fetch();
        if (!$tpl) {
            $this->setFlashMessage('قالب انتخاب‌شده یافت نشد.');
            $this->redirect('/hnnh');
        }

        $eventKey = $tpl['event_key'];
        $userIds = [];

        if ($recipientType === 'all') {
            $rows = $db->query("SELECT id FROM users WHERE role != 'superadmin'")->fetchAll();
            foreach ($rows as $r) $userIds[] = (int)$r['id'];
        } elseif ($recipientType === 'active') {
            $rows = $db->query("SELECT id FROM users WHERE status = 'active' AND role != 'superadmin'")->fetchAll();
            foreach ($rows as $r) $userIds[] = (int)$r['id'];
        } elseif ($recipientType === 'subscription') {
            $rows = $db->query("SELECT DISTINCT user_id as id FROM subscriptions WHERE status = 'active'")->fetchAll();
            foreach ($rows as $r) $userIds[] = (int)$r['id'];
        } else {
            $this->setFlashMessage('نوع گیرندگان نامعتبر است.');
            $this->redirect('/hnnh');
        }

        if (empty($userIds)) {
            $this->setFlashMessage('هیچ کاربری یافت نشد.');
            $this->redirect('/hnnh');
        }

        $result = EmailTemplate::sendBulk($eventKey, $userIds);

        $msg = '✅ ایمیل انبوه ارسال شد! موفق: ' . TextFormat::fa_digits($result['sent']) . ' | ناموفق: ' . TextFormat::fa_digits($result['failed']);
        if (!empty($result['errors'])) {
            $msg .= ' | خطاها: ' . implode('، ', array_slice($result['errors'], 0, 3));
        }
        $this->setFlashMessage($msg);
        $this->redirect('/hnnh');
    }

    /**
     * پیش‌نمایش قالب ایمیل (برگرداندن HTML رندر شده)
     */
    public function handlePreviewEmailTemplate() {
        $this->checkSuperAdmin();
        header('Content-Type: text/html; charset=utf-8');

        $bodyHtml = trim($_POST['body_html'] ?? '');
        $variablesStr = trim($_POST['variables'] ?? '[]');
        $variables = json_decode($variablesStr, true) ?: [];

        // مقداردهی نمونه برای پیش‌نمایش
        $variables['app_name'] = Bootstrap::getConfig('app.name', 'پُست‌یار');
        $variables['app_url'] = Bootstrap::getConfig('app.url', '#');
        $variables['name'] = 'نام نمونه';
        $variables['plan_name'] = 'پلن حرفه‌ای';
        $variables['amount'] = TextFormat::fa_digits('500000');
        $variables['days_left'] = TextFormat::fa_digits('3');
        $variables['ticket_subject'] = 'موضوع تیکت نمونه';
        $variables['message'] = 'این یک پیام نمونه برای پیش‌نمایش اعلان است.';
        $variables['date'] = '۱۴۰۴/۰۴/۲۶';
        $variables['reset_link'] = '#';

        echo EmailTemplate::renderTemplate($bodyHtml, $variables);
        exit;
    }

    public function handleLinkRedirect(string $code) {
        $result = LinkTracker::handleClick($code);
        if ($result) {
            header('Location: ' . $result['original_url'], true, 302);
            exit;
        }
        http_response_code(404);
        exit('لینک یافت نشد.');
    }

    public function linkStatsSection() {
        $this->checkAuth();
        $tenantId = Auth::tenantId();
        $stats = LinkTracker::getUserLinkStats($tenantId);
        $dailyClicks = LinkTracker::getDailyClicks($tenantId, 30);
        echo json_encode(['stats' => $stats, 'daily' => $dailyClicks], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function handleResetPasswordSms() {
        header('Content-Type: application/json; charset=utf-8');
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { echo json_encode(['success' => false, 'message' => 'خطای امنیتی!'], JSON_UNESCAPED_UNICODE); exit; }
        $phone = trim($_POST['phone'] ?? '');
        if (empty($phone)) { echo json_encode(['success' => false, 'message' => 'شماره موبایل را وارد کنید.'], JSON_UNESCAPED_UNICODE); exit; }
        if (!class_exists('WHCM\\Core\\Sms') || !Sms::isEnabled()) { echo json_encode(['success' => false, 'message' => 'سرویس پیامک فعال نیست.'], JSON_UNESCAPED_UNICODE); exit; }
        $db = Bootstrap::getDB();
        $stmt = $db->prepare('SELECT id FROM users WHERE phone = ? LIMIT 1');
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        if (!$user) { echo json_encode(['success' => true, 'message' => 'در صورت وجود حساب، کد تأیید ارسال شد.'], JSON_UNESCAPED_UNICODE); exit; }
        $userId = (int)$user['id'];
        $stmt = $db->prepare('SELECT template_id FROM sms_templates WHERE event_key = ? AND is_active = 1 LIMIT 1');
        $stmt->execute(['password_reset']);
        $template = $stmt->fetch();
        if (!$template) { echo json_encode(['success' => false, 'message' => 'قالب پیامک بازنشانی تنظیم نشده است.'], JSON_UNESCAPED_UNICODE); exit; }
        $code = VerificationCode::generate($userId, 'sms_reset', 5);
        $result = Sms::send($phone, (int)$template['template_id'], [['Parameter' => 'code', 'ParameterValue' => $code]], $userId);
        if (!$result['success']) { echo json_encode(['success' => false, 'message' => 'خطا در ارسال پیامک: ' . ($result['error'] ?? '')], JSON_UNESCAPED_UNICODE); exit; }
        $_SESSION['sms_reset_user_id'] = $userId;
        echo json_encode(['success' => true, 'message' => 'کد تأیید ارسال شد.'], JSON_UNESCAPED_UNICODE); exit;
    }

    public function showSmsVerifyForm() {
        $this->render('home', ['title' => 'تأیید کد پیامکی | پُست‌یار', 'csrf_field' => Csrf::field(), 'message' => $this->getFlashMessage(), 'show_sms_verify' => true]);
    }

    public function handleVerifySmsCode() {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) { $this->setFlashMessage('خطای امنیتی!'); $this->redirect('/sms-verify'); return; }
        $code = trim($_POST['code'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $userId = (int)($_SESSION['sms_reset_user_id'] ?? 0);
        if ($userId <= 0) { $this->setFlashMessage('نشست منقضی.'); $this->redirect('/'); return; }
        if (empty($code) || !VerificationCode::verify($userId, 'sms_reset', $code)) { $this->setFlashMessage('کد نامعتبر یا منقضی.'); $this->redirect('/sms-verify'); return; }
        if (empty($newPassword) || strlen($newPassword) < 6) { $this->setFlashMessage('رمز حداقل ۶ کاراکتر.'); $this->redirect('/sms-verify'); return; }
        if ($newPassword !== $confirmPassword) { $this->setFlashMessage('رمز با تکرار مطابقت ندارد.'); $this->redirect('/sms-verify'); return; }
        $db = Bootstrap::getDB();
        $db->prepare('UPDATE users SET password = ? WHERE id = ?')->execute([password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]), $userId]);
        unset($_SESSION['sms_reset_user_id']);
        $this->setFlashMessage('رمز عبور با موفقیت تغییر یافت.');
        $this->redirect('/');
    }

    /**
     * صفحه آموزش و راهنمای کاربری
     */
    public function helpPage() {
        $this->render('help', ['title' => 'آموزش استفاده از پُست‌یار']);
    }

    /**
     * سیاست حریم خصوصی عمومی وب‌سایت و اپلیکیشن
     */
    public function privacyPage() {
        // انتقال آدرس پارامتری قدیمی به نشانی خوانا و ثابت
        if (isset($_GET['route'])) {
            $canonicalUrl = rtrim(Bootstrap::getConfig('app.url', 'https://asovin.ir'), '/') . '/privacy';
            header('Location: ' . $canonicalUrl, true, 301);
            exit;
        }

        header('Cache-Control: public, max-age=3600');
        header('Link: <' . rtrim(Bootstrap::getConfig('app.url', 'https://asovin.ir'), '/') . '/privacy>; rel="canonical"');
        $this->render('privacy', ['title' => 'حریم خصوصی کاربران پُست‌یار']);
    }

    // ─── Push Notification ──────────────────────────────────────

    /**
     * بازگرداندن کلید عمومی VAPID برای ثبت اعلان در مرورگر
     */
    public function getVapidPublicKey() {
        header('Content-Type: application/json; charset=utf-8');

        $vapid = Bootstrap::getConfig('vapid');
        if (empty($vapid['public_key'])) {
            http_response_code(503);
            echo json_encode(['success' => false, 'message' => 'پوش ناتیفیکیشن فعال نیست.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        echo json_encode(['success' => true, 'publicKey' => $vapid['public_key']]);
    }

    /**
     * ثبت اشتراک Push کاربر
     */
    public function handlePushSubscribe() {
        header('Content-Type: application/json; charset=utf-8');

        Auth::requireLogin();
        $userId = Auth::id();

        $input = json_decode(file_get_contents('php://input'), true);
        $endpoint    = $input['endpoint'] ?? '';
        $keysP256dh  = $input['keys']['p256dh'] ?? '';
        $keysAuth    = $input['keys']['auth'] ?? '';

        if (!$endpoint || !$keysP256dh || !$keysAuth) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'پارامترهای اشتراک ناقص است.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = Bootstrap::getDB();

        // حذف اشتراک قبلی این کاربر (هر کاربر فقط یک اشتراک فعال)
        $db->prepare('DELETE FROM push_subscriptions WHERE user_id = ?')->execute([$userId]);

        $stmt = $db->prepare('INSERT INTO push_subscriptions (user_id, endpoint, keys_p256dh, keys_auth) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $endpoint, $keysP256dh, $keysAuth]);

        echo json_encode(['success' => true, 'message' => 'اشتراک اعلان با موفقیت ثبت شد.'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * لغو اشتراک Push کاربر
     */
    public function handlePushUnsubscribe() {
        header('Content-Type: application/json; charset=utf-8');

        Auth::requireLogin();
        $userId = Auth::id();

        $db = Bootstrap::getDB();
        $stmt = $db->prepare('DELETE FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([$userId]);

        echo json_encode(['success' => true, 'message' => 'اشتراک اعلان لغو شد.'], JSON_UNESCAPED_UNICODE);
    }

    /**
     * بررسی وضعیت اشتراک Push کاربر
     */
    public function getPushStatus() {
        header('Content-Type: application/json; charset=utf-8');

        Auth::requireLogin();
        $userId = Auth::id();

        $db = Bootstrap::getDB();
        $stmt = $db->prepare('SELECT id FROM push_subscriptions WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);

        echo json_encode([
            'success' => true,
            'subscribed' => ($stmt->fetch() !== false),
            'enabled' => !empty(Bootstrap::getConfig('vapid.enabled')),
        ]);
    }

    /**
     * ارسال اعلان به یک کاربر خاص (برای ادمین و سیستم)
     */
    public static function sendPushToUser(int $userId, string $title, string $body, string $url = ''): bool {
        $vapid = Bootstrap::getConfig('vapid');
        if (empty($vapid['enabled']) || empty($vapid['private_key_pem'])) {
            return false;
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare('SELECT endpoint, keys_p256dh, keys_auth FROM push_subscriptions WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $sub = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$sub) return false;

        $appUrl = Bootstrap::getConfig('app.url');
        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url ?: $appUrl . '/dashboard',
        ], JSON_UNESCAPED_UNICODE);

        try {
            $result = WebPush::send(
                $sub['endpoint'],
                $sub['keys_p256dh'],
                $sub['keys_auth'],
                $payload,
                [
                    'subject'    => $vapid['subject'],
                    'publicKey'  => $vapid['public_key'],
                    'privateKey' => $vapid['private_key_pem'],
                ]
            );

            // اگر اشتراک منقضی شده، حذف شود
            if (!$result['success'] && in_array($result['status'], [404, 410])) {
                $db->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?')->execute([$sub['endpoint']]);
            }

            return $result['success'];
        } catch (\Throwable $e) {
            error_log('[Postyar Push] Send error for user ' . $userId . ': ' . $e->getMessage());
            return false;
        }
    }

    /**
     * ارسال اعلان به تمام کاربران (برداشت)
     */
    public static function sendPushBroadcast(string $title, string $body, string $url = ''): array {
        $vapid = Bootstrap::getConfig('vapid');
        if (empty($vapid['enabled']) || empty($vapid['private_key_pem'])) {
            return [];
        }

        $db = Bootstrap::getDB();
        $subs = $db->query('SELECT id, endpoint, keys_p256dh, keys_auth FROM push_subscriptions')->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($subs)) return [];

        $appUrl = Bootstrap::getConfig('app.url');
        $payload = json_encode([
            'title' => $title,
            'body'  => $body,
            'url'   => $url ?: $appUrl . '/dashboard',
        ], JSON_UNESCAPED_UNICODE);

        $vapidConfig = [
            'subject'    => $vapid['subject'],
            'publicKey'  => $vapid['public_key'],
            'privateKey' => $vapid['private_key_pem'],
        ];

        $results = [];
        $expiredEndpoints = [];

        foreach ($subs as $sub) {
            try {
                $result = WebPush::send(
                    $sub['endpoint'],
                    $sub['keys_p256dh'],
                    $sub['keys_auth'],
                    $payload,
                    $vapidConfig
                );
                $results[] = $result;

                if (!$result['success'] && in_array($result['status'], [404, 410])) {
                    $expiredEndpoints[] = $sub['endpoint'];
                }
            } catch (\Throwable $e) {
                $results[] = ['success' => false, 'error' => $e->getMessage()];
            }
        }

        // پاکسازی اشتراک‌های منقضی
        if (!empty($expiredEndpoints)) {
            $placeholders = implode(',', array_fill(0, count($expiredEndpoints), '?'));
            $db->prepare("DELETE FROM push_subscriptions WHERE endpoint IN ($placeholders)")
               ->execute($expiredEndpoints);
        }

        return $results;
    }

    /**
     * ذخیره دسته‌بندی‌های تیکت (AJAX از پنل مدیریت)
     */
    public function handleSaveTicketCategories() {
        header('Content-Type: application/json; charset=utf-8');
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            echo json_encode(['success' => false, 'message' => 'خطای امنیتی'], JSON_UNESCAPED_UNICODE);
            return;
        }

        $db = Bootstrap::getDB();
        $categories_raw = json_decode($_POST['categories'] ?? '[]', true);
        if (!is_array($categories_raw)) {
            echo json_encode(['success' => false, 'message' => 'داده نامعتبر'], JSON_UNESCAPED_UNICODE);
            return;
        }

        try {
            // حذف همه دسته‌بندی‌های فعلی
            $db->exec("DELETE FROM ticket_categories");

            $sort_order = 1;
            foreach ($categories_raw as $cat) {
                $slug = trim($cat['slug'] ?? '');
                $title = trim($cat['title'] ?? '');
                $icon = trim($cat['icon'] ?? '🌐');
                $assigned_agent = (int)($cat['assigned_agent'] ?? 0);
                if (empty($slug) || empty($title)) continue;

                $stmt = $db->prepare("INSERT INTO ticket_categories (slug, title, icon, assigned_agent_id, sort_order) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$slug, $title, $icon, $assigned_agent ?: null, $sort_order++]);
            }

            echo json_encode(['success' => true, 'message' => 'دسته‌بندی‌ها با موفقیت ذخیره شدند ✔'], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            error_log('[Postyar] Save ticket categories error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'خطا در ذخیره‌سازی'], JSON_UNESCAPED_UNICODE);
        }
    }
}
