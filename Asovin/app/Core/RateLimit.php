<?php
namespace WHCM\Core;

/**
 * مدیریت محدودیت نرخ درخواست‌ها (Rate Limiting) جهت جلوگیری از حملات Brute force و اسپم
 *
 * @package WHCM\Core
 */
class RateLimit {
    /**
     * بررسی اینکه آیا درخواست مجاز است یا خیر
     *
     * @param string $action نام عملیات (مثلاً 'login' یا 'register')
     * @param int $max_attempts حداکثر تلاش مجاز
     * @param int $lock_seconds زمان قفل به ثانیه
     * @return bool
     */
    public static function check(string $action, int $max_attempts = 5, int $lock_seconds = 60): bool {
        $ip = self::getIp();
        $db = Bootstrap::getDB();

        // پاکسازی رکوردهای منقضی شده قدیمی
        $now = time();
        $db->prepare("DELETE FROM rate_limits WHERE last_attempt < ?")->execute([$now - $lock_seconds]);

        // دریافت وضعیت فعلی این IP و اکشن
        $stmt = $db->prepare("SELECT attempts, last_attempt FROM rate_limits WHERE ip = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();

        if ($record) {
            if ($record['attempts'] >= $max_attempts && ($now - $record['last_attempt']) < $lock_seconds) {
                // هنوز در دوره قفل قرار دارد
                return false;
            }
        }

        return true;
    }

    /**
     * ثبت یک تلاش جدید (ناموفق)
     */
    public static function hit(string $action, int $lock_seconds = 60) {
        $ip = self::getIp();
        $db = Bootstrap::getDB();
        $now = time();

        $stmt = $db->prepare("SELECT id, attempts FROM rate_limits WHERE ip = ? AND action = ?");
        $stmt->execute([$ip, $action]);
        $record = $stmt->fetch();

        if ($record) {
            $stmt = $db->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = ? WHERE id = ?");
            $stmt->execute([$now, $record['id']]);
        } else {
            $stmt = $db->prepare("INSERT INTO rate_limits (ip, action, attempts, last_attempt) VALUES (?, ?, 1, ?)");
            $stmt->execute([$ip, $action, $now]);
        }
    }

    /**
     * ریست کردن شمارنده پس از یک تلاش موفق
     */
    public static function clear(string $action) {
        $ip = self::getIp();
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("DELETE FROM rate_limits WHERE ip = ? AND action = ?");
        $stmt->execute([$ip, $action]);
    }

    /**
     * استخراج آی‌پی واقعی کاربر (با اعتبارسنجی و اولویت صحیح)
     */
    private static function getIp(): string {
        // اولویت اول: REMOTE_ADDR که قابل جعل نیست
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // فقط در صورتی که از پروکسی معتبر استفاده می‌شود، X-Forwarded-For را در نظر بگیریم
        $trusted_proxies = Bootstrap::getConfig('security.trusted_proxies', []);
        if (!empty($trusted_proxies) && in_array($ip, $trusted_proxies, true)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $forwarded_ip = trim($ips[0]);
                // اعتبارسنجی فرمت IP
                if (filter_var($forwarded_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $forwarded_ip;
                }
            }
        }
        
        // اعتبارسنجی نهایی
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return '127.0.0.1';
    }
}
