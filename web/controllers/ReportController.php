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
            'summary' => $data['summary'],
            'charts' => [
                'casesByMonth' => $data['casesByMonth'],
                'casesByClassification' => $data['casesByClassification'],
                'casesByStatus' => $data['casesByStatus'],
                'casesByCollege' => $data['casesByCollege'],
                'casesByCoordinator' => $data['casesByCoordinator'],
                'hearingsByMonth' => $data['hearingsByMonth'],
            ],
            'rows' => $data['rows'],
            'options' => $data['options'],
            'isPrint' => false,
            'errors' => $errors,
        ];
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
                'filters' => $filters,
                'summary' => $data['summary'],
                'charts' => [
                    'casesByMonth' => $this->chartPayload($data['casesByMonth']),
                    'casesByClassification' => $this->chartPayload($data['casesByClassification']),
                    'casesByStatus' => $this->chartPayload($data['casesByStatus']),
                    'casesByCollege' => $this->chartPayload($data['casesByCollege']),
                    'casesByCoordinator' => $this->chartPayload($data['casesByCoordinator']),
                    'hearingsByMonth' => $this->chartPayload($data['hearingsByMonth']),
                ],
                'rows' => $data['rows'],
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to generate report data.']);
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
        echo '<tr><td class="title" colspan="8">SICMS Case Report</td></tr>';
        echo '<tr><td class="meta" colspan="8">Generated by ' . $this->html($this->displayName()) . ' on ' . $this->html(date('F d, Y h:i A')) . '</td></tr>';
        echo '<tr><td class="meta" colspan="8">Filters: ' . $this->html(implode('; ', $this->filterSummary($filters, $data['options'] ?? [])) ?: 'All records') . '</td></tr><tr></tr>';
        echo '<tr><th>Case Number</th><th>Complainant</th><th>Complainant Type</th><th>Classification</th><th>Status</th><th>Coordinator</th><th>College</th><th>Date Submitted</th></tr>';

        foreach ($data['rows'] as $row) {
            echo '<tr>';
            foreach ([$row['case_number'], $row['complainant_name'], $row['complainant_type'] ?? 'Student', $row['case_classification'], $row['status'], trim($row['coordinator_name']) ?: 'Unassigned', $row['complainant_college'], $row['submitted_at']] as $value) {
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

    private function filterSummary(array $filters, array $options) {
        $labels = ['date_from' => 'Date From', 'date_to' => 'Date To', 'month' => 'Month', 'year' => 'Year', 'status' => 'Status', 'classification' => 'Classification', 'college' => 'College'];
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
