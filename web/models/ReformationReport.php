<?php

require_once 'Model.php';

class ReformationReport extends Model {
    protected static $table = 'reformation_reports';
    protected static $primaryKey = 'report_id';

    public static function forCase($complaintId) {
        $sql = "SELECT r.*, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name, coordinator.role AS coordinator_role
                FROM reformation_reports r
                LEFT JOIN accounts coordinator ON r.coordinator_account_id = coordinator.account_id
                WHERE r.complaint_id = ?
                ORDER BY r.created_at DESC, r.report_id DESC";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function add($complaintId, $coordinatorAccountId, $title, array $file) {
        $stmt = self::$conn->prepare(
            "INSERT INTO reformation_reports
                (complaint_id, coordinator_account_id, report_title, original_filename, stored_filename, file_path, mime_type, file_size, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new Exception('Error preparing statement: ' . self::$conn->error);
        }

        $createdAt = date('Y-m-d H:i:s');
        $stmt->bind_param(
            'iisssssis',
            $complaintId,
            $coordinatorAccountId,
            $title,
            $file['original_filename'],
            $file['stored_filename'],
            $file['file_path'],
            $file['mime_type'],
            $file['file_size'],
            $createdAt
        );

        if (!$stmt->execute()) {
            throw new Exception('Error executing statement: ' . self::$conn->error);
        }

        return (int) $stmt->insert_id;
    }

    public static function find($reportId) {
        $stmt = self::$conn->prepare(
            "SELECT r.*, c.case_number, c.status,
                    c.submitted_by_account_id, c.assigned_coordinator_account_id,
                    c.assigned_reformation_coordinator_account_id, c.case_source
             FROM reformation_reports r
             INNER JOIN complaints c ON c.complaint_id = r.complaint_id
             WHERE r.report_id = ? LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $reportId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }
}