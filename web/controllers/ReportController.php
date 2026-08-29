<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Report.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../services/SicmsReportPdf.php';

class ReportController {
    private $database;
    private $db;
    private $user;
    private $allowedRoles = ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head'];

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        $filters = Report::normalizeFilters($_GET);
        $errors = Report::validateFilters($_GET, $filters);
        $isAjax = ($_GET['ajax'] ?? '') === '1';
        $mode = ($_GET['report'] ?? 'analytics') === 'yearly' ? 'yearly' : 'analytics';

        if ($errors && $isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }

        if ($errors && !empty($_GET['export'])) {
            http_response_code(422);
            echo htmlspecialchars(implode(' ', $errors), ENT_QUOTES, 'UTF-8');
            exit;
        }

        if ($mode === 'yearly') {
            $data = Report::getYearlyData($filters, !$isAjax);

            if ($isAjax) {
                $this->jsonYearly($filters, $data);
            }

            if (!empty($_GET['export'])) {
                if (!Security::validateCsrfToken($_GET['csrf_token'] ?? '')) {
                    http_response_code(403);
                    echo 'Invalid or missing CSRF token.';
                    exit;
                }

                $this->exportYearly($_GET['export'], $filters, $data);
            }

            return [
                'user' => $this->user,
                'filters' => $filters,
                'mode' => 'yearly',
                'yearly' => $data['yearly'],
                'options' => $data['options'] ?? [],
                'isPrint' => ($_GET['export'] ?? '') === 'print',
                'errors' => $errors,
            ];
        }

        $data = Report::getDashboardData($filters, !$isAjax);

        if ($isAjax) {
            $this->json($filters, $data);
        }

        if (!empty($_GET['export'])) {
            if (!Security::validateCsrfToken($_GET['csrf_token'] ?? '')) {
                http_response_code(403);
                echo 'Invalid or missing CSRF token.';
                exit;
            }

            $this->export($_GET['export'], $filters, $data);
        }

