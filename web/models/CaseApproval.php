<?php

require_once 'Model.php';

class CaseApproval extends Model {
    protected static $table = 'case_approvals';
    protected static $primaryKey = 'approval_id';

    public static function pendingForHead() {
        $sql = "SELECT a.*, c.case_number, c.complainant_name,
                       requester.first_name AS requester_first_name, requester.last_name AS requester_last_name
                FROM case_approvals a
                INNER JOIN complaints c ON c.complaint_id = a.complaint_id
                LEFT JOIN accounts requester ON requester.account_id = a.requested_by_account_id
                WHERE a.status = 'Pending'
                ORDER BY a.created_at DESC, a.approval_id DESC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function findForCase($approvalId, $complaintId) {
        $stmt = self::$conn->prepare("SELECT a.*, c.case_number FROM case_approvals a INNER JOIN complaints c ON c.complaint_id = a.complaint_id WHERE a.approval_id = ? AND a.complaint_id = ? LIMIT 1");
        if (!$stmt) return null;
        $approvalId = (int) $approvalId;
        $complaintId = (int) $complaintId;
        $stmt->bind_param('ii', $approvalId, $complaintId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function findPendingForCaseAction($complaintId, $requestedBy, $actionType) {
        $stmt = self::$conn->prepare("SELECT * FROM case_approvals WHERE complaint_id = ? AND requested_by_account_id = ? AND action_type = ? AND status = 'Pending' ORDER BY approval_id DESC LIMIT 1");
        if (!$stmt) return null;
        $complaintId = (int) $complaintId;
        $requestedBy = (int) $requestedBy;
        $stmt->bind_param('iis', $complaintId, $requestedBy, $actionType);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function findPendingForRequester($complaintId, $requestedBy) {
        $stmt = self::$conn->prepare("SELECT * FROM case_approvals WHERE complaint_id = ? AND requested_by_account_id = ? AND status = 'Pending' ORDER BY approval_id DESC LIMIT 1");
        if (!$stmt) return null;
        $complaintId = (int) $complaintId;
        $requestedBy = (int) $requestedBy;
        $stmt->bind_param('ii', $complaintId, $requestedBy);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public static function lockForReview($approvalId) {
        $approvalId = (int) $approvalId;
        if ($approvalId <= 0) return false;
        $lockName = 'daris_case_approval_' . $approvalId;
        $stmt = self::$conn->prepare('SELECT GET_LOCK(?, 10) AS acquired');
        if (!$stmt) return false;
        $stmt->bind_param('s', $lockName);
        if (!$stmt->execute()) return false;
        $row = $stmt->get_result()->fetch_assoc();
        return isset($row['acquired']) && (int) $row['acquired'] === 1;
    }

    public static function createForCase($complaintId, $requestedBy, $actionType, $actionLabel, array $payload) {
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR);
        return self::create([
            'complaint_id' => (int) $complaintId,
            'requested_by_account_id' => (int) $requestedBy,
            'action_type' => $actionType,
            'action_label' => $actionLabel,
            'payload' => $payloadJson,
            'status' => 'Pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function decide($approvalId, $reviewedBy, $status, $remarks = null) {
        $stmt = self::$conn->prepare("UPDATE case_approvals SET status = ?, reviewed_by_account_id = ?, review_remarks = ?, reviewed_at = ? WHERE approval_id = ? AND status = 'Pending'");
        if (!$stmt) throw new Exception('Unable to prepare approval decision.');
        $now = date('Y-m-d H:i:s');
        $approvalId = (int) $approvalId;
        $reviewedBy = (int) $reviewedBy;
        $stmt->bind_param('sissi', $status, $reviewedBy, $remarks, $now, $approvalId);
        if (!$stmt->execute()) throw new Exception('Unable to save approval decision.');
        return $stmt->affected_rows > 0;
    }

    public static function description(array $approval) {
        $payload = json_decode($approval['payload'] ?? '', true) ?: [];
        $parts = [];
        $keys = [
            'details', 'remarks', 'outcome', 'activity', 'activity_name', 'progress_status',
            'classification', 'classification_other', 'update_type', 'revision_fields',
            'coordinator_account_id', 'reformation_coordinator_account_id', 'respondent_type',
            'respondent_name', 'respondent_age', 'respondent_gender', 'respondent_student_no',
            'respondent_employee_no', 'respondent_college', 'respondent_course', 'respondent_section',
            'respondent_course_year', 'respondent_position', 'respondent_department',
            'respondent_affiliation', 'respondent_contact', 'respondent_email', 'respondent_address',
            'respondent_details', 'report_title', 'hearing_datetime', 'venue',
        ];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) continue;
            $value = $payload[$key];
            if ($key === 'classification' && strcasecmp((string) $value, 'Others') === 0) {
                $value = trim((string) ($payload['classification_other'] ?? '')) ?: $value;
            }
            if (is_array($value)) {
                $value = implode(', ', array_map(static fn($item) => is_scalar($item) ? (string) $item : '', $value));
            }
            $value = trim((string) $value);
            if ($value === '') continue;
            $parts[] = ucwords(str_replace('_', ' ', $key)) . ': ' . $value;
        }
        if (!empty($payload['_approval_files']) && is_array($payload['_approval_files'])) {
            $filenames = [];
            array_walk_recursive($payload['_approval_files'], function ($file) use (&$filenames) {
                if (is_array($file) && !empty($file['original_filename'])) {
                    $filenames[] = basename((string) $file['original_filename']);
                }
            });
            if (!empty($filenames)) {
                $parts[] = 'Attachments: ' . implode(', ', array_unique($filenames));
            }
        }
        return implode(' · ', $parts) ?: 'Coordinator requested this action for the case.';
    }
}