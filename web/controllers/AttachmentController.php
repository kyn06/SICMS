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

        if ($disposition === 'inline'
            && in_array($file['mime_type'], ['image/jpeg', 'image/png'], true)
            && $this->serveStamped($absolutePath, $file['mime_type'], $filename, $disposition)
        ) {
            exit;
        }

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . filesize($absolutePath));
        header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($filename, '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        readfile($absolutePath);
        exit;
    }

    private function serveStamped(string $absolutePath, string $mimeType, string $filename, string $disposition): bool {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagecreatetruecolor')) {
            return false;
        }

        $contents = @file_get_contents($absolutePath);

        if ($contents === false) {
            return false;
        }

        $source = @imagecreatefromstring($contents);

        if ($source === false) {
            return false;
        }

        $width = (int) imagesx($source);
        $height = (int) imagesy($source);

        if ($width < 80 || $height < 80) {
            imagedestroy($source);
            return false;
        }

        $image = imagecreatetruecolor($width, $height);

        if (!$image) {
            imagedestroy($source);
            return false;
        }

        imagecopy($image, $source, 0, 0, 0, 0, $width, $height);
        imagedestroy($source);

        $name = trim(($this->user['first_name'] ?? '') . ' ' . ($this->user['last_name'] ?? ''));

        if ($name === '') {
            $name = (string) ($this->user['email'] ?? 'User');
        }

        $stamp = sprintf(
            'Viewed by %s (%s) | %s | SICMS',
            $name,
            (string) ($this->user['account_id'] ?? '-'),
            date('M d, Y h:i A')
        );

        $maxTextWidth = $width - 16;
        $font = 5;

        while ($font > 2 && imagefontwidth($font) * strlen($stamp) > $maxTextWidth) {
            $font--;
        }

        while (strlen($stamp) > 3 && imagefontwidth($font) * strlen($stamp) > $maxTextWidth) {
            $stamp = substr($stamp, 0, -4) . '...';
        }

        $stripHeight = max(imagefontheight($font) + 8, (int) round($height * 0.05));
        imagefilledrectangle($image, 0, $height - $stripHeight, $width, $height, (int) imagecolorallocate($image, 236, 241, 235));
        $textColor = (int) imagecolorallocate($image, 35, 48, 36);
        $textWidth = imagefontwidth($font) * strlen($stamp);
        $x = max(8, (int) (($width - $textWidth) / 2));
        $y = $height - $stripHeight + max(3, (int) (($stripHeight - imagefontheight($font)) / 2));
        imagestring($image, $font, $x, $y, $stamp, $textColor);

        ob_start();
        $encoded = $mimeType === 'image/png' ? @imagepng($image) : @imagejpeg($image, null, 90);
        imagedestroy($image);

        if (!$encoded) {
            ob_end_clean();
            return false;
        }

        $data = (string) ob_get_clean();

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . strlen($data));
        header('Content-Disposition: ' . $disposition . '; filename="' . addcslashes($filename, '"\\') . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        echo $data;

        return true;
    }

    private function canAccess(array $file) {
        $role = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));
        if (($file['case_source'] ?? '') === 'Legacy') {
            return in_array($role, ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'coordinator'], true);
        }
        if ($role === 'student') {
            if (!empty($file['update_id'])) {
                return false;
            }

            return (int) $file['submitted_by_account_id'] === (int) $this->user['account_id'];
        }
        if ($role === 'coordinator') return (int) $file['assigned_coordinator_account_id'] === (int) $this->user['account_id'];
        return in_array($role, ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'head-of-sdru', 'sdru-head'], true);
    }
}
