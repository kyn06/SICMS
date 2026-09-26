<?php

require_once 'Model.php';
require_once __DIR__ . '/../helpers/GeoLocation.php';

class LoginSession extends Model {
    protected static $table = 'login_sessions';
    protected static $primaryKey = 'session_id';

    public static function register($accountId, $sessionId, $userAgent, $ipAddress) {
        return self::create([
            'session_id' => $sessionId,
            'account_id' => (int) $accountId,
            'device_type' => self::deviceType($userAgent),
            'ip_address' => $ipAddress,
            'location' => GeoLocation::forIp($ipAddress),
            'user_agent' => substr($userAgent, 0, 255),
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_seen_at' => date('Y-m-d H:i:s'),
            'revoked_at' => null,
        ]);
    }

    public static function touch($sessionId) {
        $stmt = self::$conn->prepare("UPDATE login_sessions SET last_seen_at = ? WHERE session_id = ? AND revoked_at IS NULL");
        if (!$stmt) return false;
        $now = date('Y-m-d H:i:s');
        $stmt->bind_param('ss', $now, $sessionId);
        return $stmt->execute();
    }

    public static function ensureCurrent($accountId, $sessionId, $userAgent, $ipAddress) {
        $stmt = self::$conn->prepare(
            "INSERT INTO login_sessions
                     (session_id, account_id, device_type, ip_address, location, user_agent, last_login_at, last_seen_at, revoked_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL)
                 ON DUPLICATE KEY UPDATE account_id = VALUES(account_id), device_type = VALUES(device_type), ip_address = VALUES(ip_address), location = VALUES(location), user_agent = VALUES(user_agent), last_seen_at = VALUES(last_seen_at), revoked_at = NULL"
        );
        if (!$stmt) return false;
        $deviceType = self::deviceType($userAgent);
        $location = GeoLocation::forIp($ipAddress);
        $now = date('Y-m-d H:i:s');
        $accountId = (int) $accountId;
        $stmt->bind_param('sissssss', $sessionId, $accountId, $deviceType, $ipAddress, $location, $userAgent, $now, $now);
        return $stmt->execute();
    }

    public static function forAccount($accountId, $currentSessionId) {
        $stmt = self::$conn->prepare("SELECT *, session_id = ? AS is_current FROM login_sessions WHERE account_id = ? AND revoked_at IS NULL ORDER BY is_current DESC, last_seen_at DESC");
        if (!$stmt) return [];
        $accountId = (int) $accountId;
        $stmt->bind_param('si', $currentSessionId, $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function revoke($sessionId, $accountId) {
        $stmt = self::$conn->prepare("UPDATE login_sessions SET revoked_at = ? WHERE session_id = ? AND account_id = ? AND revoked_at IS NULL");
        if (!$stmt) return false;
        $now = date('Y-m-d H:i:s');
        $accountId = (int) $accountId;
        $stmt->bind_param('ssi', $now, $sessionId, $accountId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    public static function destroyPhpSession($sessionId) {
        if ($sessionId === '' || session_status() !== PHP_SESSION_ACTIVE || session_id() === $sessionId) return false;
        $currentSessionId = session_id();
        session_write_close();
        session_id($sessionId);
        session_start();
        $_SESSION = [];
        session_destroy();
        session_id($currentSessionId);
        session_start();
        return true;
    }

    private static function deviceType($userAgent) {
        $agent = strtolower((string) $userAgent);
        if (str_contains($agent, 'mobile') || str_contains($agent, 'android') || str_contains($agent, 'iphone')) return 'Mobile device';
        if (str_contains($agent, 'tablet') || str_contains($agent, 'ipad')) return 'Tablet';
        return 'Desktop browser';
    }
}
