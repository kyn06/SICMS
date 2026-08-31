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
            'Resolved',
            'Archived',
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

        if (empty($filters['include_archived'])) {
            $sql .= " AND c.status != 'Archived'";
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

    public static function findEvidence($evidenceId) {
        $sql = "SELECT e.*, c.submitted_by_account_id, c.assigned_coordinator_account_id, c.case_number, c.case_source
                FROM complaint_evidence e
                INNER JOIN complaints c ON c.complaint_id = e.complaint_id
                WHERE e.evidence_id = ? LIMIT 1";
        $rows = self::fetchRelated($sql, $evidenceId);
        return $rows[0] ?? null;
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
        $sql = "SELECT c.*, a.first_name AS submitted_by_first_name, a.last_name AS submitted_by_last_name
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
            'web/views/complaints/case_details.php?id=' . $case['complaint_id']
        );
    }
}
