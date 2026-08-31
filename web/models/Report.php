<?php

require_once 'Model.php';
require_once __DIR__ . '/../helpers/Colleges.php';
require_once __DIR__ . '/../helpers/Courses.php';

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
                       c.complainant_name, c.complainant_type, c.complainant_gender, c.complainant_college, c.case_classification,
                       c.status, c.assigned_coordinator_account_id, c.submitted_at, c.updated_at,
                       (SELECT GROUP_CONCAT(r.full_name ORDER BY r.respondent_id SEPARATOR ', ') FROM complaint_respondents r WHERE r.complaint_id = c.complaint_id) AS respondent_names,
                       (SELECT GROUP_CONCAT(COALESCE(r.gender, '') ORDER BY r.respondent_id SEPARATOR '|') FROM complaint_respondents r WHERE r.complaint_id = c.complaint_id) AS respondent_genders,
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
                         c.complainant_name, c.complainant_type, c.complainant_gender, c.complainant_college, c.case_classification,
                         c.status, c.assigned_coordinator_account_id, c.submitted_at, c.updated_at,
                         COALESCE(c.original_case_date, DATE(c.submitted_at)),
                         coordinator.first_name, coordinator.last_name
                ORDER BY COALESCE(c.original_case_date, DATE(c.submitted_at)) DESC";
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
        $groups = ['casesByMonth' => [], 'casesByYear' => [], 'casesByClassification' => [], 'casesByStatus' => [], 'casesByCollege' => [], 'casesByCoordinator' => [], 'casesBySex' => [], 'respondentsBySex' => [], 'hearingsByMonth' => []];
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
            if ($row['status'] === 'Verified' || (!empty($row['assigned_coordinator_account_id']) && !in_array($row['status'], ['Resolved', 'Archived'], true))) $summary['ongoing_cases']++;
            if (($row['complainant_type'] ?? 'Student') === 'Student' && !empty($row['submitted_by_account_id'])) $students[(int) $row['submitted_by_account_id']] = true;
            $summary['scheduled_hearings'] += (int) $row['scheduled_hearing_count'];
            $summary['completed_hearings'] += (int) $row['completed_hearing_count'];

            self::increment($groups['casesByMonth'], substr($row['case_date'] ?? $row['submitted_at'], 0, 7));
            self::increment($groups['casesByYear'], substr($row['case_date'] ?? $row['submitted_at'], 0, 4));
            self::increment($groups['casesByClassification'], $row['case_classification'] ?: 'Unspecified');
            self::increment($groups['casesByCollege'], $row['complainant_college'] ?: 'Unspecified');
            self::increment($groups['casesByCoordinator'], $row['coordinator_name'] ?: 'Unassigned');
            self::increment($groups['casesBySex'], self::genderBucket($row['complainant_gender'] ?? ''));

            if (($row['respondent_genders'] ?? null) !== null) {
                foreach (explode('|', $row['respondent_genders']) as $respondentGender) {
                    self::increment($groups['respondentsBySex'], self::genderBucket($respondentGender));
                }
            }

            foreach (array_filter(explode('|', (string) $row['scheduled_hearing_datetimes'])) as $hearingDate) {
                self::increment($groups['hearingsByMonth'], substr($hearingDate, 0, 7));
                if (substr($hearingDate, 0, 10) === $today) $summary['hearings_today']++;
                if (new DateTimeImmutable($hearingDate) > $now) $summary['upcoming_hearings']++;
            }

            $reportRows[] = array_intersect_key($row, array_flip(['complaint_id', 'case_number', 'complainant_name', 'complainant_type', 'complainant_gender', 'complainant_college', 'case_classification', 'status', 'submitted_at', 'updated_at', 'assigned_at', 'coordinator_name', 'respondent_names', 'hearing_count']));
        }

        foreach (self::$caseStatuses as $status) $groups['casesByStatus'][$status] = $summary[$statusKeys[$status]];
        $summary['total_students'] = count($students);
        $groups['casesByMonth'] = self::fillMonthWindow($groups['casesByMonth']);
        $groups['hearingsByMonth'] = self::fillMonthWindow($groups['hearingsByMonth']);
        $groups['casesBySex'] = self::orderedSexBuckets($groups['casesBySex']);
        $groups['respondentsBySex'] = self::orderedSexBuckets($groups['respondentsBySex']);
        $data = ['summary' => $summary, 'rows' => $reportRows];
        foreach ($groups as $key => $values) {
            $preserveOrder = $key === 'casesByStatus' || $key === 'casesBySex' || $key === 'respondentsBySex';
            $data[$key] = self::groupRows($values, in_array($key, ['casesByMonth', 'casesByYear', 'hearingsByMonth'], true), $preserveOrder);
        }

        foreach (['casesByMonth', 'hearingsByMonth'] as $monthKey) {
            $data[$monthKey] = array_map(function ($row) {
                $row['label'] = date('M \'y', strtotime($row['label'] . '-01'));
                return $row;
            }, $data[$monthKey]);
        }

        return $data;
    }

    private static function genderBucket($raw) {
        $value = strtolower(trim((string) $raw));

        if ($value === '') {
            return 'Unspecified';
        }

        if ($value === 'male' || $value === 'm') {
            return 'Male';
        }

        if ($value === 'female' || $value === 'f') {
            return 'Female';
        }

        return 'Other';
    }

    private static function orderedSexBuckets(array $group) {
        $ordered = [];

        foreach (['Male', 'Female', 'Other', 'Unspecified'] as $bucket) {
            if (($group[$bucket] ?? 0) > 0) {
                $ordered[$bucket] = $group[$bucket];
            }
        }

        return $ordered;
    }

    private static function fillMonthWindow(array $group) {
        if ($group === []) {
            return [];
        }

        $endKey = (new DateTimeImmutable())->format('Y-m');
        $maxKey = max(array_keys($group));

        if ($maxKey <= $endKey) {
            $end = new DateTimeImmutable($endKey . '-01 00:00:00');
        } else {
            $end = new DateTimeImmutable($maxKey . '-01 00:00:00');
        }

        $start = $end->modify('-11 months');
        $filled = [];

        for ($i = 0; $i < 12; $i++) {
            $key = $start->modify("+{$i} months")->format('Y-m');
            $filled[$key] = $group[$key] ?? 0;
        }

        return $filled;
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
            'case_source' => self::caseSource(self::inputValue($input, 'case_source')),
            'sex' => substr(self::inputValue($input, 'sex'), 0, 20),
            'year_level' => substr(self::inputValue($input, 'year_level'), 0, 50),
            'department' => substr(self::inputValue($input, 'department'), 0, 255),
        ];
    }

    public static function validateFilters(array $input, array $filters) {
        $errors = [];
        foreach (['date_from', 'date_to', 'month', 'year', 'status', 'classification', 'coordinator', 'college', 'case_source', 'sex', 'year_level', 'department'] as $key) {
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
        if ($filters['sex'] !== '' && !in_array($filters['sex'], ['Male', 'Female', 'Other', 'Unspecified'], true)) $errors[] = 'Please select a valid sex.';
        if ($filters['year_level'] !== '' && !in_array($filters['year_level'], array_values(Courses::yearOptions()), true)) $errors[] = 'Please select a valid year level.';
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
        $sql = "SELECT DATE_FORMAT(COALESCE(c.original_case_date, DATE(c.submitted_at)), '%Y-%m') AS label, COUNT(*) AS total
                FROM complaints c
                $where
                GROUP BY DATE_FORMAT(COALESCE(c.original_case_date, DATE(c.submitted_at)), '%Y-%m')
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

    public static function getYearlyData(array $filters = [], $includeOptions = true) {
        $data = ['yearly' => self::yearlyReportData($filters)];

        if ($includeOptions) {
            $data['options'] = self::filterOptions();
        }

        return $data;
    }

    public static function yearlyReportData(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $trendRows = self::fetchAll(
            "SELECT YEAR(COALESCE(c.original_case_date, DATE(c.submitted_at))) AS report_year, COUNT(*) AS total
             FROM complaints c
             $where
             GROUP BY report_year
             ORDER BY report_year ASC",
            $params,
            $types
        );

        $yearLabels = [];
        $trendValues = [];
        $yearTotals = [];

        foreach ($trendRows as $row) {
            $yearLabels[] = (string) $row['report_year'];
            $trendValues[] = (int) $row['total'];
            $yearTotals[(int) $row['report_year']] = (int) $row['total'];
        }

        return [
            'years' => array_map('intval', array_keys($yearTotals)),
            'summary' => self::yearlySummary($filters),
            'trend' => ['labels' => $yearLabels, 'values' => $trendValues],
            'statusByYear' => self::yearlyPivot($filters, 'status'),
            'classificationByYear' => self::yearlyPivot($filters, 'case_classification'),
            'monthly' => empty($filters['year']) ? [] : self::monthlyBreakdown($filters),
            'comparison' => self::yearComparison($filters, $yearTotals),
            'yearToYear' => self::yearToYearChanges($yearTotals),
            'statuses' => self::$caseStatuses,
        ];
    }

    public static function filterOptions() {
        return [
            'statuses' => self::$caseStatuses,
            'years' => self::singleColumn("SELECT DISTINCT YEAR(COALESCE(original_case_date, DATE(submitted_at))) AS report_year FROM complaints ORDER BY report_year ASC"),
            'classifications' => self::singleColumn("SELECT DISTINCT case_classification FROM complaints WHERE case_classification IS NOT NULL AND case_classification <> '' ORDER BY case_classification"),
            'colleges' => Colleges::all(),
            'case_sources' => ['Online Submission', 'Legacy'],
            'sexes' => ['Male', 'Female', 'Other', 'Unspecified'],
            'year_levels' => array_values(Courses::yearOptions()),
            'departments' => self::singleColumn("SELECT DISTINCT complainant_course FROM complaints WHERE complainant_course IS NOT NULL AND TRIM(complainant_course) <> '' ORDER BY complainant_course"),
            'coordinators' => self::fetchAll("SELECT account_id, first_name, last_name, role
                                              FROM accounts
                                              WHERE status = 'active'
                                                AND LOWER(REPLACE(REPLACE(role, '_', '-'), ' ', '-')) IN ('super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head')
                                              ORDER BY first_name, last_name"),
        ];
    }

    private static function yearlySummary(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $summary = self::fetchOne(
            "SELECT COUNT(*) AS total_cases,
                    SUM(CASE WHEN c.status = 'Submitted' THEN 1 ELSE 0 END) AS submitted_cases,
                    SUM(CASE WHEN c.status = 'Verified' THEN 1 ELSE 0 END) AS verified_cases,
                    SUM(CASE WHEN c.status = 'Returned for Revision' THEN 1 ELSE 0 END) AS returned_for_revision_cases,
                    SUM(CASE WHEN c.status = 'Rejected' THEN 1 ELSE 0 END) AS rejected_cases,
                    SUM(CASE WHEN c.status = 'Resolved' THEN 1 ELSE 0 END) AS resolved_cases,
                    SUM(CASE WHEN c.status = 'Archived' THEN 1 ELSE 0 END) AS archived_cases,
                    SUM(CASE WHEN c.status IN ('Submitted', 'Returned for Revision') THEN 1 ELSE 0 END) AS pending_cases,
                    SUM(CASE WHEN (c.status = 'Verified' OR (c.assigned_coordinator_account_id IS NOT NULL AND c.status NOT IN ('Resolved', 'Archived'))) THEN 1 ELSE 0 END) AS ongoing_cases
             FROM complaints c
             $where",
            $params,
            $types
        );
        $studentCount = self::fetchOne("SELECT COUNT(DISTINCT c.submitted_by_account_id) AS student_count FROM complaints c $where", $params, $types);

        foreach (array_keys($summary) as $key) {
            $summary[$key] = (int) $summary[$key];
        }
        $summary['total_students'] = (int) ($studentCount['student_count'] ?? 0);

        return $summary;
    }

    private static function yearlyPivot(array $filters, $column) {
        if (!in_array($column, ['status', 'case_classification'], true)) {
            return ['years' => [], 'columns' => [], 'rows' => [], 'grand' => [], 'grand_total' => 0, 'chart' => ['labels' => [], 'datasets' => []]];
        }

        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $rows = self::fetchAll(
            "SELECT YEAR(COALESCE(c.original_case_date, DATE(c.submitted_at))) AS report_year,
                    COALESCE(NULLIF(c.$column, ''), 'Unspecified') AS bucket,
                    COUNT(*) AS total
             FROM complaints c
             $where
             GROUP BY report_year, bucket
             ORDER BY report_year ASC, bucket ASC",
            $params,
            $types
        );

        $columns = ($column === 'status')
            ? self::$caseStatuses
            : array_values(array_unique(array_map(fn($row) => $row['bucket'], $rows)));
        if ($column !== 'status') usort($columns, 'strnatcasecmp');

        $yearSet = [];
        $years = [];
        $grid = [];
        $totals = [];

        foreach ($rows as $row) {
            $year = (int) $row['report_year'];
            $bucket = $row['bucket'];
            if (!isset($yearSet[$year])) {
                $yearSet[$year] = true;
                $years[] = $year;
                $grid[$year] = [];
                $totals[$year] = 0;
            }
            $grid[$year][$bucket] = (int) $row['total'];
            $totals[$year] += (int) $row['total'];
        }

        sort($years);

        $grand = array_fill_keys($columns, 0);
        $grandTotal = 0;
        $pivotRows = [];

        foreach ($years as $year) {
            $values = [];
            $rowTotal = 0;
            foreach ($columns as $col) {
                $count = $grid[$year][$col] ?? 0;
                $values[$col] = $count;
                $rowTotal += $count;
                $grand[$col] += $count;
            }
            $grandTotal += $rowTotal;
            $pivotRows[] = ['year' => $year, 'values' => $values, 'total' => $rowTotal];
        }

        $datasets = [];
        foreach ($columns as $index => $col) {
            $datasets[] = [
                'label' => $col,
                'data' => array_map(fn($row) => $row['values'][$col], $pivotRows),
            ];
        }

        return [
            'years' => $years,
            'columns' => $columns,
            'rows' => $pivotRows,
            'grand' => $grand,
            'grand_total' => $grandTotal,
            'chart' => ['labels' => array_map('strval', $years), 'datasets' => $datasets],
        ];
    }

    private static function monthlyBreakdown(array $filters) {
        [$where, $params, $types] = self::caseWhere($filters, 'c');
        $rows = self::fetchAll(
            "SELECT MONTH(COALESCE(c.original_case_date, DATE(c.submitted_at))) AS month_no, COUNT(*) AS total
             FROM complaints c
             $where
             GROUP BY month_no
             ORDER BY month_no ASC",
            $params,
            $types
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['month_no']] = (int) $row['total'];
        }

        $result = [];
        for ($month = 1; $month <= 12; $month++) {
            $result[] = [
                'month' => date('F', mktime(0, 0, 0, $month, 1)),
                'number' => $month,
                'year' => (int) $filters['year'],
                'total' => $map[$month] ?? 0,
            ];
        }

        return $result;
    }

    private static function yearComparison(array $filters, array $yearTotals) {
        $selectedYear = !empty($filters['year']) ? (int) $filters['year'] : 0;

        if ($selectedYear > 0) {
            [$where, $params, $types] = self::caseWhere($filters, 'c');
            $currentTotal = (int) (self::fetchOne("SELECT COUNT(*) AS total FROM complaints c $where", $params, $types)['total'] ?? 0);

            $previousYear = $selectedYear - 1;
            $previousFilters = $filters;
            $previousFilters['year'] = $previousYear;
            [$previousWhere, $previousParams, $previousTypes] = self::caseWhere($previousFilters, 'c');
            $previousTotal = (int) (self::fetchOne("SELECT COUNT(*) AS total FROM complaints c $previousWhere", $previousParams, $previousTypes)['total'] ?? 0);

            return self::comparisonResult($selectedYear, $previousYear, $currentTotal, $previousTotal, $previousTotal > 0);
        }

        $years = array_keys($yearTotals);
        sort($years);
        $count = count($years);

        if ($count === 0) {
            return self::comparisonResult(0, null, 0, 0, false);
        }

        if ($count === 1) {
            return self::comparisonResult($years[0], null, $yearTotals[$years[0]], 0, false);
        }

        return self::comparisonResult(
            $years[$count - 1],
            $years[$count - 2],
            $yearTotals[$years[$count - 1]],
            $yearTotals[$years[$count - 2]]
        );
    }

    private static function comparisonResult($currentYear, $previousYear, $currentTotal, $previousTotal, $hasPrevious = true) {
        $difference = $currentTotal - $previousTotal;

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'difference' => $difference,
            'percent' => $previousTotal > 0
                ? round(($difference / $previousTotal) * 100, 2)
                : ($currentTotal > 0 ? 100.0 : 0.0),
            'direction' => $difference > 0 ? 'increase' : ($difference < 0 ? 'decrease' : 'stable'),
            'has_previous' => $hasPrevious,
        ];
    }

    private static function yearToYearChanges(array $yearTotals) {
        $years = array_keys($yearTotals);
        sort($years);
        $changes = [];

        for ($index = 1; $index < count($years); $index++) {
            $currentYear = (int) $years[$index];
            $previousYear = (int) $years[$index - 1];
            $currentTotal = (int) $yearTotals[$currentYear];
            $previousTotal = (int) $yearTotals[$previousYear];
            $difference = $currentTotal - $previousTotal;
            $changes[] = [
                'year' => $currentYear,
                'total' => $currentTotal,
                'previous_year' => $previousYear,
                'previous_total' => $previousTotal,
                'difference' => $difference,
                'percent' => $previousTotal > 0 ? round(($difference / $previousTotal) * 100, 2) : ($currentTotal > 0 ? 100.0 : 0.0),
                'direction' => $difference > 0 ? 'increase' : ($difference < 0 ? 'decrease' : 'stable'),
            ];
        }

        return $changes;
    }

    private static function caseWhere(array $filters, $alias = 'c') {
        $where = ["1 = 1"];
        $params = [];
        $types = '';

        if (!empty($filters['date_from'])) {
            $where[] = "COALESCE($alias.original_case_date, DATE($alias.submitted_at)) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = "COALESCE($alias.original_case_date, DATE($alias.submitted_at)) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        if (!empty($filters['month'])) {
            $where[] = "MONTH(COALESCE($alias.original_case_date, DATE($alias.submitted_at))) = ?";
            $params[] = (int) $filters['month'];
            $types .= 'i';
        }

        if (!empty($filters['year'])) {
            $where[] = "YEAR(COALESCE($alias.original_case_date, DATE($alias.submitted_at))) = ?";
            $params[] = (int) $filters['year'];
            $types .= 'i';
        }

        if (!empty($filters['case_source']) && $filters['case_source'] !== 'All') {
            if ($filters['case_source'] === 'Online Submission') {
                $where[] = "($alias.case_source = 'Online Submission' OR $alias.case_source IS NULL)";
            } else {
                $where[] = "$alias.case_source = ?";
                $params[] = $filters['case_source'];
                $types .= 's';
            }
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

        if (!empty($filters['sex'])) {
            if ($filters['sex'] === 'Unspecified') {
                $where[] = "($alias.complainant_gender IS NULL OR TRIM($alias.complainant_gender) = '')";
            } else {
                $sexMap = ['Male' => ['male', 'm'], 'Female' => ['female', 'f'], 'Other' => ['other', 'o']];
                $vals = $sexMap[$filters['sex']] ?? [$filters['sex']];
                $where[] = 'LOWER(TRIM(' . $alias . '.complainant_gender)) IN (' . implode(',', array_fill(0, count($vals), '?')) . ')';
                foreach ($vals as $v) { $params[] = strtolower($v); $types .= 's'; }
            }
        }

        if (!empty($filters['year_level'])) {
            $where[] = "$alias.complainant_year_level = ?";
            $params[] = $filters['year_level'];
            $types .= 's';
        }

        if (!empty($filters['department'])) {
            $where[] = "LOWER(TRIM($alias.complainant_course)) = ?";
            $params[] = strtolower($filters['department']);
            $types .= 's';
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

    private static function caseSource($value) {
        $value = trim((string) $value);
        return in_array($value, ['Online Submission', 'Legacy', 'All'], true) ? $value : '';
    }
}
