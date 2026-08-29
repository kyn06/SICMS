<?php

require_once 'Model.php';

class Message extends Model {
    protected static $table = 'case_messages';
    protected static $primaryKey = 'message_id';

    private static $staffRoles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private static $headRoles = ['head-of-sdru', 'sdru-head', 'head of sdru'];
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

        if ($createdMessage) {
            self::ensureParticipants((int) $complaintId, (int) $senderAccountId, (int) $receiverAccountId);
            self::markPairVisible((int) $complaintId, (int) $senderAccountId, (int) $receiverAccountId);
        }

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
            $threads = [];

            foreach (self::accessibleCases($accountId) as $case) {
                $covered = [];
                $counterparts = self::counterpartsForCase($case, $user);

                foreach ($counterparts as $counterpart) {
                    $covered[] = (int) $counterpart['account_id'];
                }

                foreach (self::explicitCounterparts((int) $case['complaint_id'], $accountId) as $explicit) {
                    if (in_array((int) $explicit['account_id'], $covered, true)) {
                        continue;
                    }

                    $counterparts[] = $explicit;
                }

                foreach ($counterparts as $counterpart) {
                    if (self::isPairHidden((int) $case['complaint_id'], $accountId, (int) $counterpart['account_id'])) {
                        continue;
                    }

                    $summary = self::pairSummary((int) $case['complaint_id'], $accountId, (int) $counterpart['account_id']);

                    $threads[] = array_merge($case, [
                        'counterpart_account_id' => (int) $counterpart['account_id'],
                        'counterpart_first_name' => $counterpart['first_name'],
                        'counterpart_last_name' => $counterpart['last_name'],
                        'counterpart_role' => $counterpart['role'],
                        'latest_message' => $summary['latest_message'],
                        'latest_message_at' => $summary['latest_message_at'],
                        'unread_total' => $summary['unread_total'],
                    ]);
                }
            }

            usort($threads, function ($left, $right) {
                $leftTime = $left['latest_message_at'] ?: $left['submitted_at'];
                $rightTime = $right['latest_message_at'] ?: $right['submitted_at'];

                if ($leftTime === $rightTime) {
                    return (int) $right['complaint_id'] - (int) $left['complaint_id'];
                }

                return strcmp((string) $rightTime, (string) $leftTime);
            });

