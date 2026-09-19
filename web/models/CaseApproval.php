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

    public static function createForCase($complaintId, $requestedBy, $actionType, $actionLabel, array $payload) {
        return self::create([
            'complaint_id' => (int) $complaintId,
            'requested_by_account_id' => (int) $requestedBy,
            'action_type' => $actionType,
            'action_label' => $actionLabel,
            'payload' => json_encode($payload),
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
        foreach (['details', 'remarks', 'outcome', 'activity', 'progress_status', 'classification', 'respondent_type', 'respondent_name', 'respondent_age', 'respondent_gender', 'respondent_student_no', 'respondent_employee_no', 'respondent_college', 'respondent_course', 'respondent_section', 'respondent_course_year', 'respondent_position', 'respondent_department', 'respondent_affiliation', 'respondent_contact', 'respondent_email', 'respondent_address', 'respondent_details', 'hearing_datetime', 'venue'] as $key) {
            if (!empty($payload[$key])) {
                $parts[] = ucwords(str_replace('_', ' ', $key)) . ': ' . trim((string) $payload[$key]);
            }
        }
        return implode(' · ', $parts) ?: 'Coordinator requested this action for the case.';
    }
}