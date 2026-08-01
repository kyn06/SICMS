<?php

require_once 'Model.php';
require_once 'Notification.php';

class CaseRecord extends Model {
    protected static $table = 'complaints';
    protected static $primaryKey = 'complaint_id';

    public static function getStatuses() {
        return [
            'Submitted',
            'Verified',
            'Returned for Revision',
            'Rejected',
        ];
    }

    public static function listCases(array $filters = []) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE 1 = 1";
        $params = [];
        $types = '';

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['classification'])) {
            $sql .= " AND c.case_classification = ?";
            $params[] = $filters['classification'];
            $types .= 's';
        }

        if (!empty($filters['case_number'])) {
            $sql .= " AND c.case_number LIKE ?";
            $params[] = '%' . $filters['case_number'] . '%';
            $types .= 's';
        }

        if (!empty($filters['student_name'])) {
            $sql .= " AND c.complainant_name LIKE ?";
            $params[] = '%' . $filters['student_name'] . '%';
            $types .= 's';
        }

        if (!empty($filters['assigned_coordinator_account_id'])) {
            $sql .= " AND c.assigned_coordinator_account_id = ?";
            $params[] = (int) $filters['assigned_coordinator_account_id'];
            $types .= 'i';
        }

        $sql .= " ORDER BY c.submitted_at DESC";

        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . self::$conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function getClassifications() {
        $stmt = self::$conn->prepare("SELECT DISTINCT case_classification FROM complaints ORDER BY case_classification");

        if (!$stmt) {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'case_classification') : [];
    }

    public static function findCase($complaintId) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE c.complaint_id = ?
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }

    public static function getRespondents($complaintId) {
        return self::fetchRelated("SELECT * FROM complaint_respondents WHERE complaint_id = ? ORDER BY respondent_id", $complaintId);
    }

    public static function getWitnesses($complaintId) {
        return self::fetchRelated("SELECT * FROM complaint_witnesses WHERE complaint_id = ? ORDER BY witness_id", $complaintId);
    }

    public static function getEvidence($complaintId) {
        return self::fetchRelated("SELECT * FROM complaint_evidence WHERE complaint_id = ? ORDER BY uploaded_at DESC", $complaintId);
    }

    public static function getHistory($complaintId) {
        $sql = "SELECT h.*, actor.first_name AS actor_first_name, actor.last_name AS actor_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM case_history h
                LEFT JOIN accounts actor ON h.created_by_account_id = actor.account_id
                LEFT JOIN accounts coordinator ON h.assigned_coordinator_account_id = coordinator.account_id
                WHERE h.complaint_id = ?
                ORDER BY h.created_at DESC, h.history_id DESC";
        return self::fetchRelated($sql, $complaintId);
    }

    public static function getCoordinators() {
        $roles = ['super-admin', 'admin', 'sdr staff', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head', 'head of sdru'];
        $placeholders = implode(', ', array_fill(0, count($roles), '?'));
        $sql = "SELECT account_id, first_name, last_name, role
                FROM accounts
                WHERE status = 'active' AND role IN ($placeholders)
                ORDER BY first_name, last_name";
        $stmt = self::$conn->prepare($sql);
        $types = str_repeat('s', count($roles));
        $stmt->bind_param($types, ...$roles);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function updateStatus($complaintId, $newStatus, $remarks, $actorAccountId) {
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = ?, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("ssi", $newStatus, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => self::actionFromStatus($newStatus),
                'previous_status' => $case['status'],
                'new_status' => $newStatus,
                'remarks' => $remarks,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            self::notifyCaseStatusChanged($case, $newStatus);

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function assignCoordinator($complaintId, $coordinatorAccountId, $remarks, $actorAccountId) {
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET assigned_coordinator_account_id = ?, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("isi", $coordinatorAccountId, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Assigned Coordinator',
                'previous_status' => $case['status'],
                'new_status' => $case['status'],
                'remarks' => $remarks,
                'assigned_coordinator_account_id' => $coordinatorAccountId,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            Notification::createForUser(
                (int) $coordinatorAccountId,
                'coordinator_assigned',
                'Case Assigned',
                'You were assigned as coordinator for case ' . $case['case_number'] . '.',
                'web/views/cases/show.php?id=' . $complaintId
            );

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    private static function fetchRelated($sql, $complaintId) {
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private static function createHistory(array $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO case_history ($columns) VALUES ($placeholders)";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . self::$conn->error);
        }

        $types = '';
        $values = [];

        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } else {
                $types .= 's';
            }

            $values[] = $value;
        }

        $stmt->bind_param($types, ...$values);

        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . self::$conn->error);
        }
    }

    private static function actionFromStatus($status) {
        return match ($status) {
            'Verified' => 'Verified Complaint',
            'Returned for Revision' => 'Returned for Revision',
            'Rejected' => 'Rejected Complaint',
            'Resolved' => 'Resolved Case',
            default => 'Updated Status',
        };
    }

    private static function notifyCaseStatusChanged(array $case, $newStatus) {
        $type = $newStatus === 'Resolved' ? 'case_resolved' : 'case_status_updated';
        $type = $newStatus === 'Verified' ? 'complaint_verified' : $type;
        $title = $newStatus === 'Verified' ? 'Complaint Verified' : 'Case Status Updated';

        if ($newStatus === 'Resolved') {
            $title = 'Case Resolved';
        }

        Notification::createForUser(
            (int) $case['submitted_by_account_id'],
            $type,
            $title,
            'Case ' . $case['case_number'] . ' is now ' . $newStatus . '.',
            'web/views/cases/show.php?id=' . $case['complaint_id']
        );
    }
}