            return $threads;
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function newConversationCandidates(array $user) {
        try {
            $accountId = (int) $user['account_id'];
            $candidates = [];

            foreach (self::candidateCases($user) as $case) {
                foreach (self::connectedPeopleForCase($case, $user) as $person) {
                    if ((int) $person['account_id'] === $accountId) {
                        continue;
                    }

                    if (self::isThreadVisible((int) $case['complaint_id'], $accountId, (int) $person['account_id'], $case, $user)) {
                        continue;
                    }

                    $candidates[] = array_merge($case, [
                        'counterpart_account_id' => (int) $person['account_id'],
                        'counterpart_first_name' => $person['first_name'],
                        'counterpart_last_name' => $person['last_name'],
                        'counterpart_role' => $person['role'],
                        'latest_message' => null,
                        'latest_message_at' => null,
                        'unread_total' => 0,
                    ]);
                }
            }

            return $candidates;
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function isValidNewConversationCandidate(array $case, array $currentUser, $counterpartAccountId) {
        $counterpartAccountId = (int) $counterpartAccountId;

        foreach (self::connectedPeopleForCase($case, $currentUser) as $person) {
            if ((int) $person['account_id'] === $counterpartAccountId) {
                return !self::isThreadVisible((int) $case['complaint_id'], (int) $currentUser['account_id'], $counterpartAccountId, $case, $currentUser);
            }
        }

        return false;
    }

    public static function openPair($complaintId, $accountId, $counterpartAccountId) {
        self::ensureParticipants((int) $complaintId, (int) $accountId, (int) $counterpartAccountId);
        self::markPairVisible((int) $complaintId, (int) $accountId, (int) $counterpartAccountId);
    }

    public static function forPair($complaintId, $firstAccountId, $secondAccountId) {
        try {
            $sql = "SELECT m.*, sender.first_name AS sender_first_name, sender.last_name AS sender_last_name,
                           receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name
                    FROM case_messages m
                    INNER JOIN accounts sender ON m.sender_account_id = sender.account_id
                    INNER JOIN accounts receiver ON m.receiver_account_id = receiver.account_id
                    WHERE m.complaint_id = ?
                      AND ((m.sender_account_id = ? AND m.receiver_account_id = ?)
                        OR (m.sender_account_id = ? AND m.receiver_account_id = ?))
                      AND NOT EXISTS (
                          SELECT 1 FROM hidden_conversations hc
                          WHERE hc.complaint_id = m.complaint_id
                            AND hc.account_id = ?
                            AND hc.counterpart_account_id = ?
                            AND m.created_at < hc.hidden_at
                      )
                    ORDER BY m.created_at ASC, m.message_id ASC";
            $stmt = self::$conn->prepare($sql);
            $complaintId = (int) $complaintId;
            $firstAccountId = (int) $firstAccountId;
            $secondAccountId = (int) $secondAccountId;
            $stmt->bind_param("iiiiiii", $complaintId, $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId, $firstAccountId, $secondAccountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function markPairMessagesRead($complaintId, $receiverAccountId, $senderAccountId) {
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE case_messages SET is_read = 1, read_at = ? WHERE complaint_id = ? AND receiver_account_id = ? AND sender_account_id = ? AND is_read = 0");
            $stmt->bind_param("siii", $now, $complaintId, $receiverAccountId, $senderAccountId);
            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function hidePair($complaintId, $accountId, $counterpartAccountId) {
        try {
            $stmt = self::$conn->prepare("INSERT IGNORE INTO hidden_conversations (complaint_id, account_id, counterpart_account_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $complaintId, $accountId, $counterpartAccountId);
            $stmt->execute();

            $stmt = self::$conn->prepare("UPDATE hidden_conversations SET visible = 0, hidden_at = ? WHERE complaint_id = ? AND account_id = ? AND counterpart_account_id = ?");
            $hiddenAt = date('Y-m-d H:i:s');
            $stmt->bind_param("siii", $hiddenAt, $complaintId, $accountId, $counterpartAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function ensureParticipants($complaintId, $firstAccountId, $secondAccountId) {
        try {
            $stmt = self::$conn->prepare("INSERT IGNORE INTO conversation_participants (complaint_id, account_id, counterpart_account_id) VALUES (?, ?, ?), (?, ?, ?)");
            $stmt->bind_param("iiiiii", $complaintId, $firstAccountId, $secondAccountId, $complaintId, $secondAccountId, $firstAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function markPairVisible($complaintId, $firstAccountId, $secondAccountId) {
        try {
            $stmt = self::$conn->prepare("INSERT IGNORE INTO hidden_conversations (complaint_id, account_id, counterpart_account_id, hidden_at) VALUES (?, ?, ?, '1000-01-01 00:00:00'), (?, ?, ?, '1000-01-01 00:00:00')");
            $stmt->bind_param("iiiiii", $complaintId, $firstAccountId, $secondAccountId, $complaintId, $secondAccountId, $firstAccountId);
            $stmt->execute();

            $stmt = self::$conn->prepare("UPDATE hidden_conversations SET visible = 1 WHERE complaint_id = ? AND ((account_id = ? AND counterpart_account_id = ?) OR (account_id = ? AND counterpart_account_id = ?))");
            $stmt->bind_param("iiiii", $complaintId, $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function isPairHidden($complaintId, $accountId, $counterpartAccountId) {
        try {
            $stmt = self::$conn->prepare("SELECT hidden_id FROM hidden_conversations WHERE complaint_id = ? AND account_id = ? AND counterpart_account_id = ? AND visible = 0 LIMIT 1");
            $stmt->bind_param("iii", $complaintId, $accountId, $counterpartAccountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function counterpartsForCase(array $case, array $currentUser) {
        $currentAccountId = (int) $currentUser['account_id'];
        $complaintId = (int) $case['complaint_id'];
        $counterparts = [];

        if (self::isHeadRole($currentUser['role'])) {
            return $counterparts;
        }

        if ($currentAccountId === (int) $case['submitted_by_account_id']) {
            if (!empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] !== $currentAccountId) {
                $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

                if ($coordinator) {
                    $counterparts[] = $coordinator;
                }
            }

            foreach (self::staffWithHistoryOnCase($complaintId, $currentAccountId) as $staff) {
                $counterparts[] = $staff;
            }

            if (empty($counterparts)) {
                foreach (self::getStaffAccounts() as $staff) {
                    if ((int) $staff['account_id'] !== $currentAccountId) {
                        $counterparts[] = $staff;
                        break;
                    }
                }
            }

            return self::uniqueRecipients($counterparts);
        }

        $student = self::findAccount((int) $case['submitted_by_account_id']);

        if ($student) {
            $counterparts[] = $student;
        }

        if (!empty($case['assigned_coordinator_account_id']) && (int) $case['assigned_coordinator_account_id'] !== $currentAccountId) {
            $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

            if ($coordinator) {
                $counterparts[] = $coordinator;
            }
        }

        return self::uniqueRecipients($counterparts);
    }

    public static function defaultCounterpartForCase(array $case, array $currentUser) {
        $counterparts = self::counterpartsForCase($case, $currentUser);

        if (!empty($counterparts)) {
            return $counterparts[0];
        }

        foreach (self::connectedPeopleForCase($case, $currentUser) as $person) {
            if ((int) $person['account_id'] !== (int) $currentUser['account_id']) {
                return $person;
            }
        }

        return null;
    }

    private static function accessibleCases($accountId) {
        try {
            $sql = "SELECT c.complaint_id, c.case_number, c.complainant_name, c.case_classification,
                           c.status, c.submitted_at, c.submitted_by_account_id,
                           c.assigned_coordinator_account_id,
                           submitter.first_name AS submitter_first_name,
                           submitter.last_name AS submitter_last_name,
                           submitter.role AS submitter_role,
                           coordinator.first_name AS coordinator_first_name,
                           coordinator.last_name AS coordinator_last_name,
                           coordinator.role AS coordinator_role
                    FROM complaints c
                    LEFT JOIN accounts submitter ON c.submitted_by_account_id = submitter.account_id
                    LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                    WHERE (c.submitted_by_account_id = ?
                       OR c.assigned_coordinator_account_id = ?
                       OR EXISTS (
                          SELECT 1
                          FROM case_messages participant_messages
                          WHERE participant_messages.complaint_id = c.complaint_id
                            AND (participant_messages.sender_account_id = ? OR participant_messages.receiver_account_id = ?)
                       )
                       OR EXISTS (
                          SELECT 1
                          FROM conversation_participants cp
                          WHERE cp.complaint_id = c.complaint_id
                            AND cp.account_id = ?
                       ))
                    ORDER BY c.complaint_id DESC";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("iiiii", $accountId, $accountId, $accountId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function staffWithHistoryOnCase($complaintId, $accountId) {
        try {
            $sql = "SELECT DISTINCT other.account_id, other.first_name, other.last_name, other.role
                    FROM case_messages cm
                    INNER JOIN accounts other
                        ON other.account_id = CASE WHEN cm.sender_account_id = ? THEN cm.receiver_account_id ELSE cm.sender_account_id END
                    WHERE cm.complaint_id = ?
                      AND (cm.sender_account_id = ? OR cm.receiver_account_id = ?)
                      AND other.account_id <> ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("iiiii", $accountId, $complaintId, $accountId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $others = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

            return array_values(array_filter($others, fn($account) => self::isStaffRole($account['role'])));
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function pairSummary($complaintId, $accountId, $otherAccountId) {
        try {
            $sql = "SELECT m.message AS latest_message, m.created_at AS latest_message_at,
                           (SELECT COUNT(*)
                            FROM case_messages unread_messages
                            WHERE unread_messages.complaint_id = ?
                              AND unread_messages.sender_account_id = ?
                              AND unread_messages.receiver_account_id = ?
                              AND unread_messages.is_read = 0
                              AND NOT EXISTS (
                                  SELECT 1 FROM hidden_conversations hc2
                                  WHERE hc2.complaint_id = unread_messages.complaint_id
                                    AND hc2.account_id = ?
                                    AND hc2.counterpart_account_id = ?
                                    AND unread_messages.created_at < hc2.hidden_at
                              )) AS unread_total
                    FROM case_messages m
                    WHERE m.complaint_id = ?
                      AND ((m.sender_account_id = ? AND m.receiver_account_id = ?)
                        OR (m.sender_account_id = ? AND m.receiver_account_id = ?))
                      AND NOT EXISTS (
                          SELECT 1 FROM hidden_conversations hc
                          WHERE hc.complaint_id = m.complaint_id
                            AND hc.account_id = ?
                            AND hc.counterpart_account_id = ?
                            AND m.created_at < hc.hidden_at
                      )
                    ORDER BY m.message_id DESC
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("iiiiiiiiiiii", $complaintId, $otherAccountId, $accountId, $accountId, $otherAccountId, $complaintId, $accountId, $otherAccountId, $otherAccountId, $accountId, $accountId, $otherAccountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result ? $result->fetch_assoc() : null;

            return [
                'latest_message' => $row['latest_message'] ?? null,
                'latest_message_at' => $row['latest_message_at'] ?? null,
                'unread_total' => (int) ($row['unread_total'] ?? 0),
            ];
        } catch (Throwable $exception) {
            return ['latest_message' => null, 'latest_message_at' => null, 'unread_total' => 0];
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
        return self::counterpartsForCase($case, $currentUser);
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

        if (self::isHeadRole($user['role'])) {
            return true;
        }

        if (self::hasParticipantRow((int) $case['complaint_id'], $accountId)) {
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
        $receiverAccountId = (int) $receiverAccountId;

        foreach (self::getRecipientsForCase($case, $sender) as $recipient) {
            if ((int) $recipient['account_id'] === $receiverAccountId) {
                return true;
            }
        }

        return self::hasParticipantRow((int) $case['complaint_id'], (int) $sender['account_id'], $receiverAccountId);
    }

    public static function accountName($accountId) {
        $account = self::findAccount($accountId);

        if (!$account) {
            return 'User';
        }

        return trim($account['first_name'] . ' ' . $account['last_name']);
    }

    public static function counterpartAccount($accountId) {
        return self::findAccount((int) $accountId);
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

    private static function isHeadRole($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', $role));

        foreach (self::$headRoles as $headRole) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $headRole))) {
                return true;
            }
        }

        return false;
    }

    private static function candidateCases(array $user) {
        if (self::isHeadRole($user['role'])) {
            return self::allCases();
        }

        return self::accessibleCases((int) $user['account_id']);
    }

    private static function allCases() {
        try {
            $sql = "SELECT c.complaint_id, c.case_number, c.complainant_name, c.case_classification,
                           c.status, c.submitted_at, c.submitted_by_account_id,
                           c.assigned_coordinator_account_id,
                           submitter.first_name AS submitter_first_name,
                           submitter.last_name AS submitter_last_name,
                           submitter.role AS submitter_role,
                           coordinator.first_name AS coordinator_first_name,
                           coordinator.last_name AS coordinator_last_name,
                           coordinator.role AS coordinator_role
                    FROM complaints c
                    LEFT JOIN accounts submitter ON c.submitted_by_account_id = submitter.account_id
                    LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                    ORDER BY c.complaint_id DESC";
            $result = self::$conn->query($sql);

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function explicitCounterparts($complaintId, $accountId) {
        try {
            $sql = "SELECT DISTINCT other.account_id, other.first_name, other.last_name, other.role
                    FROM conversation_participants cp
                    INNER JOIN accounts other ON other.account_id = cp.counterpart_account_id
                    WHERE cp.complaint_id = ? AND cp.account_id = ? AND other.account_id <> ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("iii", $complaintId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function connectedPeopleForCase(array $case, array $currentUser) {
        $people = [];

        $student = self::findAccount((int) $case['submitted_by_account_id']);

        if ($student) {
            $people[] = $student;
        }

        if (!empty($case['assigned_coordinator_account_id'])) {
            $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

            if ($coordinator) {
                $people[] = $coordinator;
            }
        }

        foreach (self::getHeadAccounts() as $head) {
            $people[] = $head;
        }

        foreach (self::caseParticipants((int) $case['complaint_id']) as $participant) {
            $people[] = $participant;
        }

        return self::uniqueRecipients($people);
    }

    private static function getHeadAccounts() {
        try {
            $normalizedRoles = array_map(fn($role) => strtolower(str_replace(['_', ' '], '-', $role)), self::$headRoles);
            $placeholders = implode(', ', array_fill(0, count($normalizedRoles), '?'));
            $sql = "SELECT account_id, first_name, last_name, email, role
                    FROM accounts
                    WHERE status = 'active' AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ($placeholders)";
            $stmt = self::$conn->prepare($sql);
            $types = str_repeat('s', count($normalizedRoles));
            $stmt->bind_param($types, ...$normalizedRoles);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function caseParticipants($complaintId) {
        try {
            $sql = "SELECT DISTINCT other.account_id, other.first_name, other.last_name, other.role
                    FROM conversation_participants cp
                    INNER JOIN accounts other ON other.account_id = cp.counterpart_account_id
                    WHERE cp.complaint_id = ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $complaintId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function hasParticipantRow($complaintId, $accountId, $counterpartAccountId = null) {
        try {
            if ($counterpartAccountId === null) {
                $stmt = self::$conn->prepare("SELECT participant_id FROM conversation_participants WHERE complaint_id = ? AND account_id = ? LIMIT 1");
                $stmt->bind_param("ii", $complaintId, $accountId);
            } else {
                $stmt = self::$conn->prepare("SELECT participant_id FROM conversation_participants WHERE complaint_id = ? AND ((account_id = ? AND counterpart_account_id = ?) OR (account_id = ? AND counterpart_account_id = ?)) LIMIT 1");
                $stmt->bind_param("iiiii", $complaintId, $accountId, $counterpartAccountId, $counterpartAccountId, $accountId);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function isThreadVisible($complaintId, $accountId, $counterpartAccountId, array $case, array $user) {
        if (self::isPairHidden($complaintId, $accountId, $counterpartAccountId)) {
            return false;
        }

        foreach (self::counterpartsForCase($case, $user) as $counterpart) {
            if ((int) $counterpart['account_id'] === $counterpartAccountId) {
                return true;
            }
        }

        foreach (self::explicitCounterparts($complaintId, $accountId) as $explicit) {
            if ((int) $explicit['account_id'] === $counterpartAccountId) {
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
