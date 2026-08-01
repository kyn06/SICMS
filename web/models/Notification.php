<?php

require_once 'Model.php';
require_once 'AuditLog.php';

class Notification extends Model {
    protected static $table = 'notifications';
    protected static $primaryKey = 'notification_id';

    private static $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];

    public static function createForUser($accountId, $type, $title, $message, $link = null) {
        if (!$accountId) {
            return null;
        }

        try {
            $notification = parent::create([
                'account_id' => (int) $accountId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            AuditLog::record(null, 'Notification Creation', 'Created notification "' . $title . '" for account #' . (int) $accountId . '.');

            return $notification;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public static function createForStaff($type, $title, $message, $link = null) {
        try {
            foreach (self::getStaffAccounts() as $account) {
                self::createForUser($account['account_id'], $type, $title, $message, $link);
            }
        } catch (Throwable $exception) {
            return;
        }
    }

    public static function notifyNewMessage($recipientAccountId, $senderName, $link = null) {
        return self::createForUser(
            $recipientAccountId,
            'message_received',
            'New Message Received',
            'You received a new message from ' . $senderName . '.',
            $link
        );
    }

    public static function unreadCount($accountId) {
        try {
            $stmt = self::$conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE account_id = ? AND is_read = 0");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return (int) ($row['total'] ?? 0);
        } catch (Throwable $exception) {
            return 0;
        }
    }

    public static function recentForUser($accountId, $limit = 5) {
        try {
            $stmt = self::$conn->prepare("SELECT * FROM notifications WHERE account_id = ? ORDER BY created_at DESC LIMIT ?");
            $stmt->bind_param("ii", $accountId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function allForUser($accountId) {
        try {
            $stmt = self::$conn->prepare("SELECT * FROM notifications WHERE account_id = ? ORDER BY created_at DESC");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function markAsRead($notificationId, $accountId) {
        $now = date('Y-m-d H:i:s');
        $stmt = self::$conn->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE notification_id = ? AND account_id = ?");
        $stmt->bind_param("sii", $now, $notificationId, $accountId);
        return $stmt->execute();
    }

    public static function markAllAsRead($accountId) {
        $now = date('Y-m-d H:i:s');
        $stmt = self::$conn->prepare("UPDATE notifications SET is_read = 1, read_at = ? WHERE account_id = ? AND is_read = 0");
        $stmt->bind_param("si", $now, $accountId);
        return $stmt->execute();
    }

    private static function getStaffAccounts() {
        $normalizedRoles = array_map(fn($role) => strtolower(str_replace(['_', ' '], '-', $role)), self::$staffRoles);
        $placeholders = implode(', ', array_fill(0, count($normalizedRoles), '?'));
        $sql = "SELECT account_id FROM accounts WHERE status = 'active' AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ($placeholders)";
        $stmt = self::$conn->prepare($sql);
        $types = str_repeat('s', count($normalizedRoles));
        $stmt->bind_param($types, ...$normalizedRoles);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
