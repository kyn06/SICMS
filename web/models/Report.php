<?php

require_once 'Model.php';
require_once __DIR__ . '/../helpers/Colleges.php';

class Report extends Model {
    private static $caseStatuses = ['Submitted', 'Verified', 'Returned for Revision', 'Rejected', 'Resolved', 'Archived'];

    public static function getDashboardData(array $filters = [], $includeOptions = true) {
        $data = self::analyticsFromDataset(self::filteredDataset($filters));

        if ($includeOptions) {
            $data['options'] = self::filterOptions();
        }

        return $data;
    }

    private static function filteredDataset(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT c.complaint_id, c.case_number, c.submitted_by_account_id,
                       c.complainant_name, c.complainant_type, c.complainant_college, c.case_classification,
                       c.status, c.assigned_coordinator_account_id, c.submitted_at, c.updated_at,
                       (SELECT GROUP_CONCAT(r.full_name ORDER BY r.respondent_id SEPARATOR ', ') FROM complaint_respondents r WHERE r.complaint_id = c.complaint_id) AS respondent_names,
                       COALESCE((SELECT MAX(ch.created_at) FROM case_history ch WHERE ch.complaint_id = c.complaint_id AND ch.action = 'Assigned Coordinator' AND ch.assigned_coordinator_account_id = c.assigned_coordinator_account_id), c.updated_at) AS assigned_at,
                       TRIM(CONCAT(COALESCE(coordinator.first_name, ''), ' ', COALESCE(coordinator.last_name, ''))) AS coordinator_name,
                       COUNT(h.hearing_id) AS hearing_count,
                       SUM(CASE WHEN h.status = 'Scheduled' THEN 1 ELSE 0 END) AS scheduled_hearing_count,
                       SUM(CASE WHEN h.status = 'Completed' THEN 1 ELSE 0 END) AS completed_hearing_count,
                       GROUP_CONCAT(CASE WHEN h.status = 'Scheduled' THEN DATE_FORMAT(h.hearing_datetime, '%Y-%m-%d %H:%i:%s') END ORDER BY h.hearing_datetime SEPARATOR '|') AS scheduled_hearing_datetimes
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN hearings h ON c.complaint_id = h.complaint_id
                $where
                GROUP BY c.complaint_id, c.case_number, c.submitted_by_account_id,
                         c.complainant_name, c.complainant_type, c.complainant_college, c.case_classification,
                         c.status, c.assigned_coordinator_account_id, c.submitted_at, c.updated_at,
                         coordinator.first_name, coordinator.last_name
                ORDER BY c.submitted_at DESC";
        return self::fetchAll($sql, $params, $types);
    }

