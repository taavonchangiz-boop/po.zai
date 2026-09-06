<?php
namespace WHCM\Domain;

use WHCM\Core\Bootstrap;
use WHCM\Core\Auth;
use WHCM\Core\HttpClient;

/**
 * مدیریت کانال‌های اختصاصی کاربران با رعایت محدودیت‌های سهمیه و قوانین ضد تقلب
 *
 * @package WHCM\Domain
 */
class ChannelManager {

    /**
     * بررسی سهمیه و افزودن کانال جدید برای مستاجر فعلی
     *
     * @param string $name نام دلخواه کانال
     * @param string $platform 'telegram' یا 'bale'
     * @param string $channel_id شناسه کانال (شروع با @ یا آیدی عددی)
     * @param string $token توکن ربات تلگرام/بله
     * @return array ['success' => bool, 'message' => string]
     */
    public static function addChannel(string $name, string $platform, string $channel_id, string $token): array {
        $tenant_id = Auth::tenantId();
        if (!$tenant_id) {
            return ['success' => false, 'message' => 'کاربر احراز هویت نشده است.'];
        }

        // استانداردسازی شناسه کانال (حذف فاصله‌ها و اطمینان از فرمت صحیح)
        $channel_id = trim($channel_id);
        $platform = strtolower(trim($platform));

        if (empty($name) || empty($channel_id) || empty($token)) {
            return ['success' => false, 'message' => 'تمام فیلدهای الزامی را پر کنید.'];
        }

        if (!in_array($platform, ['telegram', 'bale'])) {
            return ['success' => false, 'message' => 'پلتفرم نامعتبر است.'];
        }

        $db = Bootstrap::getDB();

        // ۱. بررسی محدودیت سهمیه تعداد کانال بر اساس اشتراک کاربر
        $quota = Quota::getTenantQuota($tenant_id);
        if (!$quota['can_add_channel']) {
            return [
                'success' => false,
                'message' => "شما به حداکثر تعداد کانال مجاز در پلن خود ({$quota['max_channels']} کانال) رسیده‌اید. برای افزودن کانال جدید، لطفاً اشتراک خود را ارتقا دهید."
            ];
        }

        // ۲. اعتبارسنجی اتصال ربات (تست توکن با getMe)
        $test = self::testBotConnection($platform, $token);
        $network_blocked = false;
        $warning_msg = '';

        if (!$test['success']) {
            $err_msg = strtolower($test['message']);
            // تشخیص هوشمند مسدود بودن شبکه تلگرام روی هاست ایران (فیلترینگ)
            if (strpos($err_msg, 'timeout') !== false || strpos($err_msg, 'timed out') !== false || strpos($err_msg, 'resolve') !== false || strpos($err_msg, 'connect') !== false) {
                $network_blocked = true;
                $warning_msg = ' (توجه: به دلیل محدودیت‌های شبکه هاست ایران شما در اتصال مستقیم به تلگرام، ربات بدون تایید زنده ثبت گردید. پیشنهاد می‌شود از آدرس پروکسی یا هاست خارج از کشور استفاده کنید)';
            } else {
                return [
                    'success' => false,
                    'message' => 'اتصال به ربات برقرار نشد! جزئیات خطا: ' . $test['message']
                ];
            }
        }

        // ۳. بررسی قانون ضد تقلب (قفل کانال در ثبت جهانی)
        $stmt = $db->prepare("SELECT owner_user_id FROM channel_registry WHERE platform = ? AND channel_id = ? LIMIT 1");
        $stmt->execute([$platform, $channel_id]);
        $registry = $stmt->fetch();

        if ($registry) {
            if ((int)$registry['owner_user_id'] !== $tenant_id) {
                // کانال متعلق به کاربر دیگری است و قفل شده است!
                return [
                    'success' => false,
                    'message' => 'این کانال قبلاً توسط کاربر دیگری در سیستم ثبت شده است و امکان ثبت مجدد آن در پنل دیگر وجود ندارد.'
                ];
            }
            // متعلق به خود کاربر است؛ پس فقط بررسی می‌کنیم که در لیست کانال‌های فعالش تکراری نباشد
            $stmt = $db->prepare("SELECT id FROM channels WHERE tenant_id = ? AND platform = ? AND channel_id = ? LIMIT 1");
            $stmt->execute([$tenant_id, $platform, $channel_id]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'این کانال در حال حاضر در پنل شما فعال است.'];
            }
        }

