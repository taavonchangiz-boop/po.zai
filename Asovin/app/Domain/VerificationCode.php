<?php
namespace WHCM\Domain;

use WHCM\Core\Bootstrap;

class VerificationCode {

    public static function generate(int $userId, string $type, int $expiryMinutes = 5): string {
        $db = Bootstrap::getDB();
        $code = (string)random_int(100000, 999999);
        $expiresAt = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));

        try {
            $stmt = $db->prepare("DELETE FROM verification_codes WHERE user_id = ? AND type = ? AND used = 0");
            $stmt->execute([$userId, $type]);
        } catch (\Exception $e) {}

        $stmt = $db->prepare("INSERT INTO verification_codes (user_id, type, code, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $code, $expiresAt]);
        return $code;
    }

    public static function verify(int $userId, string $type, string $code): bool {
        $db = Bootstrap::getDB();
        $stmt = $db->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND type = ? AND used = 0 ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId, $type]);
        $record = $stmt->fetch();

        if (!$record) return false;
        if (!hash_equals($record['code'], $code)) return false;
        if (strtotime($record['expires_at']) < time()) return false;

        try {
            $stmt = $db->prepare("UPDATE verification_codes SET used = 1 WHERE id = ?");
            $stmt->execute([$record['id']]);
        } catch (\Exception $e) {}
        return true;
    }

    public static function cleanup(): void {
        $db = Bootstrap::getDB();
        $driver = Bootstrap::getConfig('database.driver', 'sqlite');
        try {
            if ($driver === 'mysql') {
                $db->prepare("DELETE FROM verification_codes WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")->execute();
            } else {
                $db->prepare("DELETE FROM verification_codes WHERE created_at < datetime('now', '-24 hours')")->execute();
            }
        } catch (\Exception $e) {}
    }
}
