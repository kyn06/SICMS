<?php

require_once 'Model.php';

class Report extends Model {
    private static $caseStatuses = ['Submitted', 'Verified', 'Returned for Revision', 'Rejected', 'Resolved', 'Archived'];

    public static function getDashboardData(array $filters = []) {
        return [
            'summary' => self::summaryCards($filters),
            'casesByMonth' => self::casesByMonth($filters),
            'casesByClassification' => self::groupedCases($filters, 'case_classification'),
            'casesByStatus' => self::groupedCases($filters, 'status'),
            'casesByCollege' => self::groupedCases($filters, 'complainant_college'),
            'hearingsByMonth' => self::hearingsByMonth($filters),
            'rows' => self::reportRows($filters),
            'options' => self::filterOptions(),
        ];
    }

    public static function normalizeFilters(array $input) {
        return [
            'date_from' => self::dateValue($input['date_from'] ?? ''),
            'date_to' => self::dateValue($input['date_to'] ?? ''),
            'month' => self::monthValue($input['month'] ?? ''),
            'year' => self::yearValue($input['year'] ?? ''),
            'status' => trim($input['status'] ?? ''),
            'classification' => trim($input['classification'] ?? ''),
            'coordinator' => (int) ($input['coordinator'] ?? 0),
            'college' => trim($input['college'] ?? ''),
        ];
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

        $studentSql = "SELECT COUNT(*) AS total_students
                       FROM accounts
                       WHERE LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) = 'student'";
        $studentCounts = self::fetchOne($studentSql);

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
                       c.case_classification, c.status, c.submitted_at,
                       CONCAT(COALESCE(coordinator.first_name, ''), ' ', COALESCE(coordinator.last_name, '')) AS coordinator_name,
                       COUNT(h.hearing_id) AS hearing_count
                FROM complaints c
                LEFT JOIN accounts coordinator ON c.assigned_coordinator_account_id = coordinator.account_id
                LEFT JOIN hearings h ON c.complaint_id = h.complaint_id
                $where
                GROUP BY c.complaint_id, c.case_number, c.complainant_name, c.complainant_college,
                         c.case_classification, c.status, c.submitted_at, coordinator.first_name, coordinator.last_name
                ORDER BY c.submitted_at DESC
                LIMIT 500";

        return self::fetchAll($sql, $params, $types);
    }

    public static function filterOptions() {
        return [
            'statuses' => self::$caseStatuses,
            'classifications' => self::singleColumn("SELECT DISTINCT case_classification FROM complaints WHERE case_classification IS NOT NULL AND case_classification <> '' ORDER BY case_classification"),
            'colleges' => self::singleColumn("SELECT DISTINCT complainant_college FROM complaints WHERE complainant_college IS NOT NULL AND complainant_college <> '' ORDER BY complainant_college"),
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
            $where[] = "$alias.complainant_college = ?";
            $params[] = $filters['college'];
            $types .= 's';
        }

        return ['WHERE ' . implode(' AND ', $where), $params, $types];
    }

    private static function hearingWhere(array $filters, $hearingAlias = 'h', $caseAlias = 'c') {
        [$where, $params, $types] = self::caseWhere($filters, $caseAlias);
        $conditions = substr($where, 6);
        $whereParts = [$conditions];

        if (!empty($filters['date_from'])) {
            $whereParts[] = "$hearingAlias.hearing_datetime >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $whereParts[] = "$hearingAlias.hearing_datetime <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        return ['WHERE ' . implode(' AND ', $whereParts), $params, $types];
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

    private static function dateValue($value) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? $value : '';
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
