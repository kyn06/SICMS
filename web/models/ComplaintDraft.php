<?php

require_once 'Model.php';

class ComplaintDraft extends Model {
    protected static $table = 'complaint_drafts';

    public static function ensureTable() {
        return self::$conn->query(
            "CREATE TABLE IF NOT EXISTS complaint_drafts (
                draft_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                account_id INT UNSIGNED NOT NULL,
                submission_token VARCHAR(64) NOT NULL,
                payload LONGTEXT NOT NULL,
                version INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (draft_id),
                UNIQUE KEY uq_complaint_drafts_account (account_id),
                CONSTRAINT fk_complaint_drafts_account FOREIGN KEY (account_id) REFERENCES accounts (account_id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public static function forAccount($accountId) {
        self::ensureTable();
        $stmt = self::$conn->prepare('SELECT draft_id, payload, version, updated_at FROM complaint_drafts WHERE account_id = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row) return null;
        $decoded = json_decode($row['payload'], true);
        $row['payload'] = is_array($decoded) ? $decoded : [];
        return $row;
    }

    public static function saveVersioned($accountId, $submissionToken, array $payload, $expectedVersion) {
        self::ensureTable();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || strlen($json) > 524288) return ['status' => 'invalid'];
        $current = self::forAccount($accountId);
        if (!$current) {
            if ((int) $expectedVersion !== 0) return ['status' => 'conflict', 'draft' => null];
            $stmt = self::$conn->prepare('INSERT INTO complaint_drafts (account_id, submission_token, payload, version) VALUES (?, ?, ?, 1)');
            if (!$stmt) return ['status' => 'error'];
            $stmt->bind_param('iss', $accountId, $submissionToken, $json);
            if (!$stmt->execute()) return ['status' => $stmt->errno === 1062 ? 'conflict' : 'error'];
        } else {
            $stmt = self::$conn->prepare('UPDATE complaint_drafts SET submission_token = ?, payload = ?, version = version + 1, updated_at = NOW() WHERE account_id = ? AND version = ?');
            if (!$stmt) return ['status' => 'error'];
            $expectedVersion = (int) $expectedVersion;
            $stmt->bind_param('ssii', $submissionToken, $json, $accountId, $expectedVersion);
            if (!$stmt->execute()) return ['status' => 'error'];
            if ($stmt->affected_rows !== 1) return ['status' => 'conflict', 'draft' => self::forAccount($accountId)];
        }
        return ['status' => 'saved', 'draft' => self::forAccount($accountId)];
    }

    public static function deleteForAccount($accountId) {
        self::ensureTable();
        $stmt = self::$conn->prepare('DELETE FROM complaint_drafts WHERE account_id = ?');
        if (!$stmt) return false;
        $stmt->bind_param('i', $accountId);
        return $stmt->execute();
    }
}