        // شروع تراکنش جهت ثبت ایمن در هر دو جدول
        $db->beginTransaction();
        try {
            // الف) ثبت در رجیستری جهانی در صورت عدم وجود قبلی
            if (!$registry) {
                $stmt = $db->prepare("INSERT INTO channel_registry (platform, channel_id, owner_user_id) VALUES (?, ?, ?)");
                $stmt->execute([$platform, $channel_id, $tenant_id]);
            }

            // ب) ثبت کانال در لیست فعال کاربر
            $stmt = $db->prepare("INSERT INTO channels (tenant_id, name, platform, channel_id, token, link_config, button_config) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $tenant_id,
                $name,
                $platform,
                $channel_id,
                $token,
                json_encode([
                    ['name' => 'مشاهده سایت', 'url' => ''],
                    ['name' => 'کانال تلگرام', 'url' => ''],
                    ['name' => 'کانال بله', 'url' => '']
                ], JSON_UNESCAPED_UNICODE),
                json_encode([
                    'active' => false,
                    'buttons' => [
                        ['text' => 'پشتیبانی', 'url' => ''],
                        ['text' => 'ثبت سفارش', 'url' => '']
                    ]
                ], JSON_UNESCAPED_UNICODE)
            ]);

            $db->commit();

            // تلاش برای ثبت وبهوک (در صورت عدم موفقیت، Polling فعال خواهد بود)
            $stmt_new = $db->prepare("SELECT * FROM channels WHERE tenant_id = ? AND platform = ? AND channel_id = ? ORDER BY id DESC LIMIT 1");
            $stmt_new->execute([$tenant_id, $platform, $channel_id]);
            $new_channel = $stmt_new->fetch();
            if ($new_channel) {
                self::tryActivateWebhook($new_channel);
            }

