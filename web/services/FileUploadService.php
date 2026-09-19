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

    public static function validateFiles(array $files) {
        $errors = [];

        foreach (($files['name'] ?? []) as $index => $name) {
            if ($name === '') {
                continue;
            }

            if (($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = $name . ' could not be uploaded.';
                continue;
            }

            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                $errors[] = $name . ' is not a valid uploaded file.';
                continue;
            }

            if (($files['size'][$index] ?? 0) > self::MAX_BYTES) {
                $errors[] = $name . ' exceeds the 5MB file limit.';
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = $name . ' has an invalid file type.';
                continue;
            }

            $mimeType = mime_content_type($files['tmp_name'][$index]);

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

        foreach ($files['name'] as $index => $name) {
            if ($name === '') {
                continue;
            }

            if (empty($files['tmp_name'][$index]) || !is_uploaded_file($files['tmp_name'][$index])) {
                throw new Exception('Invalid uploaded file.');
            }

            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $storedFilename = date('YmdHis') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
            $destination = $uploadDir . DIRECTORY_SEPARATOR . $storedFilename;

            if (!move_uploaded_file($files['tmp_name'][$index], $destination)) {
                throw new Exception('Unable to save uploaded file.');
            }

            $savedFiles[] = [
                'original_filename' => basename($name),
                'stored_filename' => $storedFilename,
                'file_path' => 'storage/evidence/' . $storedFilename,
                'mime_type' => mime_content_type($destination),
                'file_size' => (int) $files['size'][$index],
            ];
        }

        return $savedFiles;
    }
}