    private static function analyticsFromDataset(array $rows) {
        $summary = [
            'total_cases' => count($rows),
            'submitted_cases' => 0,
            'verified_cases' => 0,
            'returned_for_revision_cases' => 0,
            'rejected_cases' => 0,
            'resolved_cases' => 0,
            'archived_cases' => 0,
            'scheduled_hearings' => 0,
            'hearings_today' => 0,
            'upcoming_hearings' => 0,
            'completed_hearings' => 0,
            'pending_cases' => 0,
            'ongoing_cases' => 0,
            'total_students' => 0,
        ];
        $students = [];
        $groups = ['casesByMonth' => [], 'casesByClassification' => [], 'casesByStatus' => [], 'casesByCollege' => [], 'casesByCoordinator' => [], 'hearingsByMonth' => []];
        $reportRows = [];
        $statusKeys = [
            'Submitted' => 'submitted_cases',
            'Verified' => 'verified_cases',
            'Returned for Revision' => 'returned_for_revision_cases',
            'Rejected' => 'rejected_cases',
            'Resolved' => 'resolved_cases',
            'Archived' => 'archived_cases',
        ];
        $now = new DateTimeImmutable();
        $today = $now->format('Y-m-d');

        foreach ($rows as $row) {
            $row['complainant_college'] = Colleges::canonical($row['complainant_college']);
            if (isset($statusKeys[$row['status']])) $summary[$statusKeys[$row['status']]]++;
            if (in_array($row['status'], ['Submitted', 'Returned for Revision'], true)) $summary['pending_cases']++;
            if ($row['status'] === 'Verified' || !empty($row['assigned_coordinator_account_id'])) $summary['ongoing_cases']++;
            if (($row['complainant_type'] ?? 'Student') === 'Student' && !empty($row['submitted_by_account_id'])) $students[(int) $row['submitted_by_account_id']] = true;
            $summary['scheduled_hearings'] += (int) $row['scheduled_hearing_count'];
            $summary['completed_hearings'] += (int) $row['completed_hearing_count'];

            self::increment($groups['casesByMonth'], substr($row['submitted_at'], 0, 7));
            self::increment($groups['casesByClassification'], $row['case_classification'] ?: 'Unspecified');
            self::increment($groups['casesByCollege'], $row['complainant_college'] ?: 'Unspecified');
            self::increment($groups['casesByCoordinator'], $row['coordinator_name'] ?: 'Unassigned');

            foreach (array_filter(explode('|', (string) $row['scheduled_hearing_datetimes'])) as $hearingDate) {
                self::increment($groups['hearingsByMonth'], substr($hearingDate, 0, 7));
                if (substr($hearingDate, 0, 10) === $today) $summary['hearings_today']++;
                if (new DateTimeImmutable($hearingDate) > $now) $summary['upcoming_hearings']++;
            }

            $reportRows[] = array_intersect_key($row, array_flip(['complaint_id', 'case_number', 'complainant_name', 'complainant_type', 'complainant_college', 'case_classification', 'status', 'submitted_at', 'updated_at', 'assigned_at', 'coordinator_name', 'respondent_names', 'hearing_count']));
        }

        foreach (self::$caseStatuses as $status) $groups['casesByStatus'][$status] = $summary[$statusKeys[$status]];
        $summary['total_students'] = count($students);
        $data = ['summary' => $summary, 'rows' => $reportRows];
        foreach ($groups as $key => $values) {
            $preserveOrder = $key === 'casesByStatus';
            $data[$key] = self::groupRows($values, in_array($key, ['casesByMonth', 'hearingsByMonth'], true), $preserveOrder);
        }
        return $data;
    }

    private static function increment(array &$group, $label) {
        $group[$label] = ($group[$label] ?? 0) + 1;
    }

    private static function groupRows(array $group, $chronological = false, $preserveOrder = false) {
        if ($chronological) ksort($group); elseif (!$preserveOrder) arsort($group);
        $rows = [];
        foreach ($group as $label => $total) $rows[] = ['label' => $label, 'total' => $total];
        return $rows;
    }

    public static function normalizeFilters(array $input) {
        return [
            'date_from' => self::dateValue(self::inputValue($input, 'date_from')),
            'date_to' => self::dateValue(self::inputValue($input, 'date_to')),
            'month' => self::monthValue(self::inputValue($input, 'month')),
            'year' => self::yearValue(self::inputValue($input, 'year')),
            'status' => substr(self::inputValue($input, 'status'), 0, 50),
            'classification' => substr(self::inputValue($input, 'classification'), 0, 100),
            'coordinator' => (int) self::inputValue($input, 'coordinator'),
            'college' => substr(self::inputValue($input, 'college'), 0, 255),
        ];
    }

    public static function validateFilters(array $input, array $filters) {
        $errors = [];
        foreach (['date_from', 'date_to', 'month', 'year', 'status', 'classification', 'coordinator', 'college'] as $key) {
            if (isset($input[$key]) && !is_scalar($input[$key])) $errors[] = 'Invalid filter input.';
        }
        foreach (['date_from' => 'Date From', 'date_to' => 'Date To'] as $key => $label) {
            if (($input[$key] ?? '') !== '' && $filters[$key] === '') $errors[] = "$label must be a valid date.";
        }
        if ($filters['date_from'] && $filters['date_to'] && $filters['date_from'] > $filters['date_to']) {
            $errors[] = 'Date From must be earlier than or equal to Date To.';
        }
        if (($input['month'] ?? '') !== '' && $filters['month'] === '') $errors[] = 'Month must be between 1 and 12.';
        if (($input['year'] ?? '') !== '' && $filters['year'] === '') $errors[] = 'Year must be between 2000 and 2100.';
        if (is_scalar($input['coordinator'] ?? '') && ($input['coordinator'] ?? '') !== '' && (!ctype_digit((string) $input['coordinator']) || (int) $input['coordinator'] <= 0)) $errors[] = 'Please select a valid coordinator.';
        if ($filters['status'] !== '' && !in_array($filters['status'], self::$caseStatuses, true)) $errors[] = 'Please select a valid status.';
        if ($filters['classification'] !== '' && !self::valueExists('case_classification', $filters['classification'])) $errors[] = 'Please select a valid classification.';
        if ($filters['college'] !== '' && !Colleges::contains($filters['college'])) $errors[] = 'Please select a valid college.';
        if ($filters['coordinator'] && !self::coordinatorExists($filters['coordinator'])) $errors[] = 'Please select a valid coordinator.';
        return array_values(array_unique($errors));
    }

