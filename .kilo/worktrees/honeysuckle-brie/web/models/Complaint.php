<?php

require_once 'Model.php';

class Complaint extends Model {
    protected static $table = 'complaints';
    protected static $primaryKey = 'complaint_id';

    private static function nextDailyCaseNumber($dateKey) {
        $prefix = 'SDRU-' . $dateKey . '-';
        $like = $prefix . '%';
        $stmt = self::$conn->prepare(
            "SELECT COALESCE(MAX(CAST(RIGHT(case_number, 4) AS UNSIGNED)), 0) AS last_sequence
             FROM complaints
             WHERE case_number LIKE ? AND case_number REGEXP '^SDRU-[0-9]{8}-[0-9]{4}$'"
        );
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $lastSequence = (int) ($stmt->get_result()->fetch_assoc()['last_sequence'] ?? 0);

        if ($lastSequence >= 9999) {
            throw new RuntimeException('The daily case number limit has been reached.');
        }

        return $prefix . str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);
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
                    SUM(CASE WHEN status IN ('Under Investigation', 'Returned for Revision') THEN 1 ELSE 0 END) AS pending_cases,
                    SUM(CASE WHEN (status IN ('Under Investigation') OR assigned_coordinator_account_id IS NOT NULL) AND status NOT IN ('Resolved', 'Reformation in Progress', 'Reformation Completed') THEN 1 ELSE 0 END) AS ongoing_cases,
                    SUM(CASE WHEN status IN ('Resolved', 'Reformation in Progress', 'Reformation Completed') THEN 1 ELSE 0 END) AS resolved_cases
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
        $dateKey = date('Ymd');
        $lockName = 'sicms_case_number_' . $dateKey;
        $lockStmt = self::$conn->prepare('SELECT GET_LOCK(?, 10) AS acquired');
        $lockStmt->bind_param('s', $lockName);
        $lockStmt->execute();
        $lockAcquired = (int) ($lockStmt->get_result()->fetch_assoc()['acquired'] ?? 0) === 1;

        if (!$lockAcquired) {
            throw new RuntimeException('Unable to allocate a case number. Please try again.');
        }

        self::$conn->begin_transaction();

