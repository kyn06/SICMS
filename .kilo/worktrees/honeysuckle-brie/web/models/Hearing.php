<?php

require_once 'Model.php';
require_once 'Case.php';
require_once 'Notification.php';

class Hearing extends Model {
    protected static $table = 'hearings';
    protected static $primaryKey = 'hearing_id';

    public static function getStatuses() {
        return ['Scheduled', 'Cancelled', 'Completed'];
    }

    public static function listHearings(array $user = null) {
        $sql = "SELECT h.*, c.case_number, c.complainant_name, c.case_classification
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id";
        $params = [];
        $types = '';

        if ($user && self::roleKey($user) === 'coordinator') {
            $sql .= " WHERE c.assigned_coordinator_account_id = ?";
            $params[] = (int) $user['account_id'];
            $types .= 'i';
        }

        $sql .= " ORDER BY h.hearing_datetime DESC";
        $stmt = self::$conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getUpcoming($limit = 5) {
        $sql = "SELECT h.*, c.case_number, c.complainant_name
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                WHERE h.status = 'Scheduled' AND h.hearing_datetime >= NOW()
                ORDER BY h.hearing_datetime ASC
                LIMIT ?";
        try {
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (Throwable $exception) {
            return [];
        }

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getSchedulableCases(array $user = null) {
        $sql = "SELECT complaint_id, case_number, complainant_name, case_classification, status
                FROM complaints
                WHERE status = 'Under Investigation'";
        $params = [];
        $types = '';

        if ($user && self::roleKey($user) === 'coordinator') {
            $sql .= " AND assigned_coordinator_account_id = ?";
            $params[] = (int) $user['account_id'];
            $types .= 'i';
        }

        $sql .= "
                ORDER BY submitted_at DESC";
        $stmt = self::$conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function isSchedulableCase($complaintId, array $user = null) {
        $sql = "SELECT complaint_id
                FROM complaints
                WHERE complaint_id = ? AND status = 'Under Investigation'";
        $params = [(int) $complaintId];
        $types = 'i';

        if ($user && self::roleKey($user) === 'coordinator') {
            $sql .= " AND assigned_coordinator_account_id = ?";
            $params[] = (int) $user['account_id'];
            $types .= 'i';
        }

        $sql .= " LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result && $result->num_rows > 0;
    }

    public static function findHearing($hearingId) {
        $sql = "SELECT h.*, c.case_number, c.complainant_name, c.case_classification
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                WHERE h.hearing_id = ?
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param("i", $hearingId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }

    public static function forComplaint($complaintId) {
        $sql = "SELECT h.*, c.case_number, c.complainant_name, c.case_classification
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                WHERE h.complaint_id = ?
                ORDER BY h.hearing_datetime DESC";
        $stmt = self::$conn->prepare($sql);
        $id = (int) $complaintId;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function schedule(array $data) {
        $hearing = parent::create($data);
        if ($hearing) {
            self::notifyHearingChange((int) $hearing['complaint_id'], 'scheduled', $hearing['hearing_datetime'] ?? '');
        }
        return $hearing;
    }

    public static function updateHearing($hearingId, array $data) {
        $hearing = self::findHearing($hearingId);
        $updated = parent::updateById($hearingId, $data);
        if ($updated && $hearing) {
            self::notifyHearingChange(
                (int) $hearing['complaint_id'],
                'updated',
                $data['hearing_datetime'] ?? ($hearing['hearing_datetime'] ?? '')
            );
        }
        return $updated;
    }

    public static function updateStatus($hearingId, $status) {
        $hearing = self::findHearing($hearingId);
        $updated = parent::updateById($hearingId, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($updated && $hearing && in_array($status, ['Cancelled', 'Completed'], true)) {
            self::notifyHearingChange((int) $hearing['complaint_id'], strtolower($status), $hearing['hearing_datetime'] ?? '');
        }
        return $updated;
    }

    /*
     * ---- Respondent hearing views ----
     */

    public static function forRespondent($accountId) {
        $sql = "SELECT h.*, c.case_number
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                INNER JOIN complaint_respondents r ON r.complaint_id = c.complaint_id AND r.account_id = ?
                WHERE (c.case_source IS NULL OR c.case_source <> 'Legacy')
                  AND c.respondent_released_at IS NOT NULL
                ORDER BY h.hearing_datetime DESC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getUpcomingForRespondent($accountId, $limit = 5) {
        $sql = "SELECT h.*, c.case_number
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                INNER JOIN complaint_respondents r ON r.complaint_id = c.complaint_id AND r.account_id = ?
                WHERE (c.case_source IS NULL OR c.case_source <> 'Legacy')
                  AND c.respondent_released_at IS NOT NULL
                  AND h.status = 'Scheduled' AND h.hearing_datetime >= NOW()
                ORDER BY h.hearing_datetime ASC
                LIMIT ?";
        try {
            $stmt = self::$conn->prepare($sql);
            $stmt->bind_param("ii", $accountId, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
        } catch (Throwable $exception) {
            return [];
        }
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /* In-app notification to every linked active respondent on the case's hearings. */
    private static function notifyHearingChange($complaintId, $verb, $scheduleText = '') {
        try {
            $case = CaseRecord::findCase($complaintId);
            if (!$case) return;
            $schedulePart = $scheduleText !== '' ? ' for ' . date('M d, Y h:i A', strtotime($scheduleText)) : '';
            foreach (CaseRecord::linkedRespondentAccounts($complaintId) as $account) {
                Notification::createForUser(
                    (int) $account['account_id'],
                    'hearing_updated',
                    'Hearing ' . ucfirst($verb),
                    'A hearing' . $schedulePart . ' on case ' . $case['case_number'] . ' was ' . $verb . '.',
                    'web/views/respondent/case_show.php?id=' . $complaintId
                );
            }
        } catch (Throwable $exception) {
            return;
        }
    }

    private static function roleKey(array $user) {
        return strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));
    }
}
