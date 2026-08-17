<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class AttachmentController {
    private $user;
    private $database;

    public function __construct() {
        Security::startSession();
        if (!isset($_SESSION['email'])) {
            http_response_code(401);
            exit('Authentication required.');
        }
        $this->database = new Database();
        $db = $this->database->getConnection();
        User::setConnection($db); CaseRecord::setConnection($db); AuditLog::setConnection($db);
        $this->user = User::findByEmail($_SESSION['email']);
        if (!$this->user || $this->user['status'] !== 'active') {
            http_response_code(403);
            exit('Access denied.');
        }
    }

    public function serve($evidenceId, $mode = 'view') {
        $file = CaseRecord::findEvidence((int) $evidenceId);
        if (!$file || !$this->canAccess($file)) {
            http_response_code(403);
            exit('Access denied.');
        }

        $storageRoot = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence');
        $absolutePath = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file['file_path']));
        if (!$storageRoot || !$absolutePath || !str_starts_with($absolutePath, $storageRoot . DIRECTORY_SEPARATOR) || !is_file($absolutePath)) {
            http_response_code(404);
            exit('Attachment not found.');
        }

        $inlineTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        $disposition = $mode === 'download' || !in_array($file['mime_type'], $inlineTypes, true) ? 'attachment' : 'inline';
        $filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($file['original_filename'])) ?: 'attachment';
        AuditLog::record($this->user, 'Attachment Access', ucfirst($disposition) . ' access to ' . $filename . ' for ' . $file['case_number'] . '.');

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($absolutePath));
        header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($filename, '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        readfile($absolutePath);
        exit;
    }

    private function canAccess(array $file) {
        $role = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));
        if ($role === 'student') return (int) $file['submitted_by_account_id'] === (int) $this->user['account_id'];
        if ($role === 'coordinator') return (int) $file['assigned_coordinator_account_id'] === (int) $this->user['account_id'];
        return in_array($role, ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head'], true);
    }
}
