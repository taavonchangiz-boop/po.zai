<?php
namespace WHCM\Modules\Billing\Controllers;

use WHCM\Core\Bootstrap;
use WHCM\Core\Csrf;
use WHCM\Domain\Notification;
use WHCM\Controllers\BaseController;

/**
 * کنترلر ماژول Billing — تأیید پرداخت‌ها
 * قدم ۲-الف
 */
class PaymentController extends BaseController
{
    public function approve()
    {
        $this->checkSuperAdmin();
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->setFlashMessage('خطای امنیتی! توکن نامعتبر است.');
            $this->redirect('/hnnh');
        }
        $id = (int)($_POST['payment_id'] ?? 0);
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM payments WHERE id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$id]);
        $payment = $stmt->fetch();
        if (!$payment) {
            $this->setFlashMessage('تراکنش مورد نظر یافت نشد یا قبلاً پردازش شده است.');
            $this->redirect('/hnnh');
        }
        $user_id = (int)$payment['user_id'];
        $plan_id = (int)$payment['plan_id'];
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = ? LIMIT 1");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        if (!$plan) {
            $this->setFlashMessage('پلن مربوطه یافت نشد.');
            $this->redirect('/hnnh');
        }
        $db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $db->prepare("UPDATE payments SET status = 'approved', verified_at = ? WHERE id = ?");
            $stmt->execute([$now, $id]);
            $stmt = $db->prepare("UPDATE subscriptions SET status = 'expired' WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $duration = (int)$plan['duration_days'];
            $start_date = $now;
            $end_date = $duration > 0 ? date('Y-m-d H:i:s', strtotime("+{$duration} days")) : '2099-12-30 00:00:00';
            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$user_id, $plan_id, $start_date, $end_date]);
            $db->commit();

            // ارسال نوتیفیکیشن پوش به کاربر
            try {
                \WHCM\Controllers\MainController::sendPushToUser($user_id, '✅ اشتراک شما فعال شد!', 'پلن «' . $plan['title'] . '» با موفقیت فعال گردید. ✔', '/dashboard');
            } catch (\Throwable $e) {}

            // ثبت اعلان درون‌برنامه‌ای
            try {
                Notification::create($user_id, '✅ اشتراک شما فعال شد', 'پلن «' . $plan['title'] . '» با موفقیت فعال گردید و از همین لحظه قابل استفاده است.', 'subscription', 'upgrade');
            } catch (\Throwable $e) {}

            $this->setFlashMessage('پرداخت با موفقیت تایید و اشتراک کاربر بلافاصله فعال گردید. ✔');
        } catch (\Exception $e) {
            $db->rollBack();
            $this->setFlashMessage('بروز خطا در پردازش تایید تراکنش: ' . $e->getMessage());
        }
        $this->redirect('/hnnh');
    }
}