            return ['success' => true, 'message' => 'کانال جدید با موفقیت به پنل شما متصل شد.' . $warning_msg];

        } catch (\Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'خطایی در ثبت کانال رخ داد: ' . $e->getMessage()];
        }
    }

    /**
     * تست زنده اتصال ربات به سرورهای تلگرام/بله
     */
    public static function testBotConnection(string $platform, string $token): array {
        $base_url = ($platform === 'bale') ? 'https://tapi.bale.ai/bot' : 'https://api.telegram.org/bot';
        $url = $base_url . trim($token) . '/getMe';

        // استفاده از کلاس کمکی برای ارسال درخواست
        $res = HttpClient::get($url, [], 10);
        if (!$res['success']) {
            return ['success' => false, 'message' => $res['error'] ?? 'خطای شبکه یا مسدود بودن هاست.'];
        }

        $data = json_decode($res['body'], true);
        if (!empty($data['ok'])) {
            $bot_name = $data['result']['first_name'] ?? 'Bot';
            $bot_username = $data['result']['username'] ?? '';
            return [
                'success' => true,
                'message' => "اتصال موفقیت‌آمیز بود! نام ربات: {$bot_name} (@{$bot_username})"
            ];
        }

        return [
            'success' => false,
            'message' => $data['description'] ?? 'توکن نامعتبر است.'
        ];
    }

    /**
     * دریافت لیست کانال‌های متصل کاربر
     */
    public static function getTenantChannels(?int $tenant_id = null): array {
        $tenant_id = $tenant_id ?? Auth::tenantId();
        if (!$tenant_id) {
            return [];
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM channels WHERE tenant_id = ? ORDER BY id DESC");
        $stmt->execute([$tenant_id]);
        return $stmt->fetchAll();
    }

    /**
     * دریافت اطلاعات یک کانال خاص با اعتبارسنجی مالکیت
     */
    public static function getChannel(int $id, ?int $tenant_id = null): ?array {
        $tenant_id = $tenant_id ?? Auth::tenantId();
        if (!$tenant_id) {
            return null;
        }

        $db = Bootstrap::getDB();
        // برای امنیت و رعایت حریم خصوصی مستاجر، حتماً شرط tenant_id اعمال می‌شود
        $stmt = $db->prepare("SELECT * FROM channels WHERE id = ? AND tenant_id = ? LIMIT 1");
        $stmt->execute([$id, $tenant_id]);
        $channel = $stmt->fetch();
        return $channel ?: null;
    }

    /**
     * حذف کانال از پنل کاربر (ولی در رجیستری ضدتقلب برای همیشه باقی می‌ماند)
     */
    public static function deleteChannel(int $id): bool {
        $tenant_id = Auth::tenantId();
        if (!$tenant_id) {
            return false;
        }

        $channel = self::getChannel($id, $tenant_id);
        if (!$channel) {
            return false;
        }

        // اگر وبهوک فعال بود، ابتدا آن را حذف می‌کنیم
        if ($channel['webhook_active']) {
            self::deleteWebhook($channel);
        }

        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM channels WHERE id = ? AND tenant_id = ?");
        return $stmt->execute([$id, $tenant_id]);
    }

    /**
     * ثبت آدرس وبهوک برای دریافت پیام‌ها و پاسخگو بودن خودکار
     */
    public static function setWebhook(array $channel): array {
        $platform = $channel['platform'];
        $token = trim($channel['token']);
        $base_url = ($platform === 'bale') ? 'https://tapi.bale.ai/bot' : 'https://api.telegram.org/bot';

        // آدرس روت دریافت وبهوک در سیستم SaaS
        $app_url = Bootstrap::getConfig('app.url', 'http://localhost');
        $webhook_url = rtrim($app_url, '/') . '/api/webhook?channel_id=' . (int)$channel['id'];
        
        $url = $base_url . $token . '/setWebhook';
        $body = ['url' => $webhook_url];

        if ($platform === 'telegram' && !empty($channel['webhook_secret'])) {
            $body['secret_token'] = $channel['webhook_secret'];
        }

        $res = HttpClient::post($url, $body, [], 15);
        if (!$res['success']) {
            return ['success' => false, 'message' => 'خطا در ارتباط با سرور مقصد.'];
        }

        $data = json_decode($res['body'], true);
        if (!empty($data['ok'])) {
            $db = Bootstrap::getDB();
            $db->prepare("UPDATE channels SET webhook_active = 1 WHERE id = ?")->execute([$channel['id']]);
            return ['success' => true, 'message' => 'وبهوک ربات با موفقیت فعال شد.'];
        }

        return ['success' => false, 'message' => $data['description'] ?? 'خطا در ثبت وبهوک ربات.'];
    }

    /**
     * تلاش برای فعال‌سازی وبهوک روی یک کانال. در صورت عدم موفقیت، حالت Polling فعال می‌ماند.
     */
    public static function tryActivateWebhook(array $channel): void {
        try {
            $result = self::setWebhook($channel);
            if ($result['success']) {
                error_log('[Postyar] Webhook set for channel #' . (int)$channel['id'] . ' (' . $channel['platform'] . ')');
            } else {
                error_log('[Postyar] Webhook failed for channel #' . (int)$channel['id'] . ': ' . $result['message']);
            }
        } catch (\Throwable $e) {
            error_log('[Postyar] Webhook error for channel #' . (int)$channel['id'] . ': ' . $e->getMessage());
        }
    }

    /**
     * حذف آدرس وبهوک
     */
    public static function deleteWebhook(array $channel): bool {
        $platform = $channel['platform'];
        $token = trim($channel['token']);
        $base_url = ($platform === 'bale') ? 'https://tapi.bale.ai/bot' : 'https://api.telegram.org/bot';

        $url = $base_url . $token . '/deleteWebhook';
        $res = HttpClient::post($url, [], [], 15);

        if ($res['success']) {
            $data = json_decode($res['body'], true);
            if (!empty($data['ok'])) {
                $db = Bootstrap::getDB();
                $db->prepare("UPDATE channels SET webhook_active = 0 WHERE id = ?")->execute([$channel['id']]);
                return true;
            }
        }

        return false;
    }
}
