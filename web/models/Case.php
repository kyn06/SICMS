<?php

require_once 'Model.php';
require_once 'Notification.php';
require_once __DIR__ . '/../helpers/PersonName.php';

class CaseRecord extends Model {
    protected static $table = 'complaints';
    protected static $primaryKey = 'complaint_id';

    public static function getStatuses() {
        return [
            'Under Investigation',
            'Returned for Revision',
            'Rejected',
            'Resolved',
            'Reformation in Progress',
            'Reformation Completed',
            'Escalated',
            'Archived',
        ];
    }

    public static function listCases(array $filters = []) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name,
                       reformation_coordinator.first_name AS reformation_coordinator_first_name,
                       reformation_coordinator.last_name AS reformation_coordinator_last_name,
                       (SELECT GROUP_CONCAT(r.full_name ORDER BY r.respondent_id SEPARATOR ', ') FROM complaint_respondents r WHERE r.complaint_id = c.complaint_id) AS respondent_names
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN accounts reformation_coordinator ON c.assigned_reformation_coordinator_account_id = reformation_coordinator.account_id
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

        if (!empty($filters['date_from'])) {
            $sql .= " AND c.submitted_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND c.submitted_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(c.submitted_at) = ?";
            $params[] = (int) $filters['month'];
            $types .= 'i';
        }

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(c.submitted_at) = ?";
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (c.case_number LIKE ? OR c.complainant_name LIKE ?
                    OR c.status LIKE ? OR c.case_classification LIKE ?
                    OR DATE_FORMAT(c.submitted_at, '%Y-%m-%d') LIKE ?
                    OR DATE_FORMAT(c.submitted_at, '%M %e, %Y') LIKE ?
                    OR DATE_FORMAT(c.submitted_at, '%b %e, %Y') LIKE ? OR EXISTS (
                        SELECT 1 FROM complaint_respondents rr WHERE rr.complaint_id = c.complaint_id AND rr.full_name LIKE ?
                    ))";
            array_push($params, $search, $search, $search, $search, $search, $search, $search, $search);
            $types .= 'ssssssss';
        }

        if (!empty($filters['assigned_coordinator_account_id'])) {
            $sql .= " AND c.assigned_coordinator_account_id = ?";
            $params[] = (int) $filters['assigned_coordinator_account_id'];
            $types .= 'i';
        }

        if (!empty($filters['assigned_reformation_coordinator_account_id'])) {
            $sql .= " AND c.assigned_reformation_coordinator_account_id = ?";
            $params[] = (int) $filters['assigned_reformation_coordinator_account_id'];
            $types .= 'i';
        }

        if (empty($filters['include_archived'])) {
            $sql .= " AND c.status != 'Archived'";
        }

        $sql .= " AND COALESCE(c.case_source, 'Online Submission') <> 'Legacy'";

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

    public static function listArchivedCases(array $filters = []) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                WHERE c.status = 'Archived'";
        $params = [];
        $types = '';

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

        if (!empty($filters['assigned_reformation_coordinator_account_id'])) {
            $sql .= " AND c.assigned_reformation_coordinator_account_id = ?";
            $params[] = (int) $filters['assigned_reformation_coordinator_account_id'];
            $types .= 'i';
        }

        $sql .= " ORDER BY c.updated_at DESC";

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
                       coordinator.last_name AS coordinator_last_name,
                       reformation_coordinator.first_name AS reformation_coordinator_first_name,
                       reformation_coordinator.last_name AS reformation_coordinator_last_name
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN accounts reformation_coordinator ON c.assigned_reformation_coordinator_account_id = reformation_coordinator.account_id
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

    public static function createRespondent($complaintId, array $data) {
        if (array_key_exists('full_name', $data)) $data['full_name'] = PersonName::normalize($data['full_name']);
        $stmt = self::$conn->prepare(
                "INSERT INTO complaint_respondents
                    (complaint_id, respondent_type, full_name, gender, age, student_no, employee_no, college, office_department, course_year, position, affiliation, contact_info, email, address, details, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) throw new Exception('Unable to prepare respondent creation.');

        $createdAt = date('Y-m-d H:i:s');
        $age = ($data['age'] ?? '') !== '' && (int) $data['age'] > 0 ? (int) $data['age'] : null;
        $email = (string) ($data['email'] ?? '');
        $address = (string) ($data['address'] ?? '');
        $stmt->bind_param(
            'isssissssssssssss',
            $complaintId,
            $data['respondent_type'],
            $data['full_name'],
            $data['gender'],
            $age,
            $data['student_no'],
            $data['employee_no'],
            $data['college'],
            $data['office_department'],
            $data['course_year'],
            $data['position'],
            $data['affiliation'],
            $data['contact_info'],
            $email,
            $address,
            $data['details'],
            $createdAt
        );
        if (!$stmt->execute()) throw new Exception('Unable to create respondent.');
        return (int) $stmt->insert_id;
    }

    public static function updateRespondent($respondentId, $complaintId, array $data) {
        if (array_key_exists('full_name', $data)) $data['full_name'] = PersonName::normalize($data['full_name']);
        $allowed = ['respondent_type', 'full_name', 'gender', 'age', 'student_no', 'employee_no', 'college', 'office_department', 'course_year', 'position', 'affiliation', 'contact_info', 'email', 'address', 'details'];
        $changes = array_intersect_key($data, array_flip($allowed));
        if (empty($changes)) return false;

        $set = implode(', ', array_map(fn($key) => "$key = ?", array_keys($changes)));
        $sql = "UPDATE complaint_respondents SET $set WHERE respondent_id = ? AND complaint_id = ?";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) throw new Exception('Unable to prepare respondent update.');

        $values = array_values($changes);
        $types = str_repeat('s', count($values)) . 'ii';
        $values[] = (int) $respondentId;
        $values[] = (int) $complaintId;
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) throw new Exception('Unable to save respondent details.');
        return $stmt->affected_rows > 0;
    }

    public static function getWitnesses($complaintId) {
        return self::fetchRelated("SELECT * FROM complaint_witnesses WHERE complaint_id = ? ORDER BY witness_id", $complaintId);
    }

    public static function getEvidence($complaintId) {
        return self::fetchRelated("SELECT * FROM complaint_evidence WHERE complaint_id = ? AND update_id IS NULL ORDER BY uploaded_at DESC", $complaintId);
    }

    public static function findEvidence($evidenceId) {
        $sql = "SELECT e.*, c.submitted_by_account_id, c.assigned_coordinator_account_id,
                       c.assigned_reformation_coordinator_account_id, c.case_number, c.case_source
                FROM complaint_evidence e
                INNER JOIN complaints c ON c.complaint_id = e.complaint_id
                WHERE e.evidence_id = ? LIMIT 1";
        $rows = self::fetchRelated($sql, $evidenceId);
        return $rows[0] ?? null;
    }

    public static function getHistory($complaintId) {
        $sql = "SELECT h.*, actor.first_name AS actor_first_name, actor.last_name AS actor_last_name,
                       coordinator.first_name AS coordinator_first_name,
                       coordinator.last_name AS coordinator_last_name,
                       reformation_coordinator.first_name AS reformation_coordinator_first_name,
                       reformation_coordinator.last_name AS reformation_coordinator_last_name
                FROM case_history h
                LEFT JOIN accounts actor ON h.created_by_account_id = actor.account_id
                LEFT JOIN accounts coordinator ON h.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN accounts reformation_coordinator ON h.reformation_coordinator_account_id = reformation_coordinator.account_id
                WHERE h.complaint_id = ?
                ORDER BY h.created_at DESC, h.history_id DESC";
        return self::fetchRelated($sql, $complaintId);
    }

    public static function getCoordinators($role = 'coordinator') {
        $normalized = strtolower(str_replace(['_', ' '], '-', (string) $role));
        $sql = "SELECT account_id, first_name, last_name, role
                FROM accounts
                WHERE status = 'active' AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) = ?
                ORDER BY first_name, last_name";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param('s', $normalized);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /*
     * ------------------------------------------------------------------
     * Legacy Cases (digitized historical cases)
     * ------------------------------------------------------------------
     * Legacy cases live in the same `complaints` table as online cases,
     * flagged via `case_source = 'Legacy'` and dated by
     * `original_case_date` so reports group them by their ORIGINAL case
     * date rather than the date staff digitized them.
     * ------------------------------------------------------------------
     */

    public static function listLegacyCases(array $filters = []) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name,
                       (SELECT GROUP_CONCAT(r.full_name ORDER BY r.respondent_id SEPARATOR ', ') FROM complaint_respondents r WHERE r.complaint_id = c.complaint_id) AS respondent_names
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                WHERE c.case_source = 'Legacy'";
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

        if (!empty($filters['college'])) {
            $sql .= " AND c.complainant_college LIKE ?";
            $params[] = '%' . $filters['college'] . '%';
            $types .= 's';
        }

        if (!empty($filters['year'])) {
            $sql .= " AND YEAR(COALESCE(c.original_case_date, c.submitted_at)) = ?";
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND COALESCE(c.original_case_date, c.submitted_at) >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND COALESCE(c.original_case_date, c.submitted_at) <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        if (!empty($filters['month'])) {
            $sql .= " AND MONTH(COALESCE(c.original_case_date, c.submitted_at)) = ?";
            $params[] = (int) $filters['month'];
            $types .= 'i';
        }

        if (!empty($filters['case_number'])) {
            $sql .= " AND c.case_number LIKE ?";
            $params[] = '%' . $filters['case_number'] . '%';
            $types .= 's';
        }

        if (!empty($filters['complainant_name'])) {
            $sql .= " AND c.complainant_name LIKE ?";
            $params[] = '%' . $filters['complainant_name'] . '%';
            $types .= 's';
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $sql .= " AND (c.case_number LIKE ? OR c.complainant_name LIKE ?
                    OR c.status LIKE ? OR c.case_classification LIKE ?
                    OR DATE_FORMAT(COALESCE(c.original_case_date, c.submitted_at), '%Y-%m-%d') LIKE ?
                    OR DATE_FORMAT(COALESCE(c.original_case_date, c.submitted_at), '%M %e, %Y') LIKE ?
                    OR DATE_FORMAT(COALESCE(c.original_case_date, c.submitted_at), '%b %e, %Y') LIKE ? OR EXISTS (
                        SELECT 1 FROM complaint_respondents rr WHERE rr.complaint_id = c.complaint_id AND rr.full_name LIKE ?
                    ))";
            array_push($params, $search, $search, $search, $search, $search, $search, $search, $search);
            $types .= 'ssssssss';
        }

        $sql .= " ORDER BY COALESCE(c.original_case_date, c.submitted_at) DESC, c.complaint_id DESC";

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

    public static function findLegacyCase($complaintId) {
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name
                FROM complaints c
                LEFT JOIN accounts a ON c.submitted_by_account_id = a.account_id
                WHERE c.complaint_id = ? AND c.case_source = 'Legacy'
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_assoc() : null;
    }

    public static function legacyClassifications() {
        $stmt = self::$conn->prepare("SELECT DISTINCT case_classification FROM complaints WHERE case_source = 'Legacy' ORDER BY case_classification");
        if (!$stmt) return [];
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? array_column($result->fetch_all(MYSQLI_ASSOC), 'case_classification') : [];
    }

    public static function createLegacyCase(array $complaint, array $respondents, array $witnesses, array $evidenceFiles, array $hearings, $actorAccountId) {
        self::$conn->begin_transaction();

        try {
            $complaint['case_source'] = 'Legacy';
            $complaint['created_at'] = date('Y-m-d H:i:s');
            $complaint['updated_at'] = date('Y-m-d H:i:s');
            self::insertRelated('complaints', $complaint);
            $complaintId = (int) self::$conn->insert_id;

            foreach ($respondents as $respondent) {
                self::insertRelated('complaint_respondents', array_merge($respondent, ['complaint_id' => $complaintId, 'created_at' => date('Y-m-d H:i:s')]));
            }
            foreach ($witnesses as $witness) {
                self::insertRelated('complaint_witnesses', array_merge($witness, ['complaint_id' => $complaintId, 'created_at' => date('Y-m-d H:i:s')]));
            }
            foreach ($evidenceFiles as $file) {
                self::insertRelated('complaint_evidence', array_merge($file, ['complaint_id' => $complaintId, 'uploaded_at' => date('Y-m-d H:i:s')]));
            }
            foreach ($hearings as $hearing) {
                self::insertRelated('hearings', array_merge($hearing, ['complaint_id' => $complaintId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]));
            }

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Legacy Case Digitized',
                'previous_status' => null,
                'new_status' => $complaint['status'] ?? null,
                'remarks' => 'Legacy case digitized into the system by staff.',
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            self::$conn->commit();
            return self::find($complaintId);
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function updateLegacyCase($complaintId, array $complaint, array $respondents, array $witnesses, array $evidenceFiles, array $removeEvidenceIds, array $hearings, $actorAccountId) {
        $previousStatus = $complaint['legacy_previous_status'] ?? null;
        $updateRemarks = trim((string) ($complaint['legacy_update_remarks'] ?? '')) ?: null;
        unset($complaint['legacy_previous_status'], $complaint['legacy_update_remarks']);

        self::$conn->begin_transaction();

        try {
            $complaint['updated_at'] = date('Y-m-d H:i:s');
            $set = implode(', ', array_map(fn($column) => "$column = ?", array_keys($complaint)));
            $values = array_values($complaint);
            $types = str_repeat('s', count($values)) . 'i';
            $values[] = $complaintId;
            $stmt = self::$conn->prepare("UPDATE complaints SET $set WHERE complaint_id = ? AND case_source = 'Legacy'");
            $stmt->bind_param($types, ...$values);
            $stmt->execute();

            if ($respondents !== null) {
                self::replaceLegacyPeople('complaint_respondents', 'respondent_id', $complaintId, $respondents);
            }
            if ($witnesses !== null) {
                self::replaceLegacyPeople('complaint_witnesses', 'witness_id', $complaintId, $witnesses);
            }

            $removedPaths = [];
            if ($removeEvidenceIds) {
                $placeholders = implode(',', array_fill(0, count($removeEvidenceIds), '?'));
                $params = array_merge([$complaintId], array_map('intval', $removeEvidenceIds));
                $types = 'i' . str_repeat('i', count($removeEvidenceIds));
                $stmt = self::$conn->prepare("SELECT evidence_id, file_path FROM complaint_evidence WHERE complaint_id = ? AND evidence_id IN ($placeholders)");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $removedPaths = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'file_path');
                $stmt = self::$conn->prepare("DELETE FROM complaint_evidence WHERE complaint_id = ? AND evidence_id IN ($placeholders)");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
            }

            foreach ($evidenceFiles as $file) {
                self::insertRelated('complaint_evidence', array_merge($file, ['complaint_id' => $complaintId, 'uploaded_at' => date('Y-m-d H:i:s')]));
            }

            if ($hearings !== null) {
                $stmt = self::$conn->prepare("DELETE FROM hearings WHERE complaint_id = ?");
                $stmt->bind_param('i', $complaintId);
                $stmt->execute();
                foreach ($hearings as $hearing) {
                    self::insertRelated('hearings', array_merge($hearing, ['complaint_id' => $complaintId, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]));
                }
            }

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Legacy Case Updated',
                'previous_status' => $previousStatus,
                'new_status' => $complaint['status'] ?? null,
                'remarks' => $updateRemarks,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            self::$conn->commit();
            return $removedPaths;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    private static function insertRelated($table, array $data) {
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
            if (is_int($value)) $types .= 'i';
            elseif (is_float($value)) $types .= 'd';
            else $types .= 's';
            $values[] = $value;
        }
        $stmt->bind_param($types, ...$values);
        if (!$stmt->execute()) {
            throw new Exception("Error executing statement: " . self::$conn->error);
        }
    }

    private static function replaceLegacyPeople($table, $primaryKey, $complaintId, array $rows) {
        $stmt = self::$conn->prepare("DELETE FROM $table WHERE complaint_id = ?");
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();

        foreach ($rows as $row) {
            $row = array_diff_key($row, array_flip([$primaryKey, 'complaint_id', 'created_at']));
            self::insertRelated($table, array_merge($row, ['complaint_id' => $complaintId, 'created_at' => date('Y-m-d H:i:s')]));
        }
    }

    public static function updateStatus($complaintId, $newStatus, $remarks, $actorAccountId, array $revisionFields = []) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            if ($newStatus === 'Under Investigation'
                && in_array($case['status'] ?? '', ['Resolved', 'Reformation in Progress', 'Reformation Completed'], true)) {
                $stmt = self::$conn->prepare("UPDATE complaints SET status = ?, updated_at = ?, assigned_reformation_coordinator_account_id = NULL, reformation_started_at = NULL, reformation_completed_at = NULL WHERE complaint_id = ?");
                $stmt->bind_param("ssi", $newStatus, $now, $complaintId);
            } else {
                $stmt = self::$conn->prepare("UPDATE complaints SET status = ?, updated_at = ? WHERE complaint_id = ?");
                $stmt->bind_param("ssi", $newStatus, $now, $complaintId);
            }
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => self::actionFromStatus($newStatus),
                'previous_status' => $case['status'],
                'new_status' => $newStatus,
                'remarks' => $remarks,
                'revision_fields' => $revisionFields ? json_encode(array_values($revisionFields)) : null,
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

    /*
     * ---- Respondent account helpers ----
     */

    const INVITATION_VALID_HOURS = 72;

    /* Info sections a coordinator may release to the respondent when forwarding a case. */
    const RESPONDENT_VISIBILITY_KEYS = [
        'complaint_details',
        'incident',
        'hearings',
        'final_information',
    ];

    /* Whether the case has been released/forwarded to its respondents. */
    public static function respondentReleased($complaintId) {
        $case = self::findCase($complaintId);
        return $case && !empty($case['respondent_released_at']);
    }

    /* The visibility array for a case (all keys true when never explicitly set). */
    public static function respondentVisibility($complaintId) {
        $case = self::findCase($complaintId);
        $raw = (string) ($case['respondent_visibility'] ?? '');
        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $allowed = [];
                foreach (self::RESPONDENT_VISIBILITY_KEYS as $key) {
                    $allowed[$key] = !empty($decoded[$key]);
                }
                return $allowed;
            }
        }
        return array_fill_keys(self::RESPONDENT_VISIBILITY_KEYS, true);
    }

    /* Coordinator/head releases permitted case info to the selected linked respondents.
     * Pass $recipientIds (account IDs) to notify only those; null forwards to all. */
    public static function releaseToRespondents($complaintId, $actorAccountId, array $visibility = [], $recipientIds = null) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);
        if (!$case) return false;

        $selected = is_array($recipientIds)
            ? array_map('intval', array_values(array_filter($recipientIds, fn($id) => (int) $id > 0)))
            : null;

        $now = date('Y-m-d H:i:s');

        $normalized = [];
        foreach (self::RESPONDENT_VISIBILITY_KEYS as $key) {
            $normalized[$key] = isset($visibility[$key]) && $visibility[$key];
        }
        $normalized['complaint_details'] = true;

        self::$conn->begin_transaction();

        try {
            $json = json_encode($normalized);
            $stmt = self::$conn->prepare(
                "UPDATE complaints
                 SET respondent_released_at = ?, respondent_released_by_account_id = ?, respondent_visibility = ?, updated_at = ?
                 WHERE complaint_id = ?"
            );
            $stmt->bind_param('sissi', $now, $actorAccountId, $json, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Forwarded to Respondent',
                'previous_status' => $case['status'],
                'new_status' => $case['status'],
                'remarks' => 'Coordinator released the permitted case information to the respondent(s).',
                'revision_fields' => null,
                'assigned_coordinator_account_id' => $case['assigned_coordinator_account_id'],
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            Notification::createForHeads(
                'respondent_case_released',
                'Case Forwarded to Respondent',
                'The permitted case information for ' . ($case['case_number'] ?? ('case #' . $complaintId)) . ' was released to the respondent(s).',
                'web/views/cases/show.php?id=' . $complaintId
            );

            foreach (self::linkedRespondentAccounts($complaintId) as $account) {
                if ($selected !== null && !in_array((int) $account['account_id'], $selected, true)) {
                    continue;
                }
                Notification::createForUser(
                    (int) $account['account_id'],
                    'respondent_case_released',
                    'Case Forwarded to You',
                    'The SDRU has forwarded case ' . ($case['case_number'] ?? ('case #' . $complaintId)) . ' to you as a respondent. You may now review the permitted details and file your counter-statement.',
                    'web/views/respondent/case_show.php?id=' . $complaintId
                );
            }

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    /* Whether a respondent invitation link (sent at $invitedAt) has expired. */
    public static function invitationIsExpired($invitedAt, $now = null) {
        $invitedAt = trim((string) $invitedAt);
        if ($invitedAt === '') return true;
        $sentTs = strtotime($invitedAt);
        if ($sentTs === false) return true;
        $now = $now === null ? time() : (int) $now;
        return ($now - $sentTs) > self::INVITATION_VALID_HOURS * 3600;
    }

    /* Single complaint_respondents link row for an account on a case, or null. */
    public static function respondentLink($complaintId, $accountId) {
        $sql = "SELECT r.*, c.case_number, c.status AS case_status, c.case_source
                FROM complaint_respondents r
                INNER JOIN complaints c ON c.complaint_id = r.complaint_id
                INNER JOIN accounts respondent_account
                    ON respondent_account.account_id = r.account_id
                    AND respondent_account.role = 'student'
                    AND respondent_account.status = 'active'
                WHERE r.complaint_id = ? AND r.account_id = ? AND (c.case_source IS NULL OR c.case_source <> 'Legacy')
                LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('ii', $complaintId, $accountId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        return $rows[0] ?? null;
    }

    public static function isAccountRespondentForCase($complaintId, $accountId) {
        return self::respondentLink($complaintId, $accountId) !== null;
    }

    /* Every complaint_respondents row on a case with any linked account info. */
    public static function respondentsWithAccounts($complaintId) {
        $sql = "SELECT r.*, a.account_id AS linked_account_id, a.email AS account_email,
                       a.status AS account_status, a.role AS account_role,
                       a.first_name AS account_first_name, a.last_name AS account_last_name
                FROM complaint_respondents r
                LEFT JOIN accounts a ON a.account_id = r.account_id
                WHERE r.complaint_id = ?
                ORDER BY r.respondent_id ASC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    /* All non-legacy cases an account is explicitly named as a respondent on.
     * Unreleased cases are returned so the respondent can see their assignment
     * state, but case contents remain protected by respondentReleased(). */
    public static function casesForRespondent($accountId) {
        $sql = "SELECT c.*, r.respondent_id, r.full_name AS respondent_display_name,
                       (SELECT MAX(cs.updated_at)
                        FROM counter_statements cs
                        WHERE cs.complaint_id = c.complaint_id AND cs.respondent_id = r.respondent_id) AS counter_updated_at
                FROM complaints c
                INNER JOIN complaint_respondents r
                    ON r.complaint_id = c.complaint_id AND r.account_id = ?
                INNER JOIN accounts respondent_account
                    ON respondent_account.account_id = r.account_id
                    AND respondent_account.role = 'student'
                    AND respondent_account.status = 'active'
                WHERE (c.case_source IS NULL OR c.case_source <> 'Legacy')
                ORDER BY c.created_at DESC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    /* Link an existing account to a respondent row (email match validated by caller). */
    public static function linkRespondent($complaintId, $respondentId, $accountId) {
        $stmt = self::$conn->prepare(
            "UPDATE complaint_respondents SET account_id = ?, invitation_token = NULL, invited_at = NULL
             WHERE respondent_id = ? AND complaint_id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('iii', $accountId, $respondentId, $complaintId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /* Store the invitation token on a respondent row (used by activation). */
    public static function storeRespondentInvitation($complaintId, $respondentId, $token) {
        $stmt = self::$conn->prepare(
            "UPDATE complaint_respondents SET invitation_token = ?, invited_at = ?
             WHERE respondent_id = ? AND complaint_id = ?"
        );
        if (!$stmt) return false;
        $now = date('Y-m-d H:i:s');
        $stmt->bind_param('ssii', $token, $now, $respondentId, $complaintId);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /* Clear invitation token once the respondent activates their account. */
    public static function clearRespondentInvitation($complaintId, $respondentId) {
        $stmt = self::$conn->prepare(
            "UPDATE complaint_respondents SET invitation_token = NULL WHERE respondent_id = ? AND complaint_id = ?"
        );
        if (!$stmt) return false;
        $stmt->bind_param('ii', $respondentId, $complaintId);
        return $stmt->execute();
    }

    /* Active linked respondent account IDs + emails for a case (non-legacy). */
    public static function linkedRespondentAccounts($complaintId) {
        $sql = "SELECT a.account_id, a.email, a.first_name, a.last_name
                FROM complaint_respondents r
                INNER JOIN accounts a ON a.account_id = r.account_id
                WHERE r.complaint_id = ? AND a.status = 'active' AND a.role = 'student'
                ORDER BY r.respondent_id ASC";
        $stmt = self::$conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    public static function resolveCase($complaintId, $remarks, $outcome, $actorAccountId) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = 'Resolved', outcome = ?, updated_at = ? WHERE complaint_id = ?");
            $outcome = $outcome !== '' ? $outcome : null;
            $stmt->bind_param("ssi", $outcome, $now, $complaintId);
            $stmt->execute();

            $historyRemarks = $outcome ? trim($remarks . ($remarks ? "\n\n" : '') . 'Outcome: ' . $outcome) : $remarks;

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Resolved',
                'previous_status' => $case['status'],
                'new_status' => 'Resolved',
                'remarks' => $historyRemarks,
                'revision_fields' => null,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            self::notifyCaseStatusChanged($case, 'Resolved');

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function escalateCase($complaintId, $remarks, $actorAccountId) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = 'Escalated', updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("si", $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Escalated Case',
                'previous_status' => $case['status'],
                'new_status' => 'Escalated',
                'remarks' => $remarks,
                'revision_fields' => null,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            self::notifyCaseStatusChanged($case, 'Escalated');

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function getLatestRevisionRequest($complaintId) {
        $sql = "SELECT h.*, actor.first_name AS actor_first_name, actor.last_name AS actor_last_name,
                       actor.role AS actor_role
                FROM case_history h
                LEFT JOIN accounts actor ON h.created_by_account_id = actor.account_id
                WHERE h.complaint_id = ? AND h.new_status = 'Returned for Revision'
                ORDER BY h.created_at DESC, h.history_id DESC
                LIMIT 1";
        $rows = self::fetchRelated($sql, $complaintId);
        $request = $rows[0] ?? null;

        if ($request) {
            $decoded = json_decode((string) ($request['revision_fields'] ?? ''), true);
            $request['revision_fields'] = is_array($decoded) ? $decoded : [];
        }

        return $request;
    }

    public static function getLatestRevisionSubmission($complaintId) {
        $sql = "SELECT h.*, actor.first_name AS actor_first_name, actor.last_name AS actor_last_name
                FROM case_history h
                LEFT JOIN accounts actor ON h.created_by_account_id = actor.account_id
                WHERE h.complaint_id = ? AND h.action = 'Submitted Revised Complaint'
                ORDER BY h.created_at DESC, h.history_id DESC
                LIMIT 1";
        $rows = self::fetchRelated($sql, $complaintId);
        $submission = $rows[0] ?? null;

        if ($submission) {
            $decoded = json_decode((string) ($submission['revision_fields'] ?? ''), true);
            $submission['revision_fields'] = is_array($decoded) ? $decoded : [];
        }

        return $submission;
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

    public static function assignReformationCoordinator($complaintId, $coordinatorAccountId, $remarks, $actorAccountId) {
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = 'Reformation in Progress', assigned_reformation_coordinator_account_id = ?, reformation_completed_at = NULL, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("isi", $coordinatorAccountId, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Assigned Reformation Coordinator',
                'previous_status' => $case['status'],
                'new_status' => 'Reformation in Progress',
                'remarks' => $remarks,
                'assigned_coordinator_account_id' => null,
                'reformation_coordinator_account_id' => $coordinatorAccountId,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            Notification::createForUser(
                (int) $coordinatorAccountId,
                'reformation_assigned',
                'Reformation Case Assigned',
                'You were assigned as the reformation coordinator for case ' . $case['case_number'] . '.',
                'web/views/cases/show.php?id=' . $complaintId
            );

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function markReformationCompleted($complaintId, $actorAccountId) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = 'Reformation Completed', reformation_completed_at = ?, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("ssi", $now, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Reformation Completed',
                'previous_status' => $case['status'],
                'new_status' => 'Reformation Completed',
                'remarks' => null,
                'assigned_coordinator_account_id' => null,
                'reformation_coordinator_account_id' => $case['assigned_reformation_coordinator_account_id'] ?? null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            Notification::createForUser(
                (int) $case['submitted_by_account_id'],
                'reformation_completed',
                'Reformation Completed',
                'The reformation for case ' . $case['case_number'] . ' has been completed.',
                'web/views/complaints/case_details.php?id=' . $complaintId
            );

            Notification::createForHeads(
                'reformation_completed',
                'Reformation Completed',
                'The reformation for case ' . $case['case_number'] . ' has been completed.',
                'web/views/cases/show.php?id=' . $complaintId
            );

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    public static function classifyCase($complaintId, $classification, $remarks, $actorAccountId) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case) {
            return false;
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET case_classification = ?, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("ssi", $classification, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Case Classification',
                'previous_status' => $case['status'],
                'new_status' => $case['status'],
                'remarks' => $remarks,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

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
            'Under Investigation' => 'Under Investigation',
            'Returned for Revision' => 'Returned for Revision',
            'Rejected' => 'Rejected Complaint',
            'Resolved' => 'Resolved Case',
            'Reformation in Progress' => 'Reformation in Progress',
            'Reformation Completed' => 'Reformation Completed',
            'Escalated' => 'Escalated Case',
            'Archived' => 'Archived Case',
            'Unarchived' => 'Unarchived Case',
            default => 'Updated Status',
        };
    }

    public static function unarchiveCase($complaintId, $remarks, $actorAccountId) {
        $complaintId = (int) $complaintId;
        $case = self::findCase($complaintId);

        if (!$case || ($case['status'] ?? '') !== 'Archived') {
            return false;
        }

        $sql = "SELECT previous_status FROM case_history WHERE complaint_id = ? AND new_status = 'Archived'
                ORDER BY created_at DESC, history_id DESC LIMIT 1";
        $stmt = self::$conn->prepare($sql);
        $stmt->bind_param("i", $complaintId);
        $stmt->execute();
        $historyRow = $stmt->get_result()->fetch_assoc();

        $previousStatus = $historyRow['previous_status'] ?? '';
        if (!in_array($previousStatus, ['Resolved', 'Reformation in Progress', 'Reformation Completed', 'Escalated'], true)) {
            $previousStatus = 'Resolved';
        }

        self::$conn->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');
            $stmt = self::$conn->prepare("UPDATE complaints SET status = ?, updated_at = ? WHERE complaint_id = ?");
            $stmt->bind_param("ssi", $previousStatus, $now, $complaintId);
            $stmt->execute();

            self::createHistory([
                'complaint_id' => $complaintId,
                'action' => 'Unarchived Case',
                'previous_status' => $case['status'],
                'new_status' => $previousStatus,
                'remarks' => $remarks,
                'revision_fields' => null,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $actorAccountId,
                'created_at' => $now,
            ]);

            self::notifyCaseStatusChanged($case, $previousStatus);

            self::$conn->commit();
            return true;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    private static function notifyCaseStatusChanged(array $case, $newStatus) {
        $type = $newStatus === 'Resolved' ? 'case_resolved' : 'case_status_updated';
        $title = match ($newStatus) {
            'Resolved' => 'Case Resolved',
            'Escalated' => 'Case Escalated',
            default => 'Case Status Updated',
        };

        Notification::createForUser(
            (int) $case['submitted_by_account_id'],
            $type,
            $title,
            'Case ' . $case['case_number'] . ' is now ' . $newStatus . '.',
            'web/views/complaints/case_details.php?id=' . $case['complaint_id']
        );

        self::notifyRespondentsOfCaseUpdate(
            (int) $case['complaint_id'],
            $case['case_number'],
            $type,
            $title,
            'Case ' . $case['case_number'] . ' status updated to ' . $newStatus . '.'
        );
    }

    private static function notifyRespondentsOfCaseUpdate($complaintId, $caseNumber, $type, $title, $message) {
        try {
            if (!self::respondentReleased($complaintId)) return;
            foreach (self::linkedRespondentAccounts($complaintId) as $account) {
                Notification::createForUser(
                    (int) $account['account_id'],
                    $type,
                    $title,
                    $message,
                    'web/views/respondent/case_show.php?id=' . $complaintId
                );
            }
        } catch (Throwable $exception) {
            return;
        }
    }

    /*
     * Case status timeline for a respondent: only status-change events and the
     * respondent's own activity. Never exposes internal notes/remarks.
     */
    public static function respondentHistory($complaintId, $respondentAccountId) {
        $allowedActions = [
            'Under Investigation',
            'Returned for Revision',
            'Rejected Complaint',
            'Resolved Case',
            'Escalated Case',
            'Archived Case',
            'Unarchived Case',
            'Reformation in Progress',
            'Reformation Completed',
            'Counter-Statement Submitted',
            'Counter-Statement Updated',
            'Respondent Account Activated',
            'Forwarded to Respondent',
        ];

        $rows = self::getHistory($complaintId);
        $timeline = [];

        foreach ($rows as $row) {
            $isOwnAction = (int) $row['created_by_account_id'] === (int) $respondentAccountId;

            if (!$isOwnAction && !in_array($row['action'], $allowedActions, true)) {
                continue;
            }

            $timeline[] = [
                'action' => $row['action'],
                'new_status' => $row['new_status'],
                'created_at' => $row['created_at'],
                'is_own_action' => $isOwnAction,
            ];
        }

        return $timeline;
    }

    /*
     * Activity visible to staff (and, for respondent-triggered actions, to the
     * respondent themselves). Written to case_history so the existing case
     * timeline picks it up without schema changes.
     */
    public static function recordCaseActivity($complaintId, $action, $remarks, $actorAccountId) {
        $case = self::findCase($complaintId);
        if (!$case) return false;

        self::createHistory([
            'complaint_id' => $complaintId,
            'action' => $action,
            'previous_status' => $case['status'],
            'new_status' => $case['status'],
            'remarks' => $remarks,
            'revision_fields' => null,
            'assigned_coordinator_account_id' => null,
            'created_by_account_id' => $actorAccountId,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }
}
