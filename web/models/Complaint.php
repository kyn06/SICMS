<?php

require_once 'Model.php';

class Complaint extends Model {
    protected static $table = 'complaints';
    protected static $primaryKey = 'complaint_id';

    public static function generateCaseNumber() {
        $prefix = 'SDRU-' . date('Y') . '-';

        do {
            $caseNumber = $prefix . strtoupper(bin2hex(random_bytes(3)));
            $stmt = self::$conn->prepare("SELECT complaint_id FROM complaints WHERE case_number = ? LIMIT 1");
            $stmt->bind_param("s", $caseNumber);
            $stmt->execute();
            $result = $stmt->get_result();
        } while ($result && $result->num_rows > 0);

        return $caseNumber;
    }

    public static function forStudent($accountId, $limit = 5, array $filters = [], $offset = 0) {
        [$where, $params, $types] = self::studentWhere($accountId, $filters);
        $orderBy = match ($filters['sort'] ?? 'newest') {
            'oldest' => 'c.submitted_at ASC, c.complaint_id ASC',
            'updated' => 'c.updated_at DESC, c.complaint_id DESC',
            default => 'c.submitted_at DESC, c.complaint_id DESC',
        };
        $sql = "SELECT c.*, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name,
                       coordinator.role AS coordinator_role
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                $where
                ORDER BY $orderBy
                LIMIT ? OFFSET ?";
        $params[] = (int) $limit;
        $params[] = (int) $offset;
        $types .= 'ii';

        return self::fetchAll($sql, $params, $types);
    }

    public static function countForStudent($accountId, array $filters = []) {
        [$where, $params, $types] = self::studentWhere($accountId, $filters);
        $rows = self::fetchAll("SELECT COUNT(*) AS total FROM complaints c $where", $params, $types);

        return (int) ($rows[0]['total'] ?? 0);
    }

    public static function summaryForStudent($accountId) {
        $sql = "SELECT
                    COUNT(*) AS total_complaints,
                    SUM(CASE WHEN status IN ('Submitted', 'Returned for Revision') THEN 1 ELSE 0 END) AS pending_cases,
                    SUM(CASE WHEN status IN ('Verified') OR assigned_coordinator_account_id IS NOT NULL THEN 1 ELSE 0 END) AS ongoing_cases,
                    SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_cases
                FROM complaints
                WHERE submitted_by_account_id = ?";
        $rows = self::fetchAll($sql, [(int) $accountId], 'i');
        $row = $rows[0] ?? [];

        return [
            'total_complaints' => (int) ($row['total_complaints'] ?? 0),
            'pending_cases' => (int) ($row['pending_cases'] ?? 0),
            'ongoing_cases' => (int) ($row['ongoing_cases'] ?? 0),
            'resolved_cases' => (int) ($row['resolved_cases'] ?? 0),
        ];
    }

    public static function findForStudent($complaintId, $accountId) {
        $sql = "SELECT c.*, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name,
                       coordinator.role AS coordinator_role
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE c.complaint_id = ? AND c.submitted_by_account_id = ?
                LIMIT 1";
        $rows = self::fetchAll($sql, [(int) $complaintId, (int) $accountId], 'ii');

        return $rows[0] ?? null;
    }

    public static function hearingsForStudent($accountId, $limit = 5) {
        $sql = "SELECT h.*, c.case_number, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE c.submitted_by_account_id = ?
                  AND h.status = 'Scheduled'
                  AND h.hearing_datetime >= NOW()
                ORDER BY h.hearing_datetime ASC
                LIMIT ?";

        return self::fetchAll($sql, [(int) $accountId, (int) $limit], 'ii');
    }

    public static function hearingsForStudentCase($complaintId, $accountId) {
        $sql = "SELECT h.*, coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE h.complaint_id = ? AND c.submitted_by_account_id = ?
                ORDER BY h.hearing_datetime DESC";

        return self::fetchAll($sql, [(int) $complaintId, (int) $accountId], 'ii');
    }

    private static function studentWhere($accountId, array $filters) {
        $where = ['c.submitted_by_account_id = ?'];
        $params = [(int) $accountId];
        $types = 'i';

        if (!empty($filters['case_number'])) {
            $where[] = 'c.case_number LIKE ?';
            $params[] = '%' . $filters['case_number'] . '%';
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $where[] = 'c.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['classification'])) {
            $where[] = 'c.case_classification = ?';
            $params[] = $filters['classification'];
            $types .= 's';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'c.submitted_at >= ?';
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'c.submitted_at <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        if (!empty($filters['academic_year'])) {
            $where[] = 'YEAR(c.submitted_at) = ?';
            $params[] = (int) $filters['academic_year'];
            $types .= 'i';
        }

        return ['WHERE ' . implode(' AND ', $where), $params, $types];
    }

    private static function fetchAll($sql, array $params = [], $types = '') {
        try {
            $stmt = self::$conn->prepare($sql);

            if (!$stmt) {
                return [];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } catch (Throwable $exception) {
            return [];
        }
    }

    public static function createComplaint(array $complaint, array $respondents, array $witnesses, array $evidenceFiles) {
        self::$conn->begin_transaction();

        try {
            $createdComplaint = parent::create($complaint);
            $complaintId = $createdComplaint['complaint_id'];

            foreach ($respondents as $respondent) {
                self::createRelatedRecord('complaint_respondents', [
                    'complaint_id' => $complaintId,
                    'full_name' => $respondent['full_name'],
                    'student_no' => $respondent['student_no'],
                    'college' => $respondent['college'],
                    'course_year' => $respondent['course_year'],
                    'contact_info' => $respondent['contact_info'],
                    'details' => $respondent['details'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            foreach ($witnesses as $witness) {
                self::createRelatedRecord('complaint_witnesses', [
                    'complaint_id' => $complaintId,
                    'full_name' => $witness['full_name'],
                    'student_no' => $witness['student_no'],
                    'contact_info' => $witness['contact_info'],
                    'statement' => $witness['statement'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            foreach ($evidenceFiles as $file) {
                self::createRelatedRecord('complaint_evidence', [
                    'complaint_id' => $complaintId,
                    'original_filename' => $file['original_filename'],
                    'stored_filename' => $file['stored_filename'],
                    'file_path' => $file['file_path'],
                    'mime_type' => $file['mime_type'],
                    'file_size' => $file['file_size'],
                    'uploaded_at' => date('Y-m-d H:i:s'),
                ]);
            }

            self::$conn->commit();
            return $createdComplaint;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    private static function createRelatedRecord($table, array $data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Error preparing statement: " . self::$conn->error);
        }

        $types = '';
        $values = [];

        foreach ($data as $value) {
            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value)) {
                $types .= 'd';
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
}