    public static function summaryCards(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT
                    COUNT(*) AS total_cases,
                    SUM(CASE WHEN c.status IN ('Submitted', 'Returned for Revision') THEN 1 ELSE 0 END) AS pending_cases,
                    SUM(CASE WHEN c.status IN ('Verified') OR c.assigned_coordinator_account_id IS NOT NULL THEN 1 ELSE 0 END) AS ongoing_cases,
                    SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_cases,
                    SUM(CASE WHEN c.status = 'Archived' THEN 1 ELSE 0 END) AS archived_cases
                FROM complaints c
                $where";
        $caseCounts = self::fetchOne($sql, $params, $types);

        [$hearingWhere, $hearingParams, $hearingTypes] = self::hearingWhere($filters, 'h', 'c');
        $hearingSql = "SELECT COUNT(*) AS scheduled_hearings
                       FROM hearings h
                       INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                       $hearingWhere AND h.status = 'Scheduled'";
        $hearingCounts = self::fetchOne($hearingSql, $hearingParams, $hearingTypes);

        $studentSql = "SELECT COUNT(DISTINCT c.submitted_by_account_id) AS total_students
                       FROM complaints c
                       $where";
        $studentCounts = self::fetchOne($studentSql, $params, $types);

        return [
            'total_cases' => (int) ($caseCounts['total_cases'] ?? 0),
            'pending_cases' => (int) ($caseCounts['pending_cases'] ?? 0),
            'ongoing_cases' => (int) ($caseCounts['ongoing_cases'] ?? 0),
            'resolved_cases' => (int) ($caseCounts['resolved_cases'] ?? 0),
            'archived_cases' => (int) ($caseCounts['archived_cases'] ?? 0),
            'scheduled_hearings' => (int) ($hearingCounts['scheduled_hearings'] ?? 0),
            'total_students' => (int) ($studentCounts['total_students'] ?? 0),
        ];
    }

    public static function casesByMonth(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT DATE_FORMAT(c.submitted_at, '%Y-%m') AS label, COUNT(*) AS total
                FROM complaints c
                $where
                GROUP BY DATE_FORMAT(c.submitted_at, '%Y-%m')
                ORDER BY label ASC";

        return self::fetchAll($sql, $params, $types);
    }

    public static function groupedCases(array $filters, $column) {
        $allowedColumns = ['case_classification', 'status', 'complainant_college'];

        if (!in_array($column, $allowedColumns, true)) {
            return [];
        }

        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT COALESCE(NULLIF(c.$column, ''), 'Unspecified') AS label, COUNT(*) AS total
                FROM complaints c
                $where
                GROUP BY COALESCE(NULLIF(c.$column, ''), 'Unspecified')
                ORDER BY total DESC, label ASC";

        return self::fetchAll($sql, $params, $types);
    }

    public static function casesByCoordinator(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT COALESCE(NULLIF(TRIM(CONCAT(coordinator.first_name, ' ', coordinator.last_name)), ''), 'Unassigned') AS label,
                       COUNT(*) AS total
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                $where
                GROUP BY c.assigned_coordinator_account_id, coordinator.first_name, coordinator.last_name
                ORDER BY total DESC, label ASC";
        return self::fetchAll($sql, $params, $types);
    }

    public static function hearingsByMonth(array $filters) {
        [$where, $params, $types] = self::hearingWhere($filters, 'h', 'c');
        $sql = "SELECT DATE_FORMAT(h.hearing_datetime, '%Y-%m') AS label, COUNT(*) AS total
                FROM hearings h
                INNER JOIN complaints c ON h.complaint_id = c.complaint_id
                $where
                GROUP BY DATE_FORMAT(h.hearing_datetime, '%Y-%m')
                ORDER BY label ASC";

        return self::fetchAll($sql, $params, $types);
    }

