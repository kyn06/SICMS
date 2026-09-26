<?php

require_once 'Model.php';

class Message extends Model {
    protected static $table = 'case_messages';
    protected static $primaryKey = 'message_id';

    private static $staffRoles = ['admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
    private static $headRoles = ['head-of-sdru', 'sdru-head', 'head of sdru'];
    private static $columnCache = [];

    /* -----------------------------------------------------------------
     * Person-level conversation API
     * One thread per account pair, stored with complaint_id = 0.
     * ----------------------------------------------------------------- */

    public static function createMessage($senderAccountId, $receiverAccountId, $message, $attachment = null) {
        $now = date('Y-m-d H:i:s');
        $data = [
            'complaint_id' => 0,
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
            self::ensureParticipants((int) $senderAccountId, (int) $receiverAccountId);
            self::markPairVisible((int) $senderAccountId, (int) $receiverAccountId);
        }

        return $createdMessage ? self::findWithNames((int) $createdMessage['message_id']) : null;
    }

    public static function forPair($firstAccountId, $secondAccountId) {
        try {
            $sql = "SELECT m.*, sender.first_name AS sender_first_name, sender.last_name AS sender_last_name,
                           receiver.first_name AS receiver_first_name, receiver.last_name AS receiver_last_name
                    FROM case_messages m
                    INNER JOIN accounts sender ON m.sender_account_id = sender.account_id
                    INNER JOIN accounts receiver ON m.receiver_account_id = receiver.account_id
                    WHERE m.complaint_id = 0
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
            $firstAccountId = (int) $firstAccountId;
            $secondAccountId = (int) $secondAccountId;
            $stmt->bind_param("iiiiii", $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId, $firstAccountId, $secondAccountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function markPairMessagesRead($receiverAccountId, $senderAccountId) {
        try {
            $receiverId = (int) $receiverAccountId;
            $senderId = (int) $senderAccountId;
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE case_messages SET is_read = 1, read_at = ? WHERE complaint_id = 0 AND receiver_account_id = ? AND sender_account_id = ? AND is_read = 0");
            $stmt->bind_param("sii", $now, $receiverId, $senderId);
            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function markAllRead($accountId) {
        try {
            $accountId = (int) $accountId;
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE case_messages SET is_read = 1, read_at = ? WHERE receiver_account_id = ? AND is_read = 0");
            $stmt->bind_param("si", $now, $accountId);
            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function threadsForUser(array $user) {
        try {
            $accountId = (int) $user['account_id'];
            $counterparts = self::distinctCounterpartsForAccount($accountId);

            if (empty($counterparts) && !self::isStaffRole($user['role'])) {
                $defaultStaff = self::isRespondentRole($user['role'])
                    ? self::defaultStaffForRespondent($accountId)
                    : self::defaultStaffForStudent($accountId);

                foreach ($defaultStaff as $staff) {
                    $counterparts[] = $staff;
                }
            }

            $threads = [];
            $seen = [];

            foreach ($counterparts as $counterpart) {
                $peerId = (int) $counterpart['account_id'];

                if ($peerId === $accountId || in_array($peerId, $seen, true)) {
                    continue;
                }

                if (self::isAssignmentRole($user['role'])) {
                    $counterpartRoleKey = strtolower((string) ($counterpart['role'] ?? ''));

                    if ($counterpartRoleKey === 'student' && !self::studentAssignedToStaff($user['role'], $peerId, $accountId)) {
                        continue;
                    }

                    if ($counterpartRoleKey === 'respondent' && !self::respondentAssignedToStaff($user['role'], $peerId, $accountId)) {
                        continue;
                    }
                }

                if (self::isPairHidden($accountId, $peerId)) {
                    continue;
                }

                $seen[] = $peerId;
                $summary = self::pairSummary($accountId, $peerId);

                $threads[] = [
                    'counterpart_account_id' => $peerId,
                    'counterpart_first_name' => $counterpart['first_name'],
                    'counterpart_last_name' => $counterpart['last_name'],
                    'counterpart_role' => $counterpart['role'],
                    'latest_message' => $summary['latest_message'],
                    'latest_message_at' => $summary['latest_message_at'],
                    'unread_total' => $summary['unread_total'],
                ];
            }

            usort($threads, function ($left, $right) {
                return strcmp((string) ($right['latest_message_at'] ?? ''), (string) ($left['latest_message_at'] ?? ''));
            });

            return $threads;
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function newConversationCandidates(array $user) {
        if (!self::isStaffRole($user['role'])) {
            return [];
        }

        try {
            $accountId = (int) $user['account_id'];
            $exclude = [];

            foreach (self::distinctCounterpartsForAccount($accountId) as $peer) {
                $peerId = (int) $peer['account_id'];

                if ($peerId !== $accountId && !self::isPairHidden($accountId, $peerId)) {
                    $exclude[] = $peerId;
                }
            }

            $candidates = [];

            foreach (self::activeStudentAccounts() as $student) {
                $studentId = (int) $student['account_id'];

                if ($studentId === $accountId || in_array($studentId, $exclude, true)) {
                    continue;
                }

                if (self::isAssignmentRole($user['role']) && !self::studentAssignedToStaff($user['role'], $studentId, $accountId)) {
                    continue;
                }

                $candidates[] = array_merge([
                    'counterpart_account_id' => $studentId,
                    'counterpart_first_name' => $student['first_name'],
                    'counterpart_last_name' => $student['last_name'],
                    'counterpart_role' => $student['role'],
                    'cases' => self::casesForComplainant($studentId),
                ]);
            }

            foreach (self::activeStaffAccounts() as $staff) {
                $staffId = (int) $staff['account_id'];

                if ($staffId === $accountId || in_array($staffId, $exclude, true)) {
                    continue;
                }

                $candidates[] = [
                    'counterpart_account_id' => $staffId,
                    'counterpart_first_name' => $staff['first_name'],
                    'counterpart_last_name' => $staff['last_name'],
                    'counterpart_role' => $staff['role'],
                    'cases' => [],
                ];
            }

            $respondentPool = self::isAssignmentRole($user['role'])
                ? self::respondentAccountsForStaff($accountId, $user['role'])
                : self::allActiveRespondentAccounts();

            foreach ($respondentPool as $respondent) {
                $respondentId = (int) $respondent['account_id'];

                if ($respondentId === $accountId || in_array($respondentId, $exclude, true)) {
                    continue;
                }

                $candidates[] = [
                    'counterpart_account_id' => $respondentId,
                    'counterpart_first_name' => $respondent['first_name'],
                    'counterpart_last_name' => $respondent['last_name'],
                    'counterpart_role' => $respondent['role'],
                    'cases' => self::casesForRespondent($respondentId),
                ];
            }

            usort($candidates, function ($left, $right) {
                return strcmp(
                    strtolower($left['counterpart_last_name'] . ' ' . $left['counterpart_first_name']),
                    strtolower($right['counterpart_last_name'] . ' ' . $right['counterpart_first_name'])
                );
            });

            return $candidates;
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function startThread($firstAccountId, $secondAccountId) {
        self::ensureParticipants((int) $firstAccountId, (int) $secondAccountId);
        self::markPairVisible((int) $firstAccountId, (int) $secondAccountId);
    }

    public static function hidePair($accountId, $peerAccountId) {
        try {
            $accountId = (int) $accountId;
            $peerAccountId = (int) $peerAccountId;
            $stmt = self::$conn->prepare("INSERT IGNORE INTO hidden_conversations (complaint_id, account_id, counterpart_account_id) VALUES (0, ?, ?)");
            $stmt->bind_param("ii", $accountId, $peerAccountId);
            $stmt->execute();

            $stmt = self::$conn->prepare("UPDATE hidden_conversations SET visible = 0, hidden_at = ? WHERE complaint_id = 0 AND account_id = ? AND counterpart_account_id = ?");
            $hiddenAt = date('Y-m-d H:i:s');
            $stmt->bind_param("sii", $hiddenAt, $accountId, $peerAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function isValidMessagingPeer(array $currentUser, $peerAccountId) {
        $peer = self::findAccount((int) $peerAccountId);

        if (!$peer || ($peer['status'] ?? '') !== 'active') {
            return null;
        }

        if (self::isStaffRole($currentUser['role'])) {
            return $peer;
        }

        return self::isStaffRole($peer['role']) ? $peer : null;
    }

    public static function accountThreadExists($accountId, $peerAccountId) {
        try {
            $accountId = (int) $accountId;
            $peerAccountId = (int) $peerAccountId;

            if (self::hasParticipantRow($accountId, $peerAccountId)) {
                return true;
            }

            $stmt = self::$conn->prepare("SELECT message_id FROM case_messages WHERE complaint_id = 0 AND ((sender_account_id = ? AND receiver_account_id = ?) OR (sender_account_id = ? AND receiver_account_id = ?)) LIMIT 1");
            $stmt->bind_param("iiii", $accountId, $peerAccountId, $peerAccountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    public static function canStartThread(array $currentUser, $peerAccountId) {
        if (!self::isValidMessagingPeer($currentUser, $peerAccountId)) {
            return false;
        }

        $peer = self::findAccount((int) $peerAccountId);

        if ($peer && self::isAssignmentRole($currentUser['role'])
            && strtolower((string) ($peer['role'] ?? '')) === 'student'
            && !self::studentAssignedToStaff($currentUser['role'], (int) $peerAccountId, (int) $currentUser['account_id'])) {
            return false;
        }

        $accountId = (int) $currentUser['account_id'];
        $peerAccountId = (int) $peerAccountId;

        if (!self::accountThreadExists($accountId, $peerAccountId)) {
            return true;
        }

        return self::isPairHidden($accountId, $peerAccountId);
    }

    public static function casesForComplainant($accountId) {
        try {
            $accountId = (int) $accountId;
            $sql = "SELECT c.complaint_id, c.case_number, c.case_classification, c.status,
                           coordinator.first_name AS coord_first_name,
                           coordinator.last_name AS coord_last_name
                    FROM complaints c
                    LEFT JOIN accounts coordinator ON coordinator.account_id = c.assigned_coordinator_account_id
                    WHERE c.submitted_by_account_id = ?
                    ORDER BY c.complaint_id DESC";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function casesForRespondent($accountId) {
        try {
            $accountId = (int) $accountId;
            $sql = "SELECT c.complaint_id, c.case_number, c.case_classification, c.status,
                           coordinator.first_name AS coord_first_name,
                           coordinator.last_name AS coord_last_name
                    FROM complaint_respondents r
                    INNER JOIN complaints c ON c.complaint_id = r.complaint_id
                    LEFT JOIN accounts coordinator ON coordinator.account_id = c.assigned_coordinator_account_id
                    WHERE r.account_id = ?
                      AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy'
                    ORDER BY c.complaint_id DESC";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function studentAssignedToStaff($staffRole, $studentId, $staffId) {
        try {
            $studentId = (int) $studentId;
            $staffId = (int) $staffId;
            $column = self::assignmentColumn($staffRole);
            $sql = "SELECT complaint_id
                    FROM complaints
                    WHERE submitted_by_account_id = ?
                      AND $column = ?
                      AND COALESCE(case_source, 'Online Submission') <> 'Legacy'
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("ii", $studentId, $staffId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function respondentAssignedToStaff($staffRole, $respondentId, $staffId) {
        try {
            $respondentId = (int) $respondentId;
            $staffId = (int) $staffId;
            $column = self::assignmentColumn($staffRole);
            $sql = "SELECT r.complaint_id
                    FROM complaint_respondents r
                    INNER JOIN complaints c ON c.complaint_id = r.complaint_id
                    WHERE r.account_id = ?
                      AND c.$column = ?
                      AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy'
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("ii", $respondentId, $staffId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function caseAssignedToStaff(array $case, $staffRole, $staffId) {
        $column = self::assignmentColumn($staffRole);
        return !empty($case[$column])
            && (int) $case[$column] === (int) $staffId;
    }

    private static function isAssignmentRole($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));
        return $roleKey === 'coordinator' || $roleKey === 'reformation-coordinator';
    }

    private static function assignmentColumn($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));
        return $roleKey === 'reformation-coordinator' ? 'assigned_reformation_coordinator_account_id' : 'assigned_coordinator_account_id';
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

    public static function accountName($accountId) {
        $account = self::findAccount($accountId);

        if (!$account) {
            return 'User';
        }

        return trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''));
    }

    public static function counterpartAccount($accountId) {
        return self::findAccount((int) $accountId);
    }

    public static function canAccessCaseMessages(array $case, array $user) {
        if ((int) $case['submitted_by_account_id'] === (int) $user['account_id']) {
            return true;
        }

        if (!self::isStaffRole($user['role'])) {
            return false;
        }

        $roleKey = strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));

        if (self::isAssignmentRole($user['role'] ?? '')) {
            return self::caseAssignedToStaff($case, $user['role'] ?? '', $user['account_id'] ?? 0);
        }

        return true;
    }

    public static function defaultCounterpartForCase(array $case, array $currentUser) {
        if (!self::isStaffRole($currentUser['role'])) {
            $studentId = (int) $case['submitted_by_account_id'];

            if (!empty($case['assigned_coordinator_account_id'])
                && (int) $case['assigned_coordinator_account_id'] !== (int) $currentUser['account_id']) {
                $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

                if ($coordinator && ($coordinator['status'] ?? '') === 'active') {
                    return $coordinator;
                }
            }

            foreach (self::staffWithHistoryOnCase((int) $case['complaint_id'], $studentId) as $staff) {
                return $staff;
            }

            foreach (self::getHeadAccounts() as $head) {
                if ((int) $head['account_id'] !== (int) $currentUser['account_id']) {
                    return $head;
                }
            }

            foreach (self::getStaffAccounts() as $staff) {
                if ((int) $staff['account_id'] !== (int) $currentUser['account_id']) {
                    return $staff;
                }
            }

            return null;
        }

        $student = self::findAccount((int) $case['submitted_by_account_id']);

        if (!$student || ($student['status'] ?? '') !== 'active') {
            return null;
        }

        $roleKey = strtolower(str_replace(['_', ' '], '-', $currentUser['role'] ?? ''));

        if (self::isAssignmentRole($currentUser['role'] ?? '')
            && !self::caseAssignedToStaff($case, $currentUser['role'] ?? '', $currentUser['account_id'] ?? 0)) {
            return null;
        }

        return $student;
    }

    public static function isStaffRole($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));

        foreach (self::$staffRoles as $staffRole) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $staffRole))) {
                return true;
            }
        }

        return false;
    }

    public static function isCoordinatorRole($role) {
        return strtolower(str_replace(['_', ' '], '-', (string) $role)) === 'coordinator';
    }

    public static function isRespondentRole($role) {
        return strtolower(str_replace(['_', ' '], '-', (string) $role)) === 'respondent';
    }

    public static function canStartConversations(array $user) {
        return self::isStaffRole($user['role']);
    }

    public static function isHeadRole($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));

        foreach (self::$headRoles as $headRole) {
            if ($roleKey === strtolower(str_replace(['_', ' '], '-', $headRole))) {
                return true;
            }
        }

        return false;
    }

    public static function roleLabel($role) {
        $roleKey = strtolower(str_replace(['_', ' '], '-', (string) $role));

        return match ($roleKey) {
            'head-of-sdru', 'sdru-head' => 'Head SDRU',
            'sdr-staff', 'sdru-staff' => 'Staff',
            'admin' => 'Administrator',
            'student' => 'Student',
            default => ucwords(str_replace('-', ' ', $roleKey)),
        };
    }

    /* -----------------------------------------------------------------
     * Internals
     * ----------------------------------------------------------------- */

    private static function respondentAccountsForStaff($staffId, $staffRole) {
        try {
            $staffId = (int) $staffId;
            $column = self::assignmentColumn($staffRole);
            $sql = "SELECT DISTINCT acct.account_id, acct.first_name, acct.last_name, acct.email, acct.role
                    FROM complaint_respondents r
                    INNER JOIN complaints c ON c.complaint_id = r.complaint_id
                    INNER JOIN accounts acct ON acct.account_id = r.account_id
                    WHERE c.$column = ?
                      AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy'
                      AND acct.status = 'active'
                    ORDER BY acct.first_name, acct.last_name";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $staffId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function allActiveRespondentAccounts() {
        try {
            $sql = "SELECT account_id, first_name, last_name, email, role
                    FROM accounts
                    WHERE status = 'active' AND LOWER(role) = 'respondent'
                    ORDER BY first_name, last_name";
            $result = self::$conn->query($sql);

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function distinctCounterpartsForAccount($accountId) {
        try {
            $sql = "SELECT DISTINCT o.account_id, o.first_name, o.last_name, o.role
                    FROM (
                        SELECT cp.counterpart_account_id AS other_id
                        FROM conversation_participants cp
                        WHERE cp.complaint_id = 0 AND cp.account_id = ?
                        UNION
                        SELECT cp.account_id AS other_id
                        FROM conversation_participants cp
                        WHERE cp.complaint_id = 0 AND cp.counterpart_account_id = ?
                        UNION
                        SELECT cm.sender_account_id AS other_id
                        FROM case_messages cm
                        WHERE cm.complaint_id = 0 AND cm.receiver_account_id = ?
                        UNION
                        SELECT cm.receiver_account_id AS other_id
                        FROM case_messages cm
                        WHERE cm.complaint_id = 0 AND cm.sender_account_id = ?
                    ) pair_pool
                    INNER JOIN accounts o ON o.account_id = pair_pool.other_id
                    WHERE o.account_id <> ? AND o.status = 'active'
                    ORDER BY o.first_name, o.last_name";
            $stmt = self::$conn->prepare($sql);
            $accountId = (int) $accountId;
            $stmt->bind_param("iiiii", $accountId, $accountId, $accountId, $accountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function activeStudentAccounts() {
        try {
            $sql = "SELECT account_id, first_name, last_name, email, role
                    FROM accounts
                    WHERE status = 'active' AND LOWER(role) = 'student'
                    ORDER BY first_name, last_name";
            $result = self::$conn->query($sql);

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function activeStaffAccounts() {
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

    private static function defaultStaffForStudent($accountId) {
        try {
            $accountId = (int) $accountId;
            $sql = "SELECT complaint_id, assigned_coordinator_account_id
                    FROM complaints
                    WHERE submitted_by_account_id = ?
                    ORDER BY complaint_id DESC
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $case = $result ? $result->fetch_assoc() : null;

            if ($case && !empty($case['assigned_coordinator_account_id'])) {
                $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

                if ($coordinator && ($coordinator['status'] ?? '') === 'active') {
                    return [$coordinator];
                }
            }

            $heads = self::getHeadAccounts();

            if (!empty($heads)) {
                return [$heads[0]];
            }

            return self::getStaffAccounts();
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function defaultStaffForRespondent($accountId) {
        try {
            $accountId = (int) $accountId;
            $sql = "SELECT c.complaint_id, c.assigned_coordinator_account_id, c.assigned_reformation_coordinator_account_id
                    FROM complaint_respondents r
                    INNER JOIN complaints c ON c.complaint_id = r.complaint_id
                    WHERE r.account_id = ?
                      AND (c.assigned_coordinator_account_id IS NOT NULL
                           OR c.assigned_reformation_coordinator_account_id IS NOT NULL)
                      AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy'
                    ORDER BY c.complaint_id DESC
                    LIMIT 1";
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();
            $case = $result ? $result->fetch_assoc() : null;

            if ($case && !empty($case['assigned_coordinator_account_id'])) {
                $coordinator = self::findAccount((int) $case['assigned_coordinator_account_id']);

                if ($coordinator && ($coordinator['status'] ?? '') === 'active') {
                    return [$coordinator];
                }
            }

            if ($case && !empty($case['assigned_reformation_coordinator_account_id'])) {
                $refCoordinator = self::findAccount((int) $case['assigned_reformation_coordinator_account_id']);

                if ($refCoordinator && ($refCoordinator['status'] ?? '') === 'active') {
                    return [$refCoordinator];
                }
            }

            $heads = self::getHeadAccounts();

            if (!empty($heads)) {
                return [$heads[0]];
            }

            return self::getStaffAccounts();
        } catch (Throwable $exception) {
            return [];
        }
    }

    private static function ensureParticipants($firstAccountId, $secondAccountId) {
        try {
            $firstAccountId = (int) $firstAccountId;
            $secondAccountId = (int) $secondAccountId;
            $stmt = self::$conn->prepare("INSERT IGNORE INTO conversation_participants (complaint_id, account_id, counterpart_account_id) VALUES (0, ?, ?), (0, ?, ?)");
            $stmt->bind_param("iiii", $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function markPairVisible($firstAccountId, $secondAccountId) {
        try {
            $firstAccountId = (int) $firstAccountId;
            $secondAccountId = (int) $secondAccountId;
            $stmt = self::$conn->prepare("INSERT IGNORE INTO hidden_conversations (complaint_id, account_id, counterpart_account_id, hidden_at) VALUES (0, ?, ?, '1000-01-01 00:00:00'), (0, ?, ?, '1000-01-01 00:00:00')");
            $stmt->bind_param("iiii", $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId);
            $stmt->execute();

            $stmt = self::$conn->prepare("UPDATE hidden_conversations SET visible = 1 WHERE complaint_id = 0 AND ((account_id = ? AND counterpart_account_id = ?) OR (account_id = ? AND counterpart_account_id = ?))");
            $stmt->bind_param("iiii", $firstAccountId, $secondAccountId, $secondAccountId, $firstAccountId);

            return $stmt->execute();
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function isPairHidden($accountId, $peerAccountId) {
        try {
            $accountId = (int) $accountId;
            $peerAccountId = (int) $peerAccountId;
            $stmt = self::$conn->prepare("SELECT hidden_id FROM hidden_conversations WHERE complaint_id = 0 AND account_id = ? AND counterpart_account_id = ? AND visible = 0 LIMIT 1");
            $stmt->bind_param("ii", $accountId, $peerAccountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function pairSummary($accountId, $otherAccountId) {
        try {
            $sql = "SELECT m.message AS latest_message, m.created_at AS latest_message_at,
                           (SELECT COUNT(*)
                            FROM case_messages unread_messages
                            WHERE unread_messages.complaint_id = 0
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
                    WHERE m.complaint_id = 0
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
            $stmt->bind_param("iiiiiiiiii", $otherAccountId, $accountId, $accountId, $otherAccountId, $accountId, $otherAccountId, $otherAccountId, $accountId, $accountId, $otherAccountId);
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

    private static function hasParticipantRow($accountId, $peerAccountId) {
        try {
            $accountId = (int) $accountId;
            $peerAccountId = (int) $peerAccountId;
            $stmt = self::$conn->prepare("SELECT participant_id FROM conversation_participants WHERE complaint_id = 0 AND ((account_id = ? AND counterpart_account_id = ?) OR (account_id = ? AND counterpart_account_id = ?)) LIMIT 1");
            $stmt->bind_param("iiii", $accountId, $peerAccountId, $peerAccountId, $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result && $result->num_rows > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }

    private static function findAccount($accountId) {
        try {
            $accountId = (int) $accountId;
            $stmt = self::$conn->prepare("SELECT account_id, first_name, last_name, email, role, status FROM accounts WHERE account_id = ? LIMIT 1");
            $stmt->bind_param("i", $accountId);
            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_assoc() : null;
        } catch (Throwable $exception) {
            return null;
        }
    }

    private static function staffWithHistoryOnCase($complaintId, $accountId) {
        try {
            $complaintId = (int) $complaintId;
            $accountId = (int) $accountId;
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