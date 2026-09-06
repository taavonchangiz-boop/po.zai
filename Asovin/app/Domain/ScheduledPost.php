<?php
namespace WHCM\Domain;

use WHCM\Core\Bootstrap;
use WHCM\Domain\Sender;
use WHCM\Domain\Quota;

/**
 * پردازش خودکار پست‌های زمان‌بندی‌شده توسط Cron Job
 *
 * پست‌هایی با status='scheduled' و scheduled_at<=NOW را پیدا کرده
 * و به کانال‌های هدف ارسال می‌کند.
 *
 * @package WHCM\Domain
 */
class ScheduledPost {

    /**
     * پردازش تمامی پست‌های زمان‌بندی‌شده‌ای که زمان آن‌ها رسیده است
     */
    public static function processAll(): int {
        $db = Bootstrap::getDB();
        $now = date('Y-m-d H:i:s');
        $processed = 0;

        // یافتن پست‌های زمان‌بندی‌شده‌ای که زمان ارسالشان فرا رسیده
        $driver = Bootstrap::getConfig('database.driver', 'sqlite');
        if ($driver === 'mysql') {
            $stmt = $db->prepare("
                SELECT p.* FROM posts p
                WHERE p.status = 'scheduled' AND p.scheduled_at <= ?
                ORDER BY p.scheduled_at ASC
                LIMIT 50
            ");
        } else {
            $stmt = $db->prepare("
                SELECT p.* FROM posts p
                WHERE p.status = 'scheduled' AND p.scheduled_at <= ?
                ORDER BY p.scheduled_at ASC
                LIMIT 50
            ");
        }
        $stmt->execute([$now]);
        $posts = $stmt->fetchAll();

        foreach ($posts as $post) {
            $tenant_id = (int)$post['tenant_id'];
            $post_id = (int)$post['id'];

            // بازیابی لیست کانال‌های هدف
            $channel_ids = json_decode($post['target_channels'] ?? '[]', true);
            if (empty($channel_ids) || !is_array($channel_ids)) {
                // فال‌بک: اگر target_channels ذخیره نشده بود، تمام کانال‌های مستاجر را استفاده کن
                $stmt2 = $db->prepare("SELECT id FROM channels WHERE tenant_id = ?");
                $stmt2->execute([$tenant_id]);
                $channel_ids = $stmt2->fetchAll(\PDO::FETCH_COLUMN);
            }

            if (empty($channel_ids)) {
                // بدون کانال هدف، پست را ناموفق علامت‌گذاری می‌کنیم
                $db->prepare("UPDATE posts SET status = 'failed' WHERE id = ?")->execute([$post_id]);
                error_log('[Postyar Cron] Scheduled post #' . $post_id . ' has no target channels.');
                $processed++;
                continue;
            }

            // بررسی سهمیه
            $quota = Quota::getTenantQuota($tenant_id);
            if (!$quota['can_send_post']) {
                // اگر سهمیه تمام شده، پست را در وضعیت scheduled نگه دارید (ارسال نشده)
                error_log('[Postyar Cron] User #' . $tenant_id . ' has no post quota for scheduled post #' . $post_id . '.');
                continue;
            }

            // استخراج محتوای اصلی (بدون لینک ردیابی) برای ارسال
            $content = $post['content'];

            // ارسال به کانال‌ها
            $res = Sender::sendPostToChannels(
                $tenant_id,
                $channel_ids,
                $post['title'],
                $content,
                $post['media_url'] ?? '',
                $post_id
            );

            if ($res['success']) {
                $db->prepare("UPDATE posts SET status = 'sent' WHERE id = ?")->execute([$post_id]);
            } else {
                $db->prepare("UPDATE posts SET status = 'failed' WHERE id = ?")->execute([$post_id]);
                $errors = [];
                foreach ($res['channels'] ?? [] as $ch) {
                    if (!$ch['success']) {
                        $errors[] = $ch['name'] . ': ' . $ch['message'];
                    }
                }
                error_log('[Postyar Cron] Scheduled post #' . $post_id . ' failed: ' . implode('; ', $errors));
            }

            $processed++;
        }

        return $processed;
    }
}