    public static function reportRows(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $sql = "SELECT c.case_number, c.complainant_name, c.complainant_college,
                       c.case_classification, c.status, c.submitted_at, c.updated_at,
                       CONCAT(COALESCE(coordinator.first_name, ''), ' ', COALESCE(coordinator.last_name, '')) AS coordinator_name,
                       COUNT(h.hearing_id) AS hearing_count
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN hearings h ON c.complaint_id = h.complaint_id
                $where
                GROUP BY c.complaint_id, c.case_number, c.complainant_name, c.complainant_college,
                         c.case_classification, c.status, c.submitted_at, c.updated_at, coordinator.first_name, coordinator.last_name
                ORDER BY c.submitted_at DESC
                LIMIT 500";

        return self::fetchAll($sql, $params, $types);
    }

    public static function filterOptions() {
        return [
            'statuses' => self::$caseStatuses,
            'classifications' => self::singleColumn("SELECT DISTINCT case_classification FROM complaints WHERE case_classification IS NOT NULL AND case_classification <> '' ORDER BY case_classification"),
            'colleges' => Colleges::all(),
            'coordinators' => self::fetchAll("SELECT account_id, first_name, last_name, role
                                              FROM accounts
                                              WHERE status = 'active'
                                                AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ('super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head')
                                              ORDER BY first_name, last_name"),
        ];
    }

    private static function caseWhere(array $filters, $alias = 'c') {
        $where = ["1 = 1"];
        $params = [];
        $types = '';

        if (!empty($filters['date_from'])) {
            $where[] = "$alias.submitted_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "$alias.submitted_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        if (!empty($filters['month'])) {
            $where[] = "MONTH($alias.submitted_at) = ?";
            $params[] = (int) $filters['month'];
            $types .= 'i';
        }

        if (!empty($filters['year'])) {
            $where[] = "YEAR($alias.submitted_at) = ?";
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        if (!empty($filters['status'])) {
            $where[] = "$alias.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['classification'])) {
            $where[] = "$alias.case_classification = ?";
            $params[] = $filters['classification'];
            $types .= 's';
        }

        if (!empty($filters['coordinator'])) {
            $where[] = "$alias.assigned_coordinator_account_id = ?";
            $params[] = (int) $filters['coordinator'];
            $types .= 'i';
        }

        if (!empty($filters['college'])) {
            $aliases = Colleges::aliasesFor($filters['college']);
            $where[] = "LOWER(TRIM($alias.complainant_college)) IN (?, ?)";
            $params[] = strtolower($aliases[0]);
            $params[] = strtolower($aliases[1] ?? $aliases[0]);
            $types .= 'ss';
        }

        return ['WHERE ' . implode(' AND ', $where), $params, $types];
    }

    private static function hearingWhere(array $filters, $hearingAlias = 'h', $caseAlias = 'c') {
        return self::caseWhere($filters, $caseAlias);
    }

    private static function fetchOne($sql, array $params = [], $types = '') {
        $rows = self::fetchAll($sql, $params, $types);

        return $rows[0] ?? [];
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

    private static function singleColumn($sql) {
        $rows = self::fetchAll($sql);
        $values = [];

        foreach ($rows as $row) {
            $values[] = reset($row);
        }

        return $values;
    }

    private static function valueExists($column, $value) {
        if (!in_array($column, ['case_classification', 'complainant_college'], true)) return false;
        return !empty(self::fetchOne("SELECT 1 AS found FROM complaints WHERE $column = ? LIMIT 1", [$value], 's'));
    }

    private static function coordinatorExists($accountId) {
        return !empty(self::fetchOne("SELECT 1 AS found FROM accounts WHERE account_id = ? AND status = 'active' LIMIT 1", [(int) $accountId], 'i'));
    }

    private static function dateValue($value) {
        $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
        return $date && $date->format('Y-m-d') === (string) $value ? $value : '';
    }

    private static function inputValue(array $input, $key) {
        return isset($input[$key]) && is_scalar($input[$key]) ? trim((string) $input[$key]) : '';
    }

    private static function monthValue($value) {
        $month = (int) $value;

        return ($month >= 1 && $month <= 12) ? $month : '';
    }

    private static function yearValue($value) {
        $year = (int) $value;

        return ($year >= 2000 && $year <= 2100) ? $year : '';
    }
}
