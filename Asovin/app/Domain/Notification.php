<?php
namespace WHCM\Domain;

use WHCM\Core\Bootstrap;

/**
 * مدیریت اعلان‌های کاربر
 *
 * @package WHCM\Domain
 */
class Notification {

    /**
     * ایجاد یک اعلان جدید برای یک کاربر
     */
    public static function create(int $user_id, string $title, string $message = '', string $type = 'general', string $target_section = ''): int {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, target_section) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $type, $title, $message, $target_section]);
        return (int)$db->lastInsertId();
    }

    /**
     * ایجاد اعلان همگانی برای تمام کاربران
     */
    public static function broadcast(string $title, string $message = '', string $type = 'announcement', string $target_section = ''): int {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, target_section) SELECT id, ?, ?, ?, ? FROM users WHERE role != 'superadmin'");
        $stmt->execute([$type, $title, $message, $target_section]);
        return $stmt->rowCount();
    }

    /**
     * دریافت اعلان‌های کاربر (با صفحه‌بندی)
     */
    public static function getUserNotifications(int $user_id, int $limit = 20, int $offset = 0): array {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user_id, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * دریافت تعداد اعلان‌های خوانده‌نشده
     */
    public static function getUnreadCount(int $user_id): int {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetch()['cnt'];
    }

    /**
     * دریافت آخرین اعلان‌های خوانده‌نشده (برای نمایش در زنگوله)
     */
    public static function getRecentUnread(int $user_id, int $limit = 10): array {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT ?");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * علامت‌گذاری یک اعلان به‌عنوان خوانده‌شده
     */
    public static function markAsRead(int $notification_id, int $user_id): bool {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        return $stmt->execute([$notification_id, $user_id]) && $stmt->rowCount() > 0;
    }

    /**
     * علامت‌گذاری تمام اعلان‌های یک کاربر به‌عنوان خوانده‌شده
     */
    public static function markAllAsRead(int $user_id): int {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        return $stmt->rowCount();
    }

    /**
     * حذف اعلان‌های قدیمی‌تر از X روز
     */
    public static function cleanupOld(int $days = 90): int {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM notifications WHERE created_at < datetime('now', ?)");
        $stmt->execute(["-{$days} days"]);
        return $stmt->rowCount();
    }
}
