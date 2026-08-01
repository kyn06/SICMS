<?php

require_once 'Model.php';

class Message extends Model {
    protected static $table = 'case_messages';
    protected static $primaryKey = 'message_id';

    private static $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private static $columnCache = [];

    public static function forCase($complaintId) {
        try {
            $sql = "SELECT m.*, sender.first_name AS sender_first_name, sender.last_name AS sender_last_name,
                           receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name
                    FROM case_messages m
                    INNER JOIN accounts sender ON m.sender_account_id = sender.account_id
                    INNER JOIN accounts receiver ON m.receiver_account_id = receiver.account_id
                    WHERE m.complaint_id = ?
                    ORDER BY m.created_at ASC, m.message_id ASC";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $complaintId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function forCaseForUser($complaintId, $accountId) {
        try {
            $sql = "SELECT m.*, sender.first_name AS sender_first_name, sender.last_name AS sender_last_name,
                           receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name
                    FROM case_messages m
                    INNER JOIN accounts sender ON m.sender_account_id = sender.account_id
                    INNER JOIN accounts receiver ON m.receiver_account_id = receiver.account_id
                    WHERE m.complaint_id = ?
                      AND (m.sender_account_id = ? OR m.receiver_account_id = ?)
                    ORDER BY m.created_at ASC, m.message_id ASC";
            $stmt = self::$conn->prepare($sql);
            $accountId = (int) $accountId;
            $stmt->bind_param("iii", $complaintId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function createMessage($complaintId, $senderAccountId, $receiverAccountId, $message, $attachment = null) {
        $now = date('Y-m-d H:i:s');
        $data = [
            'complaint_id' => (int) $complaintId,
            'sender_account_id' => (int) $senderAccountId,
            'receiver_account_id' => (int) $receiverAccountId,
            'message' => trim($message),
            'is_read' => 0,
            'created_at' => $now,
        ];

        if (self::hasColumn('attachment')) {
            $data['attachment'] = $attachment;
        }

        if (self::hasColumn('updated_at')) {
            $data['updated_at'] = $now;
        }

        $createdMessage = parent::create($data);

        return $createdMessage ? self::findWithNames((int) $createdMessage['message_id']) : null;
    }

    public static function markCaseMessagesRead($complaintId, $accountId) {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE case_messages SET is_read = 1, read_at = ? WHERE complaint_id = ? AND receiver_account_id = ? AND is_read = 0");
            $stmt->bind_param("sii", $now, $complaintId, $accountId);
            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function conversationsForUser(array $user) {
        try {
            $accountId = (int) $user['account_id'];
            $whereClause = "(c.submitted_by_account_id = ?
                             OR c.assigned_coordinator_account_id = ?
                             OR EXISTS (
                                SELECT 1
                                FROM case_messages participant_messages
                                WHERE participant_messages.complaint_id = c.complaint_id
                                  AND (participant_messages.sender_account_id = ? OR participant_messages.receiver_account_id = ?)
                             ))";

            $sql = "SELECT c.complaint_id, c.case_number, c.complainant_name, c.case_classification,
                           c.status, c.submitted_at, c.submitted_by_account_id,
                           c.assigned_coordinator_account_id,
                           submitter.first_name AS submitter_first_name,
                           submitter.last_name AS submitter_last_name,
                           submitter.role AS submitter_role,
                           coordinator.first_name AS coordinator_first_name,
                           coordinator.last_name AS coordinator_last_name,
                           coordinator.role AS coordinator_role,
                           latest.message AS latest_message,
                           latest.created_at AS latest_message_at,
                           latest.sender_account_id AS latest_sender_account_id,
                           COALESCE(unread.unread_total, 0) AS unread_total
                    FROM complaints c
                    LEFT JOIN accounts submitter ON c.submitted_by_account_id = submitter.account_id
                    LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                    LEFT JOIN (
                        SELECT cm.*
                        FROM case_messages cm
                        INNER JOIN (
                            SELECT complaint_id, MAX(message_id) AS latest_message_id
                            FROM case_messages
                            WHERE sender_account_id = ? OR receiver_account_id = ?
                            GROUP BY complaint_id
                        ) lm ON lm.latest_message_id = cm.message_id
                    ) latest ON latest.complaint_id = c.complaint_id
                    LEFT JOIN (
                        SELECT complaint_id, COUNT(*) AS unread_total
                        FROM case_messages
                        WHERE receiver_account_id = ? AND is_read = 0
                        GROUP BY complaint_id
                    ) unread ON unread.complaint_id = c.complaint_id
                    WHERE $whereClause
                    ORDER BY COALESCE(latest.created_at, c.submitted_at) DESC, c.complaint_id DESC";

            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("iiiiiii", $accountId, $accountId, $accountId, $accountId, $accountId, $accountId, $accountId);

            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function findWithNames($messageId) {
        try {
            $sql = "SELECT m.*, sender.first_name AS sender_first_name, sender.last_name AS sender_last_name,
                           receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name
                    FROM case_messages m
                    INNER JOIN accounts sender ON m.sender_account_id = sender.account_id
                    INNER JOIN accounts receiver ON m.receiver_account_id = receiver.account_id
                    WHERE m.message_id = ?
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $messageId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_assoc() : null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    public static function supportsAttachments() {
        return self::hasColumn('attachment');
    }

    public static function getRecipientsForCase(array $case, array $currentUser) {
        $recipients = [];
        $currentAccountId = (int) $currentUser['account_id'];

        if ($currentAccountId !== (int) $case['submitted_by_account_id']) {
            $student = self::findAccount((int) $case['submitted_by_account_id']);

            if ($student) {
                $recipients[] = $student;
            }
        }

        foreach (self::getStaffAccounts() as $staff) {
            if ((int) $staff['account_id'] !== $currentAccountId) {
                $recipients[] = $staff;
            }
        }

        return self::uniqueRecipients($recipients);
    }

    public static function canAccessCaseMessages(array $case, array $user) {
        if ((int) $case['submitted_by_account_id'] === (int) $user['account_id']) {
            return true;
        }

        return self::isStaffRole($user['role']);
    }

    public static function canAccessConversation(array $case, array $user) {
        $accountId = (int) $user['account_id'];

        if ((int) $case['submitted_by_account_id'] === $accountId) {
            return true;
        }

        if (!empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] === $accountId) {
            return true;
        }

        return self::hasMessageParticipation((int) $case['complaint_id'], $accountId);
    }

    public static function canStartCaseConversation(array $case, array $user) {
        if ((int) $case['submitted_by_account_id'] === (int) $user['account_id']) {
            return true;
        }

        return self::isStaffRole($user['role']);
    }

    public static function isValidRecipientForCase(array $case, array $sender, $receiverAccountId) {
        foreach (self::getRecipientsForCase($case, $sender) as $recipient) {
            if ((int) $recipient['account_id'] === (int) $receiverAccountId) {
                return true;
            }
        }

        return false;
    }

    public static function accountName($accountId) {
        $account = self::findAccount($accountId);

        if (!$account) {
            return 'User';
        }

        return trim($account['first_name'] . ' ' . $account['last_name']);
    }

    private static function findAccount($accountId) {
        $stmt = self::$conn->prepare("SELECT account_id, first_name, last_name, email, role FROM accounts WHERE account_id = ? LIMIT 1");
        $stmt->bind_param("i", $accountId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }

    private static function getStaffAccounts() {
        $normalizedRoles = array_map(fn($role) => strtolower(str_replace(['_', ' '], '-', $role)), self::$staffRoles);
        $placeholders = implode(', ', array_fill(0, count($normalizedRoles), '?'));
        $sql = "SELECT account_id, first_name, last_name, email, role
                FROM accounts
                WHERE status = 'active' AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ($placeholders)
                ORDER BY first_name, last_name";
        $stmt = self::$conn->prepare($sql);
        $types = str_repeat('s', count($normalizedRoles));
        $stmt->bind_param($types, ...$normalizedRoles);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private static function hasMessageParticipation($complaintId, $accountId) {
        try {
            $stmt = self::$conn->prepare("SELECT message_id FROM case_messages WHERE complaint_id = ? AND (sender_account_id = ? OR receiver_account_id = ?) LIMIT 1");
            $stmt->bind_param("iii", $complaintId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function isStaffRole($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', $role));

        foreach (self::$staffRoles as $staffRole) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $staffRole))) {
                return true;
            }
        }

        return false;
    }

    private static function uniqueRecipients(array $recipients) {
        $unique = [];

        foreach ($recipients as $recipient) {
            $unique[(int) $recipient['account_id']] = $recipient;
        }

        return array_values($unique);
    }

    private static function hasColumn($column) {
        if (array_key_exists($column, self::$columnCache)) {
            return self::$columnCache[$column];
        }

        try {
            $stmt = self::$conn->prepare("SHOW COLUMNS FROM case_messages LIKE ?");
            $stmt->bind_param("s", $column);
            $stmt->execute();
            $result = $stmt->get_result();

            self::$columnCache[$column] = $result && $result->num_rows > 0;
            return self::$columnCache[$column];
        } catch (Throwable $exception) {
            self::$columnCache[$column] = false;
            return false;
        }
    }
}
