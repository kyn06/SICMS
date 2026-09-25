<?php

require_once __DIR__ . '/Model.php';

class CaseUpdate extends Model {
    protected static $table = 'case_updates';
    protected static $primaryKey = 'update_id';

    public const TYPES = [
        'additional_details' => 'Update Respondent Details',
        'investigation_update' => 'Investigation Update',
        'additional_evidence' => 'Additional Evidence',
        'clarification' => 'Clarification',
        'warning' => 'Notice of Warning',
        'settlement' => 'Settlement',
        'agreement' => 'Agreement',
        'administrative' => 'Administrative Update',
        'other' => 'Other',
    ];

    public static function updateTypes() {
        return self::TYPES;
    }

    public static function forCase($complaintId) {
        $sql = "SELECT u.*, author.first_name AS author_first_name,
                       author.last_name AS author_last_name, author.role AS author_role
                FROM case_updates u
                LEFT JOIN accounts author ON u.author_account_id = author.account_id
                WHERE u.complaint_id = ?
                ORDER BY u.created_at DESC, u.update_id DESC";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function stages() {
        return ['Archived' => 'Post-Archive Update', 'Resolved' => 'Post-Resolution Update'];
    }

    public static function stageLabel($status) {
        $stages = self::stages();

        return $stages[$status] ?? 'Case Update';
    }

    public static function attachments($updateId) {
        $sql = "SELECT * FROM complaint_evidence WHERE update_id = ? ORDER BY uploaded_at DESC, evidence_id DESC";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $updateId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function attachmentsForCase($complaintId) {
        $sql = "SELECT * FROM complaint_evidence
                WHERE complaint_id = ? AND update_id IS NOT NULL
                ORDER BY update_id DESC, uploaded_at DESC, evidence_id DESC";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public static function add($complaintId, $authorAccountId, $updateType, $details, $statusSnapshot, array $files) {
        self::$conn->begin_transaction();

        try {
            $createdAt = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare(
                "INSERT INTO case_updates
                    (complaint_id, author_account_id, update_type, details, case_status_snapshot, created_at)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                throw new Exception('Error preparing statement: ' . self::$conn->error);
            }

            $stmt->bind_param('iissss', $complaintId, $authorAccountId, $updateType, $details, $statusSnapshot, $createdAt);

            if (!$stmt->execute()) {
                throw new Exception('Error executing statement: ' . self::$conn->error);
            }

            $updateId = (int) $stmt->insert_id;

            foreach ($files as $file) {
                $fileStmt = self::$conn->prepare(
                    "INSERT INTO complaint_evidence
                        (complaint_id, update_id, original_filename, stored_filename, file_path, mime_type, file_size, uploaded_at, doc_type)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );

                if (!$fileStmt) {
                    throw new Exception('Error preparing statement: ' . self::$conn->error);
                }

                $docType = strtolower(pathinfo((string) $file['original_filename'], PATHINFO_EXTENSION));
                $fileStmt->bind_param(
                    'iissssiss',
                    $complaintId,
                    $updateId,
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

            return (int) $updateId;

        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }
}