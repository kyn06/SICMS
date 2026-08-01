<?php

require_once 'Model.php';

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
                WHERE (status = 'Verified' OR assigned_coordinator_account_id IS NOT NULL)";
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
                WHERE complaint_id = ? AND (status = 'Verified' OR assigned_coordinator_account_id IS NOT NULL)";
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

    public static function schedule(array $data) {
        return parent::create($data);
    }

    public static function updateHearing($hearingId, array $data) {
        return parent::updateById($hearingId, $data);
    }

    public static function updateStatus($hearingId, $status) {
        return parent::updateById($hearingId, [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function roleKey(array $user) {
        return strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));
    }
}
