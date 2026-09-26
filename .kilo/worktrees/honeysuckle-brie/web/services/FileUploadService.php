<?php

class FileUploadService {
    private const ALLOWED_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'docx'];
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    private const MAX_BYTES = 5 * 1024 * 1024;

    private static function normalizeUploadedFiles(array $files): array {
        if (empty($files['name'])) {
            return [];
        }

        $names = $files['name'];
        if (!is_array($names)) {
            return [[
                'name' => (string) $files['name'],
                'type' => (string) ($files['type'] ?? ''),
                'tmp_name' => (string) ($files['tmp_name'] ?? ''),
                'error' => (int) ($files['error'] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($files['size'] ?? 0),
            ]];
        }

        if (!isset($files['name'][0]) || !is_array($files['name'][0])) {
            $count = count($names);
            $normalized = [];
            for ($i = 0; $i < $count; $i++) {
                $normalized[] = [
                    'name' => (string) ($names[$i] ?? ''),
                    'type' => (string) (($files['type'][$i] ?? '') ?? ''),
                    'tmp_name' => (string) (($files['tmp_name'][$i] ?? '') ?? ''),
                    'error' => (int) (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) ?? UPLOAD_ERR_NO_FILE),
                    'size' => (int) (($files['size'][$i] ?? 0) ?? 0),
                ];
            }
            return $normalized;
        }

        $normalized = [];
        foreach ($names as $index => $name) {
            if (!is_array($name)) {
                continue;
            }

            $normalized[] = [
                'name' => (string) ($name['name'] ?? ''),
                'type' => (string) (($files['type'][$index]['name'] ?? $files['type'][$index] ?? '') ?? ''),
                'tmp_name' => (string) (($files['tmp_name'][$index]['name'] ?? $files['tmp_name'][$index] ?? '') ?? ''),
                'error' => (int) (($files['error'][$index]['name'] ?? $files['error'][$index] ?? UPLOAD_ERR_NO_FILE) ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) (($files['size'][$index]['name'] ?? $files['size'][$index] ?? 0) ?? 0),
            ];
        }

        return $normalized;
    }

    public static function validateFiles(array $files) {
        $errors = [];

        foreach (self::normalizeUploadedFiles($files) as $file) {
            $name = (string) ($file['name'] ?? '');
            if ($name === '') {
                continue;
            }

            if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = $name . ' could not be uploaded.';
                continue;
            }

            $tmpName = (string) ($file['tmp_name'] ?? '');
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                $errors[] = $name . ' is not a valid uploaded file.';
                continue;
            }

            if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
                $errors[] = $name . ' exceeds the 5MB file limit.';
                continue;
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = $name . ' has an invalid file type.';
                continue;
            }

            $mimeType = mime_content_type($tmpName);

            if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
                $errors[] = $name . ' has an invalid file content type.';
            }
        }

        return $errors;
    }

    public static function saveToEvidence(array $files) {
        require_once __DIR__ . '/GoogleDriveService.php';
        $savedFiles = [];
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';
        $drive = new GoogleDriveService();
        $driveEnabled = $drive->isAvailable();

        self::ensureStorageColumns();

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Unable to create the evidence directory.');
        }

        try {
            foreach (self::normalizeUploadedFiles($files) as $file) {
                $name = (string) ($file['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    throw new Exception('The uploaded file could not be read.');
                }
                if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
                    throw new Exception('The uploaded file exceeds the 5MB file limit.');
                }

                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    throw new Exception('The uploaded file has an invalid file type.');
                }

                $tmpName = (string) ($file['tmp_name'] ?? '');
                if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                    throw new Exception('Invalid uploaded file.');
                }
                if (!in_array(mime_content_type($tmpName), self::ALLOWED_MIME_TYPES, true)) {
                    throw new Exception('The uploaded file has an invalid file content type.');
                }

                $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
                $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

                if ($driveEnabled) {
                    $driveFile = $drive->upload($tmpName, basename($name), mime_content_type($tmpName));
                    if ($driveFile) {
                        $savedFiles[] = [
                            'original_filename' => basename($name),
                            'stored_filename' => '',
                            'file_path' => '',
                            'mime_type' => mime_content_type($tmpName),
                            'file_size' => (int) $file['size'],
                            'storage_provider' => 'google_drive',
                            'drive_file_id' => $driveFile['drive_file_id'],
                            'drive_web_view_link' => $driveFile['drive_web_view_link'],
                        ];
                        continue;
                    }
                }

                if (!move_uploaded_file($tmpName, $destination)) {
                    throw new Exception('Unable to save uploaded file.');
                }

                $savedFiles[] = [
                    'original_filename' => basename($name),
                    'stored_filename' => $storedFilename,
                    'file_path' => 'storage/evidence/' . $storedFilename,
                    'mime_type' => mime_content_type($destination),
                    'file_size' => (int) filesize($destination),
                    'storage_provider' => 'local',
                    'drive_file_id' => null,
                    'drive_web_view_link' => null,
                ];
            }
        } catch (Throwable $exception) {
            self::removeSavedFiles($savedFiles);
            throw $exception;
        }

        return $savedFiles;
    }

    private static function ensureStorageColumns() {
        require_once __DIR__ . '/../config/Database.php';
        $db = (new Database())->getConnection();
        if (!$db) return;
        $columns = [
            'storage_provider' => "ALTER TABLE complaint_evidence ADD COLUMN storage_provider VARCHAR(30) NOT NULL DEFAULT 'local'",
            'drive_file_id' => "ALTER TABLE complaint_evidence ADD COLUMN drive_file_id VARCHAR(255) NULL",
            'drive_web_view_link' => "ALTER TABLE complaint_evidence ADD COLUMN drive_web_view_link TEXT NULL",
        ];
        foreach ($columns as $column => $sql) {
            $check = $db->prepare("SHOW COLUMNS FROM complaint_evidence LIKE ?");
            if (!$check) continue;
            $check->bind_param('s', $column);
            $check->execute();
            $exists = $check->get_result()->num_rows > 0;
            $check->close();
            if (!$exists) $db->query($sql);
        }
    }

    private static function evidenceFilePath($relativePath) {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '') return null;

        $root = dirname(__DIR__, 2);
        $base = realpath($root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence');
        $path = realpath($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
        if ($base === false || $path === false) return null;

        $basePrefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos(strtolower($path), strtolower($basePrefix)) !== 0) return null;

        return $path;
    }

    public static function validateStoredFiles(array $files) {
        $validated = [];
        foreach ($files as $file) {
            if (!is_array($file)) throw new Exception('Invalid stored upload metadata.');

            $metadataPath = trim((string) ($file['file_path'] ?? ''));
            $path = self::evidenceFilePath($metadataPath);
            if ($path === null || !is_file($path)) throw new Exception('The approved upload is no longer available.');

            $storedFilename = basename($path);
            if ($storedFilename === '' || $storedFilename !== basename((string) ($file['stored_filename'] ?? $storedFilename))) {
                throw new Exception('The approved upload path does not match the stored file metadata.');
            }

            $expectedRelativePath = 'storage/evidence/' . $storedFilename;
            $normalizedPath = str_replace('\\', '/', $metadataPath);
            $normalizedExpected = str_replace('\\', '/', $expectedRelativePath);
            if ($normalizedPath !== $normalizedExpected) {
                throw new Exception('The approved upload path is invalid.');
            }

            $originalFilename = basename((string) ($file['original_filename'] ?? $storedFilename));
            if ($originalFilename === '' || $originalFilename !== basename($originalFilename)) {
                throw new Exception('The approved upload has an invalid original filename.');
            }
            $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) throw new Exception('The approved upload has an invalid file type.');

            $mimeType = mime_content_type($path);
            if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) throw new Exception('The approved upload has an invalid file content type.');

            if (array_key_exists('mime_type', $file) && trim((string) $file['mime_type']) !== '' && strcasecmp((string) $file['mime_type'], $mimeType) !== 0) {
                throw new Exception('The approved upload metadata does not match the stored file.');
            }

            $actualSize = (int) filesize($path);
            if (array_key_exists('file_size', $file) && (int) $file['file_size'] !== $actualSize) {
                throw new Exception('The approved upload file size does not match the stored file.');
            }

            $validated[] = [
                'original_filename' => $originalFilename,
                'stored_filename' => $storedFilename,
                'file_path' => $expectedRelativePath,
                'mime_type' => $mimeType,
                'file_size' => $actualSize,
            ];
        }
        return $validated;
    }

    public static function removeSavedFiles(array $files) {
        foreach ($files as $file) {
            if (is_array($file) && array_key_exists('file_path', $file)) {
                $path = self::evidenceFilePath($file['file_path']);
                if ($path !== null && is_file($path)) @unlink($path);
                continue;
            }
            if (is_array($file)) self::removeSavedFiles($file);
        }
    }
}