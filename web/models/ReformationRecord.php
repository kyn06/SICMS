<?php

require_once 'Model.php';

class ReformationRecord extends Model {
    protected static $table = 'reformation_records';
    protected static $primaryKey = 'reformation_record_id';

    public static function forCase($complaintId) {
        $sql = "SELECT r.*, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name, coordinator.role AS coordinator_role
                FROM reformation_records r
                LEFT JOIN accounts coordinator ON r.coordinator_account_id = coordinator.account_id
                WHERE r.complaint_id = ?
                ORDER BY r.created_at DESC, r.reformation_record_id DESC";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function attachments($recordId) {
        $stmt = self::$conn->prepare(
            "SELECT * FROM complaint_evidence
             WHERE reformation_record_id = ?
             ORDER BY uploaded_at DESC, evidence_id DESC"
        );

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $recordId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function add($complaintId, $coordinatorAccountId, $progressStatus, $remarks, array $files) {
        self::$conn->begin_transaction();

        try {
            $progressDate = date('Y-m-d');
            $createdAt = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare(
                "INSERT INTO reformation_records
                    (complaint_id, coordinator_account_id, activity, progress_date, progress_status, remarks, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                throw new Exception('Error preparing statement: ' . self::$conn->error);
            }

            $stmt->bind_param('iisssss', $complaintId, $coordinatorAccountId, $remarks, $progressDate, $progressStatus, $remarks, $createdAt);

            if (!$stmt->execute()) {
                throw new Exception('Error executing statement: ' . self::$conn->error);
            }

            $recordId = (int) $stmt->insert_id;

            foreach ($files as $file) {
                $fileStmt = self::$conn->prepare(
                    "INSERT INTO complaint_evidence
                        (complaint_id, reformation_record_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at, doc_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$fileStmt) {
                    throw new Exception('Error preparing statement: ' . self::$conn->error);
                }

                $docType = strtolower(pathinfo((string) $file['original_filename'], PATHINFO_EXTENSION));
                $fileStmt->bind_param(
                    'iissssiss',
                    $complaintId,
                    $recordId,
                    $file['original_filename'],
                    $file['stored_filename'],
                    $file['file_path'],
                    $file['mime_type'],
                    $file['file_size'],
                    $createdAt,
                    $docType
                );

                if (!$fileStmt->execute()) {
                    throw new Exception('Error executing statement: ' . self::$conn->error);
                }
            }

            self::$conn->commit();
            return $recordId;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }
}