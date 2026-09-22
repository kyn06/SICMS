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
            $this->exportPdf(AuditLog::exportRows($filters), $filters);
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

    private function authenticateHead() {
        if (!isset($_SESSION['email'])) {
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
            http_response_code(403);
            echo 'Access denied. Head SDRU account required.';
            exit;
        }
    }

    private function exportPdf(array $rows, array $filters = []) {
        AuditLog::record($this->user, 'Audit Log Export', 'Generated audit logs PDF export.');

        $logoPath = __DIR__ . '/../../public/assets/clsulogo.png';
        $generatedBy = trim(($this->user['first_name'] ?? '') . ' ' . ($this->user['last_name'] ?? '')) ?: ($this->user['email'] ?? 'User');

        $pdf = new AuditLogPdf($logoPath, $generatedBy);
        $pdf->AddPage();
        $pdf->reportHeading($filters);
        $pdf->tableHeader();

        $rowNumber = 0;
        foreach ($rows as $row) {
            $rowNumber++;
            $pdf->tableRow($rowNumber, $row);
        }

        $pdf->recordCount(count($rows));

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="sicms-audit-logs-' . date('Y-m-d') . '.pdf"');
        $pdf->Output('D', 'sicms-audit-logs-' . date('Y-m-d') . '.pdf');
        exit;
    }
}