        try {
            $complaint['case_number'] = self::nextDailyCaseNumber($dateKey);
            $createdComplaint = parent::create($complaint);
            $complaintId = $createdComplaint['complaint_id'];

            foreach ($respondents as $respondent) {
                self::createRelatedRecord('complaint_respondents', [
                    'complaint_id' => $complaintId,
                    'respondent_type' => $respondent['respondent_type'],
                    'full_name' => $respondent['full_name'],
                    'gender' => $respondent['gender'] ?? '',
                    'age' => $respondent['age'] ?? null,
                    'student_no' => $respondent['student_no'],
                    'employee_no' => $respondent['employee_no'],
                    'college' => $respondent['college'],
                    'office_department' => $respondent['office_department'],
                    'course_year' => $respondent['course_year'],
                    'position' => $respondent['position'],
                    'affiliation' => $respondent['affiliation'],
                    'contact_info' => $respondent['contact_info'],
                    'email' => $respondent['email'] ?? '',
                    'address' => $respondent['address'] ?? '',
                    'details' => $respondent['details'],
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }

            foreach ($witnesses as $witness) {
                self::createRelatedRecord('complaint_witnesses', [
                    'complaint_id' => $complaintId,
                    'person_type' => $witness['person_type'],
                    'full_name' => $witness['full_name'],
                    'gender' => $witness['gender'] ?? '',
                    'age' => $witness['age'] ?? null,
                    'student_no' => $witness['student_no'],
                    'contact_info' => $witness['contact_info'],
                    'email' => $witness['email'] ?? '',
                    'address' => $witness['address'] ?? '',
                    'statement' => $witness['statement'],
                    'employee_no' => $witness['employee_no'],
                    'college' => $witness['college'],
                    'office_department' => $witness['office_department'],
                    'position' => $witness['position'],
                    'affiliation' => $witness['affiliation'] ?? '',
                    'course_year' => $witness['course_year'],
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
        } finally {
            $releaseStmt = self::$conn->prepare('SELECT RELEASE_LOCK(?)');
            $releaseStmt->bind_param('s', $lockName);
            $releaseStmt->execute();
        }
    }

    public static function submitRevision($complaintId, $accountId, array $updates, ?array $respondents, ?array $witnesses, array $evidenceFiles, array $removeEvidenceIds) {
        self::$conn->begin_transaction();

        try {
            $stmt = self::$conn->prepare("SELECT * FROM complaints WHERE complaint_id = ? AND submitted_by_account_id = ? FOR UPDATE");
            $stmt->bind_param('ii', $complaintId, $accountId);
            $stmt->execute();
            $case = $stmt->get_result()->fetch_assoc();

            if (!$case || $case['status'] !== 'Returned for Revision') {
                throw new RuntimeException('This complaint is not available for revision.');
            }

            $revisedFields = [];
            try {
                $revisionRequest = CaseRecord::getLatestRevisionRequest($complaintId);
                $requestedFields = (array) ($revisionRequest['revision_fields'] ?? []);
            } catch (Throwable $revisionLookupException) {
                $requestedFields = [];
            }

            if ($requestedFields) {
                foreach (['complaint_details', 'incident_location'] as $field) {
                    if (in_array($field, $requestedFields, true)
                        && array_key_exists($field, $updates)
                        && trim((string) $updates[$field]) !== trim((string) ($case[$field] ?? ''))) {
                        $revisedFields[] = $field;
                    }
                }

                $oldTimestamp = strtotime((string) ($case['incident_datetime'] ?? ''));
                $newTimestamp = strtotime((string) ($updates['incident_datetime'] ?? $case['incident_datetime'] ?? ''));
                if (in_array('incident_date', $requestedFields, true)
                    && date('Y-m-d', (int) $newTimestamp) !== date('Y-m-d', (int) $oldTimestamp)) {
                    $revisedFields[] = 'incident_date';
                }
                if (in_array('incident_time', $requestedFields, true)
                    && date('H:i', (int) $newTimestamp) !== date('H:i', (int) $oldTimestamp)) {
                    $revisedFields[] = 'incident_time';
                }

                if ($respondents !== null && in_array('respondents', $requestedFields, true)) {
                    $storedRespondents = array_map(static function (array $row): array {
                        return [
                            'respondent_type' => trim((string) ($row['respondent_type'] ?? '')),
                            'full_name' => trim((string) ($row['full_name'] ?? '')),
                            'gender' => trim((string) ($row['gender'] ?? '')),
                            'student_no' => trim((string) ($row['student_no'] ?? '')),
                            'employee_no' => trim((string) ($row['employee_no'] ?? '')),
                            'college' => trim((string) ($row['college'] ?? '')),
                            'office_department' => trim((string) ($row['office_department'] ?? '')),
                            'course_year' => trim((string) ($row['course_year'] ?? '')),
                            'position' => trim((string) ($row['position'] ?? '')),
                            'affiliation' => trim((string) ($row['affiliation'] ?? '')),
                            'contact_info' => trim((string) ($row['contact_info'] ?? '')),
                            'details' => trim((string) ($row['details'] ?? '')),
                            'age' => trim((string) ($row['age'] ?? '')),
                            'email' => trim((string) ($row['email'] ?? '')),
                            'address' => trim((string) ($row['address'] ?? '')),
                        ];
                    }, CaseRecord::getRespondents($complaintId));
                    if (self::peopleChanged($respondents, $storedRespondents)) $revisedFields[] = 'respondents';
                }

                if ($witnesses !== null && in_array('witnesses', $requestedFields, true)) {
                    $storedWitnesses = array_map(static function (array $row): array {
                        return [
                            'person_type' => trim((string) ($row['person_type'] ?? '')),
                            'full_name' => trim((string) ($row['full_name'] ?? '')),
                            'gender' => trim((string) ($row['gender'] ?? '')),
                            'student_no' => trim((string) ($row['student_no'] ?? '')),
                            'contact_info' => trim((string) ($row['contact_info'] ?? '')),
                            'statement' => trim((string) ($row['statement'] ?? '')),
                            'employee_no' => trim((string) ($row['employee_no'] ?? '')),
                            'college' => trim((string) ($row['college'] ?? '')),
                            'office_department' => trim((string) ($row['office_department'] ?? '')),
                            'position' => trim((string) ($row['position'] ?? '')),
                            'affiliation' => trim((string) ($row['affiliation'] ?? '')),
                            'course_year' => trim((string) ($row['course_year'] ?? '')),
                            'age' => trim((string) ($row['age'] ?? '')),
                            'email' => trim((string) ($row['email'] ?? '')),
                            'address' => trim((string) ($row['address'] ?? '')),
                        ];
                    }, CaseRecord::getWitnesses($complaintId));
                    if (self::peopleChanged($witnesses, $storedWitnesses)) $revisedFields[] = 'witnesses';
                }

                if (in_array('evidence', $requestedFields, true) && ($removeEvidenceIds || $evidenceFiles)) {
                    $revisedFields[] = 'evidence';
                }
            }

            $revisedFieldList = array_values(array_unique($revisedFields));

            $updates['status'] = 'Under Investigation';
            $updates['updated_at'] = date('Y-m-d H:i:s');
            $set = implode(', ', array_map(fn($column) => "$column = ?", array_keys($updates)));
            $values = array_values($updates);
            $types = str_repeat('s', count($values)) . 'ii';
            $values[] = $complaintId;
            $values[] = $accountId;
            $stmt = self::$conn->prepare("UPDATE complaints SET $set WHERE complaint_id = ? AND submitted_by_account_id = ? AND status = 'Returned for Revision'");
            $stmt->bind_param($types, ...$values);
            $stmt->execute();

            if ($respondents !== null) {
                self::replacePeople('complaint_respondents', 'respondent_id', $complaintId, $respondents, [
                    'respondent_type', 'full_name', 'gender', 'age', 'student_no', 'employee_no', 'college',
                    'office_department', 'course_year', 'position', 'affiliation', 'contact_info', 'email', 'address', 'details',
                ]);
            }

            if ($witnesses !== null) {
                self::replacePeople('complaint_witnesses', 'witness_id', $complaintId, $witnesses, [
                    'person_type', 'full_name', 'gender', 'age', 'student_no', 'contact_info', 'email', 'address',
                    'statement', 'employee_no', 'college', 'office_department', 'position', 'affiliation', 'course_year',
                ]);
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

            self::createRelatedRecord('case_history', [
                'complaint_id' => $complaintId,
                'action' => 'Submitted Revised Complaint',
                'previous_status' => 'Returned for Revision',
                'new_status' => 'Under Investigation',
                'remarks' => 'Student submitted the requested revisions.',
                'revision_fields' => $revisedFieldList ? json_encode($revisedFieldList) : null,
                'assigned_coordinator_account_id' => null,
                'created_by_account_id' => $accountId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            self::$conn->commit();
            return $removedPaths;
        } catch (Throwable $exception) {
            self::$conn->rollback();
            throw $exception;
        }
    }

    private static function replacePeople($table, $primaryKey, $complaintId, array $rows, array $columns) {
        $stmt = self::$conn->prepare("DELETE FROM $table WHERE complaint_id = ?");
        $stmt->bind_param('i', $complaintId);
        $stmt->execute();

        foreach ($rows as $row) {
            $data = ['complaint_id' => $complaintId];
            foreach ($columns as $column) $data[$column] = $row[$column] ?? '';
            $data['created_at'] = date('Y-m-d H:i:s');
            self::createRelatedRecord($table, $data);
        }
    }

    private static function peopleChanged(array $newRows, array $oldRows): bool {
        $normalize = static function (array $rows): array {
            $normalized = [];
            foreach ($rows as $row) {
                foreach ($row as $key => $value) {
                    $row[$key] = is_scalar($value) ? (string) $value : '';
                }
                ksort($row);
                $normalized[] = $row;
            }
            usort($normalized, static fn($a, $b) => strcmp((string) json_encode($a), (string) json_encode($b)));
            return $normalized;
        };
        return $normalize($newRows) !== $normalize($oldRows);
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
