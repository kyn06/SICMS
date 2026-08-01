<?php

require_once 'Model.php';

class AuditLog extends Model {
    protected static $table = 'audit_logs';
    protected static $primaryKey = 'audit_log_id';

    public static function record($user, $action, $description) {
        try {
            $accountId = null;
            $userName = 'System';
            $userRole = 'System';

            if (is_object($user)) {
                $user = get_object_vars($user);
            }

            if (is_array($user)) {
                $accountId = isset($user['account_id']) ? (int) $user['account_id'] : null;
                $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['email'] ?? 'User');
                $userRole = $user['role'] ?? 'User';
            }

            $sql = "INSERT INTO audit_logs
                        (account_id, user_name, user_role, action, description, ip_address, user_agent, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = self::$conn->prepare($sql);

            if (!$stmt) {
                return null;
            }

            $ipAddress = self::ipAddress();
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
            $createdAt = date('Y-m-d H:i:s');
            $stmt->bind_param("isssssss", $accountId, $userName, $userRole, $action, $description, $ipAddress, $userAgent, $createdAt);

            if (!$stmt->execute()) {
                return null;
            }

            return self::find(mysqli_insert_id(self::$conn));
        } catch (Throwable $exception) {
            return null;
        }
    }

    public static function filters(array $input) {
        return [
            'search' => trim($input['search'] ?? ''),
            'account_id' => (int) ($input['account_id'] ?? 0),
            'role' => trim($input['role'] ?? ''),
            'action' => trim($input['action'] ?? ''),
            'date_from' => self::dateValue($input['date_from'] ?? ''),
            'date_to' => self::dateValue($input['date_to'] ?? ''),
            'page' => max(1, (int) ($input['page'] ?? 1)),
        ];
    }

    public static function listLogs(array $filters, $perPage = 25) {
        [$where, $params, $types] = self::whereClause($filters);
        $offset = ((int) $filters['page'] - 1) * $perPage;
        $sql = "SELECT *
                FROM audit_logs
                $where
                ORDER BY created_at DESC, audit_log_id DESC
                LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= 'ii';

        return self::fetchAll($sql, $params, $types);
    }

    public static function countLogs(array $filters) {
        [$where, $params, $types] = self::whereClause($filters);
        $row = self::fetchOne("SELECT COUNT(*) AS total FROM audit_logs $where", $params, $types);

        return (int) ($row['total'] ?? 0);
    }

    public static function filterOptions() {
        return [
            'users' => self::fetchAll("SELECT DISTINCT account_id, user_name
                                       FROM audit_logs
                                       WHERE account_id IS NOT NULL
                                       ORDER BY user_name"),
            'roles' => self::singleColumn("SELECT DISTINCT user_role FROM audit_logs WHERE user_role IS NOT NULL AND user_role <> '' ORDER BY user_role"),
            'actions' => self::singleColumn("SELECT DISTINCT action FROM audit_logs ORDER BY action"),
        ];
    }

    public static function exportRows(array $filters) {
        [$where, $params, $types] = self::whereClause($filters);

        return self::fetchAll("SELECT *
                               FROM audit_logs
                               $where
                               ORDER BY created_at DESC, audit_log_id DESC
                               LIMIT 1000", $params, $types);
    }

    private static function whereClause(array $filters) {
        $where = ['1 = 1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $where[] = "(user_name LIKE ? OR action LIKE ? OR description LIKE ? OR ip_address LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like, $like);
            $types .= 'ssss';
        }

        if (!empty($filters['account_id'])) {
            $where[] = "account_id = ?";
            $params[] = (int) $filters['account_id'];
            $types .= 'i';
        }

        if (!empty($filters['role'])) {
            $where[] = "user_role = ?";
            $params[] = $filters['role'];
            $types .= 's';
        }

        if (!empty($filters['action'])) {
            $where[] = "action = ?";
            $params[] = $filters['action'];
            $types .= 's';
        }

        if (!empty($filters['date_from'])) {
            $where[] = "created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        return ['WHERE ' . implode(' AND ', $where), $params, $types];
    }

    private static function fetchOne($sql, array $params = [], $types = '') {
        $rows = self::fetchAll($sql, $params, $types);

        return $rows[0] ?? [];
    }

    private static function fetchAll($sql, array $params = [], $types = '') {
        try {
            $stmt = self::$conn->prepare($sql);

            if (!$stmt) {
                return [];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function singleColumn($sql) {
        $rows = self::fetchAll($sql);
        $values = [];

        foreach ($rows as $row) {
            $values[] = reset($row);
        }

        return $values;
    }

    private static function dateValue($value) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : '';
    }

    private static function ipAddress() {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }
}
