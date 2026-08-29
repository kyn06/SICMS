<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

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
        $perPage = 10;
        $total = AuditLog::countLogs($filters);
        $totalPages = max(1, (int) ceil($total / $perPage));

        if ($filters['page'] > $totalPages) {
            $filters['page'] = $totalPages;
        }

        if (($_GET['export'] ?? '') === 'pdf') {
            $this->exportPdf(AuditLog::exportRows($filters));
        }

        return [
            'user' => $this->user,
            'filters' => $filters,
            'logs' => AuditLog::listLogs($filters, $perPage),
            'options' => AuditLog::filterOptions(),
            'pagination' => [
                'total' => $total,
                'perPage' => $perPage,
                'currentPage' => $filters['page'],
                'totalPages' => $totalPages,
            ],
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

        $this->user = User::findByEmail($_SESSION['email']);
        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));

        if (!$this->user || $this->user['status'] !== 'active' || !in_array($roleKey, $this->headRoles, true)) {
            http_response_code(403);
            echo 'Access denied. Head SDRU account required.';
            exit;
        }
    }

    private function exportPdf(array $rows) {
        AuditLog::record($this->user, 'Audit Log Export', 'Generated audit logs PDF export.');

        $lines = [
            'SICMS Audit Logs',
            'Generated: ' . date('F d, Y h:i A'),
            '',
        ];

        foreach (array_slice($rows, 0, 40) as $row) {
            $lines[] = date('Y-m-d H:i', strtotime($row['created_at'])) . ' | ' .
                $row['user_name'] . ' | ' .
                $row['action'] . ' | ' .
                $row['description'];
        }

        $pdf = $this->simplePdf($lines);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="sicms-audit-logs-' . date('Y-m-d') . '.pdf"');
        echo $pdf;
        exit;
    }

    private function simplePdf(array $lines) {
        $content = "BT\n/F1 10 Tf\n40 790 Td\n";

        foreach ($lines as $index => $line) {
            if ($index > 0) {
                $content .= "0 -15 Td\n";
            }

            $content .= '(' . $this->pdfText($line) . ") Tj\n";
        }

        $content .= "ET";
        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n$object\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n$xref\n%%EOF";

        return $pdf;
    }

    private function pdfText($value) {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], substr((string) $value, 0, 130));
    }
}