        return [
            'user' => $this->user,
            'filters' => $filters,
            'mode' => 'analytics',
            'summary' => $data['summary'],
            'charts' => [
                'casesByMonth' => $data['casesByMonth'],
                'casesByYear' => $data['casesByYear'],
                'casesByClassification' => $data['casesByClassification'],
                'casesByStatus' => $data['casesByStatus'],
                'casesByCollege' => $data['casesByCollege'],
                'casesByCoordinator' => $data['casesByCoordinator'],
                'hearingsByMonth' => $data['hearingsByMonth'],
                'sexComplainants' => $data['casesBySex'],
                'sexRespondents' => $data['respondentsBySex'],
            ],
            'sexRange' => $this->sexDisaggregatedRange($filters, $data['rows']),
            'rows' => $data['rows'],
            'options' => $data['options'],
            'isPrint' => false,
            'errors' => $errors,
        ];
    }

    private function exportYearly($type, array $filters, array $data) {
        if ($type === 'excel') {
            AuditLog::record($this->user, 'Report Generation', 'Generated Excel yearly report.');
            $this->exportExcelYearly($filters, $data);
        }

        if ($type === 'pdf') {
            AuditLog::record($this->user, 'Report Generation', 'Generated PDF yearly report.');
            $this->exportPdfYearly($filters, $data);
        }

        if ($type === 'print') {
            AuditLog::record($this->user, 'Report Generation', 'Opened printable yearly report.');
            return;
        }
    }

    private function export($type, array $filters, array $data) {
        if ($type === 'excel') {
            AuditLog::record($this->user, 'Report Generation', 'Generated Excel report.');
            $this->exportExcel($filters, $data);
        }

        if ($type === 'pdf') {
            AuditLog::record($this->user, 'Report Generation', 'Generated PDF report.');
            $this->exportPdf($filters, $data);
        }

        if ($type === 'print') {
            AuditLog::record($this->user, 'Report Generation', 'Opened printable report.');
            return;
        }
    }

    private function json(array $filters, array $data) {
        header('Content-Type: application/json; charset=utf-8');

        try {
            echo json_encode([
                'success' => true,
                'mode' => 'analytics',
                'filters' => $filters,
                'summary' => $data['summary'],
                'sexRange' => $this->sexDisaggregatedRange($filters, $data['rows']),
                'charts' => [
                    'casesByMonth' => $this->chartPayload($data['casesByMonth']),
                    'casesByYear' => $this->chartPayload($data['casesByYear']),
                    'casesByClassification' => $this->chartPayload($data['casesByClassification']),
                    'casesByStatus' => $this->chartPayload($data['casesByStatus']),
                    'casesByCollege' => $this->chartPayload($data['casesByCollege']),
                    'casesByCoordinator' => $this->chartPayload($data['casesByCoordinator']),
                    'hearingsByMonth' => $this->chartPayload($data['hearingsByMonth']),
                    'sexComplainants' => $this->chartPayload($data['casesBySex']),
                    'sexRespondents' => $this->chartPayload($data['respondentsBySex']),
                ],
                'rows' => $data['rows'],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to generate report data.']);
        }

        exit;
    }

    private function jsonYearly(array $filters, array $data) {
        header('Content-Type: application/json; charset=utf-8');

        try {
            echo json_encode([
                'success' => true,
                'mode' => 'yearly',
                'filters' => $filters,
                'yearly' => $data['yearly'],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to generate yearly report data.']);
        }

        exit;
    }

    private function chartPayload(array $rows) {
        return [
            'labels' => array_column($rows, 'label'),
            'values' => array_map('intval', array_column($rows, 'total')),
        ];
    }

    private function exportExcel(array $filters, array $data) {
        $filename = 'sicms-report-' . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse}th{background:#123c1b;color:#fff;font-weight:bold}th,td{border:1px solid #b8c7b5;padding:7px;white-space:nowrap}.title{font-size:18px;font-weight:bold}.meta{color:#536052}</style></head><body><table>';
        echo '<tr><td class="title" colspan="9">SICMS Case Report</td></tr>';
        echo '<tr><td class="meta" colspan="9">Generated by ' . $this->html($this->displayName()) . ' on ' . $this->html(date('F d, Y h:i A')) . '</td></tr>';
        echo '<tr><td class="meta" colspan="9">Filters: ' . $this->html(implode('; ', $this->filterSummary($filters, $data['options'] ?? [])) ?: 'All records') . '</td></tr><tr></tr>';
        echo '<tr><th>Case Number</th><th>Complainant</th><th>Complainant Type</th><th>Gender</th><th>Classification</th><th>Status</th><th>Coordinator</th><th>College</th><th>Date Submitted</th></tr>';

        foreach ($data['rows'] as $row) {
            echo '<tr>';
            foreach ([$row['case_number'], $row['complainant_name'], $row['complainant_type'] ?? 'Student', $row['complainant_gender'] ?: 'Not provided', $row['case_classification'], $row['status'], trim($row['coordinator_name']) ?: 'Unassigned', $row['complainant_college'], $row['submitted_at']] as $value) {
                echo '<td>' . $this->html($this->excelCell($value)) . '</td>';
            }
            echo '</tr>';
        }

        echo '</table></body></html>';

        exit;
    }

    private function exportPdf(array $filters, array $data) {
        $pdf = new SicmsReportPdf(
            __DIR__ . '/../../public/assets/clsulogo.png',
            $this->displayName()
        );
        $pdf->AddPage();
        $pdf->reportHeading($this->allFilterValues($filters, $data['options'] ?? []));
        $pdf->executiveSummary($data['summary']);
        $pdf->sectionTitle('Case Report');

        if (empty($data['rows'])) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(0, 10, 'No records found for the selected filters.', 1, 1, 'C');
        } else {
            $pdf->caseTableHeader();
            foreach ($data['rows'] as $row) {
                $pdf->caseRow([
                    $row['case_number'],
                    $row['complainant_name'] . ' (' . ($row['complainant_type'] ?? 'Student') . ')',
                    $row['complainant_gender'] ?: 'Not provided',
                    $row['case_classification'],
                    $row['status'],
                    trim($row['coordinator_name']) ?: 'Unassigned',
                    $row['complainant_college'],
                    date('Y-m-d', strtotime($row['submitted_at'])),
                ]);
            }
        }

        $pdf->Output('D', 'sicms-report-' . date('Y-m-d') . '.pdf');
        exit;
    }

    private function exportExcelYearly(array $filters, array $data) {
        $yearly = $data['yearly'];
        $filename = 'sicms-yearly-report-' . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo '<html><head><meta charset="UTF-8"><style>table{border-collapse:collapse}th{background:#123c1b;color:#fff;font-weight:bold}th,td{border:1px solid #b8c7b5;padding:7px;white-space:nowrap}.title{font-size:18px;font-weight:bold}.meta{color:#536052}.sub{font-size:13px;font-weight:bold;color:#123c1b}</style></head><body>';

        echo '<table>';
        echo '<tr><td class="title">YEARLY CASES REPORT</td></tr>';
        echo '<tr><td class="meta">Generated by ' . $this->html($this->displayName()) . ' on ' . $this->html(date('F d, Y h:i A')) . '</td></tr>';
        echo '<tr><td class="meta">Year: ' . $this->html(!empty($filters['year']) ? $filters['year'] : 'All Years') . ' | Filters: ' . $this->html(implode('; ', $this->filterSummary($filters, $data['options'] ?? [])) ?: 'All records') . '</td></tr>';
        echo '</table><br>';

        $this->excelSection('1. Summary', [
            ['Total Cases', $yearly['summary']['total_cases'] ?? 0],
            ['Pending', $yearly['summary']['pending_cases'] ?? 0],
            ['Ongoing', $yearly['summary']['ongoing_cases'] ?? 0],
            ['Resolved', $yearly['summary']['resolved_cases'] ?? 0],
            ['Archived', $yearly['summary']['archived_cases'] ?? 0],
            ['Rejected', $yearly['summary']['rejected_cases'] ?? 0],
        ], ['Metric', 'Count']);

        $this->excelSection(
            '2. Yearly Case Trend',
            array_map(fn($index) => [$yearly['trend']['labels'][$index], $yearly['trend']['values'][$index]], array_keys($yearly['trend']['labels'] ?: [])),
            ['Year', 'Total Cases']
        );

        $this->excelPivotSection('3. Case Status Breakdown', $yearly['statusByYear']);
        $this->excelPivotSection('4. Case Classification Breakdown', $yearly['classificationByYear']);

        if (!empty($yearly['monthly'])) {
            $monthlyRows = array_map(fn($month) => [$month['month'], $month['total']], $yearly['monthly']);
            $monthlyRows[] = ['Total', array_sum(array_column($yearly['monthly'], 'total'))];
            $this->excelSection(
                '5. Monthly Case Breakdown (' . (int) $filters['year'] . ')',
                $monthlyRows,
                ['Month', 'Cases']
            );
        }

        $comparison = $yearly['comparison'] ?? [];
        if (!empty($comparison['has_previous'])) {
            $this->excelSection('6. Year-over-Year Comparison', [
                [$comparison['current_year'] . ' Total Cases', $comparison['current_total']],
                [$comparison['previous_year'] . ' Total Cases', $comparison['previous_total']],
                [ucfirst($comparison['direction']), (($comparison['difference'] > 0 ? '+' : '') . $comparison['difference']) . ' cases'],
                ['Percentage Change', number_format($comparison['percent'], 2) . '%'],
            ], ['Metric', 'Value']);
        }

        echo '</body></html>';
        exit;
    }

    private function excelPivotSection($title, array $pivot) {
        $headers = array_merge(['Year'], $pivot['columns'] ?? [], ['Total']);
        $rows = [];

        foreach (($pivot['rows'] ?? []) as $row) {
            $line = [$row['year']];
            foreach (($pivot['columns'] ?? []) as $column) {
                $line[] = $row['values'][$column] ?? 0;
            }
            $line[] = $row['total'];
            $rows[] = $line;
        }

        $totalLine = ['Total'];
        foreach (($pivot['columns'] ?? []) as $column) {
            $totalLine[] = $pivot['grand'][$column] ?? 0;
        }
        $totalLine[] = $pivot['grand_total'] ?? 0;
        $rows[] = $totalLine;

        $this->excelSection($title, $rows, $headers);
    }

    private function excelSection($title, array $rows, array $headers = []) {
        echo '<table><tr><td class="sub">' . $this->html($title) . '</td></tr></table>';
        echo '<table>';
        if (!empty($headers)) {
            echo '<tr>';
            foreach ($headers as $header) {
                echo '<th>' . $this->html($header) . '</th>';
            }
            echo '</tr>';
        }
        foreach ($rows as $row) {
            echo '<tr>';
            foreach ($row as $cell) {
                echo '<td>' . $this->html($this->excelCell($cell)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</table><br>';
    }

    private function exportPdfYearly(array $filters, array $data) {
        $yearly = $data['yearly'];
        $pdf = new SicmsReportPdf(
            __DIR__ . '/../../public/assets/clsulogo.png',
            $this->displayName()
        );
        $pdf->AddPage();
        $pdf->yearlyHeading(!empty($filters['year']) ? (string) $filters['year'] : 'All Years');
        $pdf->yearlySummaryBlock($yearly['summary']);

        $pdf->sectionTitle('2. Yearly Case Trend');
        $trendRows = [];
        foreach ($yearly['trend']['labels'] as $index => $label) {
            $trendRows[] = [(string) $label, (string) $yearly['trend']['values'][$index]];
        }
        if (empty($trendRows)) {
            $trendRows[] = ['No data', '0'];
        }
        $pdf->renderStatsTable(['Year', 'Total Cases'], $pdf->columnWidths(2), $trendRows, ['Total', (string) array_sum($yearly['trend']['values'])]);

        $pdf->sectionTitle('3. Case Status Breakdown');
        $this->pdfPivotTable($pdf, $yearly['statusByYear']);

        $pdf->sectionTitle('4. Case Classification Breakdown');
        $this->pdfPivotTable($pdf, $yearly['classificationByYear']);

        if (!empty($yearly['monthly'])) {
            $pdf->sectionTitle('5. Monthly Case Breakdown (' . (int) $filters['year'] . ')');
            $monthlyRows = array_map(fn($month) => [$month['month'], (string) $month['total']], $yearly['monthly']);
            $monthlyTotal = array_sum(array_column($yearly['monthly'], 'total'));
            $pdf->renderStatsTable(['Month', 'Cases'], $pdf->columnWidths(2), $monthlyRows, ['Total', (string) $monthlyTotal]);
        }

        $pdf->sectionTitle('6. Year-over-Year Comparison');
        $comparison = $yearly['comparison'] ?? [];
        if (empty($comparison['has_previous'])) {
            $pdf->SetFont('Helvetica', '', 9);
            $pdf->Cell(0, 10, 'No previous year data available for comparison.', 1, 1, 'C');
        } else {
            $comparisonRows = [
                [$comparison['current_year'] . ' Total Cases', (string) $comparison['current_total']],
                [$comparison['previous_year'] . ' Total Cases', (string) $comparison['previous_total']],
                [ucfirst($comparison['direction']), (($comparison['difference'] > 0 ? '+' : '') . $comparison['difference']) . ' cases'],
                ['Percentage Change', number_format($comparison['percent'], 2) . '%'],
            ];
            $pdf->renderStatsTable(['Metric', 'Value'], $pdf->columnWidths(2), $comparisonRows);
        }

        $pdf->Output('D', 'sicms-yearly-report-' . date('Y-m-d') . '.pdf');
        exit;
    }

    private function pdfPivotTable(SicmsReportPdf $pdf, array $pivot) {
        $columns = array_merge(['Year'], $pivot['columns'] ?? [], ['Total']);
        $widths = $pdf->columnWidths(count($columns));
        $rows = [];

        foreach (($pivot['rows'] ?? []) as $row) {
            $line = [(string) $row['year']];
            foreach (($pivot['columns'] ?? []) as $column) {
                $line[] = (string) ($row['values'][$column] ?? 0);
            }
            $line[] = (string) $row['total'];
            $rows[] = $line;
        }

        if (empty($rows)) {
            $pdf->renderStatsTable(['Year', 'Total'], $pdf->columnWidths(2), [['No data', '0']], ['Total', '0']);
            return;
        }

        $grand = ['Total'];
        foreach (($pivot['columns'] ?? []) as $column) {
            $grand[] = (string) ($pivot['grand'][$column] ?? 0);
        }
        $grand[] = (string) $pivot['grand_total'];

        $pdf->renderStatsTable($columns, $widths, $rows, $grand);
    }

    private function filterSummary(array $filters, array $options) {
        $labels = ['date_from' => 'Date From', 'date_to' => 'Date To', 'month' => 'Month', 'year' => 'Year', 'status' => 'Status', 'classification' => 'Classification', 'college' => 'College', 'case_source' => 'Case Source'];
        $summary = [];

        foreach ($labels as $key => $label) {
            if (($filters[$key] ?? '') === '' || ($filters[$key] ?? 0) === 0) continue;
            $value = $key === 'month' ? date('F', mktime(0, 0, 0, (int) $filters[$key], 1)) : $filters[$key];
            $summary[] = $label . ': ' . $value;
        }

        if (!empty($filters['coordinator'])) {
            $name = 'Account #' . (int) $filters['coordinator'];
            foreach ($options['coordinators'] ?? [] as $coordinator) {
                if ((int) $coordinator['account_id'] === (int) $filters['coordinator']) {
                    $name = trim($coordinator['first_name'] . ' ' . $coordinator['last_name']);
                    break;
                }
            }
            $summary[] = 'Coordinator: ' . $name;
        }

        return $summary;
    }

    private function allFilterValues(array $filters, array $options) {
        $values = [
            'Date From' => $filters['date_from'] ?: 'All',
            'Date To' => $filters['date_to'] ?: 'All',
            'Month' => !empty($filters['month']) ? date('F', mktime(0, 0, 0, (int) $filters['month'], 1)) : 'All',
            'Year' => !empty($filters['year']) ? (string) $filters['year'] : 'All',
            'Status' => $filters['status'] ?: 'All',
            'Classification' => $filters['classification'] ?: 'All',
            'Case Source' => !empty($filters['case_source']) ? $filters['case_source'] : 'All',
            'Coordinator' => 'All',
            'College' => $filters['college'] ?: 'All',
        ];

        if (!empty($filters['coordinator'])) {
            $values['Coordinator'] = 'Account #' . (int) $filters['coordinator'];
            foreach ($options['coordinators'] ?? [] as $coordinator) {
                if ((int) $coordinator['account_id'] === (int) $filters['coordinator']) {
                    $values['Coordinator'] = trim($coordinator['first_name'] . ' ' . $coordinator['last_name']);
                    break;
                }
            }
        }

        return $values;
    }

    private function displayName() {
        return trim(($this->user['first_name'] ?? '') . ' ' . ($this->user['last_name'] ?? '')) ?: ($this->user['email'] ?? 'User');
    }

    private function html($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    private function excelCell($value) {
        $value = str_replace(["\t", "\r", "\n"], ' ', (string) $value);
        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function sexDisaggregatedRange(array $filters, array $rows) {
        if (!empty($filters['year'])) {
            return (string) (int) $filters['year'];
        }

        $years = [];

        foreach ($rows as $row) {
            if (!empty($row['submitted_at'])) $years[] = (int) substr($row['submitted_at'], 0, 4);
        }

        $from = !empty($filters['date_from']) ? (int) substr($filters['date_from'], 0, 4) : ($years ? min($years) : 0);
        $to = !empty($filters['date_to']) ? (int) substr($filters['date_to'], 0, 4) : ($years ? max($years) : 0);

        if ($from === 0 || $to === 0) {
            return (string) date('Y');
        }

        return $from === $to ? (string) $from : $from . '–' . $to;
    }

    private function authenticate() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        Report::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }

        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role']));

        if (!in_array($roleKey, $this->allowedRoles, true)) {
            http_response_code(403);
            echo 'Access denied. Admin or SDRU staff account required.';
            exit;
        }
    }
}
