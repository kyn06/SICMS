<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/CaseApproval.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../services/AuditLogPdf.php';

class AuditLogController {
    private $database;
    private $db;
    private $user;
    private $headRoles = ['head-of-sdru', 'sdru-head'];

    public function __construct() {
        Security::startSession();

        $this->authenticateHead();
    }

    public function index() {
        $filters = AuditLog::filters($_GET);

        $filterError = '';
        if (!empty($filters['date_from']) && !empty($filters['date_to']) && $filters['date_from'] > $filters['date_to']) {
            $filterError = 'The start date must be on or before the end date.';
            $filters['date_from'] = '';
            $filters['date_to'] = '';
        }

        $perPage = 10;
        $total = AuditLog::countLogs($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($filters['page'] > $totalPages) {
            $filters['page'] = $totalPages;
        }

        if (($_GET['export'] ?? '') === 'pdf') {
            $selectedColumns = $_GET['columns'] ?? [];
            $this->exportPdf(AuditLog::exportRows($filters), $filters, $selectedColumns);
        }

        return [
            'user' => $this->user,
            'filters' => $filters,
            'logs' => AuditLog::listLogs($filters, $perPage),
            'approvals' => CaseApproval::pendingForHead(),
            'options' => AuditLog::filterOptions(),
            'pagination' => [
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $filters['page'],
                'totalPages' => $totalPages,
            ],
            'filterError' => $filterError,
        ];
    }

    public function search() {
        try {
            $filters = AuditLog::filters($_GET);
            $filterError = '';
            if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_from'] > $filters['date_to']) {
                $filterError = 'The start date must be on or before the end date.';
                $filters['date_from'] = '';
                $filters['date_to'] = '';
            }

            $perPage = 10;
            $total = AuditLog::countLogs($filters);
            $totalPages = max(1, (int) ceil($total / $perPage));
            $filters['page'] = min($filters['page'], $totalPages);

            $this->respondJson([
                'success' => true,
                'logs' => AuditLog::listLogs($filters, $perPage),
                'filters' => $filters,
                'pagination' => [
                    'total' => $total,
                    'perPage' => $perPage,
                    'currentPage' => $filters['page'],
                    'totalPages' => $totalPages,
                ],
                'filterError' => $filterError,
            ]);
        } catch (Throwable $exception) {
            $this->respondJson(['success' => false, 'message' => 'Unable to load audit logs right now.'], 500);
        }
    }

    public static function isAjaxRequest() {
        if (($_GET['ajax'] ?? '') === '1') {
            return true;
        }

        return strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
            || strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
    }

    private function respondJson(array $payload, int $status = 200) {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    private function authenticateHead() {
        if (!isset($_SESSION['email'])) {
            if (self::isAjaxRequest()) {
                $this->respondJson(['success' => false, 'message' => 'Your session has expired. Refresh the page and sign in again.'], 401);
            }

            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        AuditLog::setConnection($this->db);
        CaseApproval::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);
        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));

        if (!$this->user || $this->user['status'] !== 'active' || !in_array($roleKey, $this->headRoles, true)) {
            if (self::isAjaxRequest()) {
                $this->respondJson(['success' => false, 'message' => 'Access denied. Head SDRU account required.'], 403);
            }

            http_response_code(403);
            echo 'Access denied. Head SDRU account required.';
            exit;
        }
    }

    private function exportPdf(array $rows, array $filters = [], array $selectedColumns = []) {
        AuditLog::record($this->user, 'Audit Log Export', 'Generated audit logs PDF export.');

        $logoPath = __DIR__ . '/../../public/assets/clsulogo.png';
        $generatedBy = trim(($this->user['first_name'] ?? '') . ' ' . ($this->user['last_name'] ?? '')) ?: ($this->user['email'] ?? 'User');

        $pdf = new AuditLogPdf($logoPath, $generatedBy, $selectedColumns);
        $pdf->AddPage();
        $pdf->reportHeading($filters);
        $pdf->tableHeader();

        foreach ($rows as $row) {
            $pdf->tableRow($row);
        }

        $pdf->recordCount(count($rows));

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="daris-audit-logs-' . date('Y-m-d') . '.pdf"');
        $pdf->Output('D', 'daris-audit-logs-' . date('Y-m-d') . '.pdf');
        exit;
    }
}
