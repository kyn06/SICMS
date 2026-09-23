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
        $savedFiles = [];
        $uploadDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'evidence';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach (self::normalizeUploadedFiles($files) as $file) {
            $name = (string) ($file['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $tmpName = (string) ($file['tmp_name'] ?? '');
            if ($tmpName === '' || !is_uploaded_file($tmpName)) {
                throw new Exception('Invalid uploaded file.');
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

            if (!move_uploaded_file($tmpName, $destination)) {
                throw new Exception('Unable to save uploaded file.');
            }

            $savedFiles[] = [
                'original_filename' => basename($name),
                'stored_filename' => $storedFilename,
                'file_path' => 'storage/evidence/' . $storedFilename,
                'mime_type' => mime_content_type($destination),
                'file_size' => (int) ($file['size'] ?? 0),
            ];
        }

        return $savedFiles;
    }
}