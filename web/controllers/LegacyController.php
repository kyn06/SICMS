<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/LegacyInCharge.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class LegacyController {
    private $database;
    private $db;
    private $user;

    private const PHOTO_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_PHOTO_BYTES = 5242880;

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->handleAction();
        }

        return [
            'user' => $this->user,
            'entries' => LegacyInCharge::orderedAll(),
            'message' => $_SESSION['legacy_message'] ?? null,
            'errors' => $_SESSION['legacy_errors'] ?? [],
            'old' => $_SESSION['legacy_old'] ?? [],
        ];
    }

    public function clearFlash() {
        unset($_SESSION['legacy_message'], $_SESSION['legacy_errors'], $_SESSION['legacy_old']);
    }

    private function authenticate() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        LegacyInCharge::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);

        if (!$this->user || $this->user['status'] !== 'active') {
            session_unset();
            session_destroy();
            header('Location: ../auth/login.php');
            exit;
        }
    }

    private function handleAction() {
        switch ($_POST['legacy_action'] ?? '') {
            case 'add':
                $this->store();
                break;
            case 'edit':
                $this->update();
                break;
            case 'delete':
                $this->destroy();
                break;
            default:
                $_SESSION['legacy_errors'] = ['Unknown legacy action.'];
                header('Location: index.php');
                exit;
        }
    }

    private function store() {
        $errors = $this->validate();

        if ($errors) {
            $_SESSION['legacy_errors'] = $errors;
            $_SESSION['legacy_old'] = $this->input();
            header('Location: index.php');
            exit;
        }

        $photoPath = $this->savePhoto();

        if ($photoPath === false) {
            $_SESSION['legacy_errors'] = ['Unable to save the uploaded photo.'];
            $_SESSION['legacy_old'] = $this->input();
            header('Location: index.php');
            exit;
        }

        $created = LegacyInCharge::create([
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'position' => trim((string) ($_POST['position'] ?? '')),
            'tenure' => trim((string) ($_POST['tenure'] ?? '')) ?: null,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'photo_path' => $photoPath,
        ]);

        if (!$created) {
            if ($photoPath) $this->deletePhotoFile($photoPath);
            $_SESSION['legacy_errors'] = ['Unable to save the legacy entry.'];
            $_SESSION['legacy_old'] = $this->input();
            header('Location: index.php');
            exit;
        }

        AuditLog::record($this->user, 'Legacy Entry Created', 'Added SDRU in-charge ' . trim((string) ($_POST['full_name'] ?? '')) . ' to the legacy page.');
        $_SESSION['legacy_message'] = 'Legacy entry added successfully.';
        header('Location: index.php');
        exit;
    }

    private function update() {
        $entry = LegacyInCharge::find((int) ($_POST['legacy_id'] ?? 0));

        if (!$entry) {
            $_SESSION['legacy_errors'] = ['Legacy entry not found.'];
            header('Location: index.php');
            exit;
        }

        $errors = $this->validate();

        if ($errors) {
            $_SESSION['legacy_errors'] = $errors;
            header('Location: index.php#legacy-entry-' . (int) $entry['legacy_id']);
            exit;
        }

        $photoPath = $this->savePhoto();

        if ($photoPath === false) {
            $_SESSION['legacy_errors'] = ['Unable to save the uploaded photo.'];
            header('Location: index.php#legacy-entry-' . (int) $entry['legacy_id']);
            exit;
        }

        $updated = LegacyInCharge::updateById((int) $entry['legacy_id'], [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'position' => trim((string) ($_POST['position'] ?? '')),
            'tenure' => trim((string) ($_POST['tenure'] ?? '')) ?: null,
            'description' => trim((string) ($_POST['description'] ?? '')) ?: null,
            'photo_path' => $photoPath ?: ($entry['photo_path'] ?? null),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$updated) {
            if ($photoPath) $this->deletePhotoFile($photoPath);
            $_SESSION['legacy_errors'] = ['Unable to update the legacy entry.'];
            header('Location: index.php#legacy-entry-' . (int) $entry['legacy_id']);
            exit;
        }

        if ($photoPath && !empty($entry['photo_path']) && $entry['photo_path'] !== $photoPath) {
            $this->deletePhotoFile((string) $entry['photo_path']);
        }

        AuditLog::record($this->user, 'Legacy Entry Updated', 'Updated SDRU in-charge ' . trim((string) ($_POST['full_name'] ?? '')) . ' on the legacy page.');
        $_SESSION['legacy_message'] = 'Legacy entry updated successfully.';
        header('Location: index.php');
        exit;
    }

    private function destroy() {
        $entry = LegacyInCharge::find((int) ($_POST['legacy_id'] ?? 0));

        if (!$entry) {
            $_SESSION['legacy_errors'] = ['Legacy entry not found.'];
            header('Location: index.php');
            exit;
        }

        if (!LegacyInCharge::deleteById((int) $entry['legacy_id'])) {
            $_SESSION['legacy_errors'] = ['Unable to delete the legacy entry.'];
            header('Location: index.php');
            exit;
        }

        if (!empty($entry['photo_path'])) {
            $this->deletePhotoFile((string) $entry['photo_path']);
        }

        AuditLog::record($this->user, 'Legacy Entry Deleted', 'Removed SDRU in-charge ' . $entry['full_name'] . ' from the legacy page.');
        $_SESSION['legacy_message'] = 'Legacy entry deleted successfully.';
        header('Location: index.php');
        exit;
    }

    private function input(): array {
        return [
            'full_name' => trim((string) ($_POST['full_name'] ?? '')),
            'position' => trim((string) ($_POST['position'] ?? '')),
            'tenure' => trim((string) ($_POST['tenure'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
        ];
    }

    private function validate(): array {
        $errors = LegacyInCharge::validate($_POST);

        if (!empty($_FILES['photo']['name'])) {
            $photoErrors = $this->validatePhoto();
            $errors = array_merge($errors, $photoErrors);
        }

        return $errors;
    }

    private function validatePhoto(): array {
        $file = $_FILES['photo'];

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['The photo upload failed. Please try again.'];
        }

        if ((int) $file['size'] > self::MAX_PHOTO_BYTES) {
            return ['The photo must not exceed 5 MB.'];
        }

        $mimeType = mime_content_type($file['tmp_name']);

        if (!in_array($mimeType, self::PHOTO_MIME_TYPES, true)) {
            return ['The photo must be a JPG, PNG, or WebP image.'];
        }

        return [];
    }

    private function savePhoto() {
        if (empty($_FILES['photo']['name'])) {
            return null;
        }

        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'legacy_photos';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = strtolower(pathinfo((string) $_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $mimeType = mime_content_type($_FILES['photo']['tmp_name']);
            $extension = match ($mimeType) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
        $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
            return false;
        }

        return 'storage/legacy_photos/' . $storedFilename;
    }

    private function deletePhotoFile(string $relativePath) {
        $storageRoot = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage');
        $absolutePath = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));

        if (!$storageRoot || !$absolutePath || !str_starts_with($absolutePath, $storageRoot . DIRECTORY_SEPARATOR) || !is_file($absolutePath)) {
            return;
        }

        @unlink($absolutePath);
    }
}
