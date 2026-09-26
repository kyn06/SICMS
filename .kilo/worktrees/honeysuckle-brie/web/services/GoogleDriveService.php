<?php

require_once __DIR__ . '/../config/Database.php';

class GoogleDriveService {
    private $db;
    private $config;
    private $accessToken;

    public function __construct($db = null) {
        $this->db = $db ?: (new Database())->getConnection();
        $this->config = require __DIR__ . '/../config/google_config.php';
    }

    public function isAvailable() {
        return $this->accessToken() !== null;
    }

    public function upload($filePath, $filename, $mimeType) {
        $token = $this->accessToken();
        if (!$token || !is_file($filePath)) return null;

        $metadata = json_encode([
            'name' => basename($filename),
            'description' => 'DARIS attachment',
        ], JSON_THROW_ON_ERROR);
        $boundary = 'daris_' . bin2hex(random_bytes(12));
        $body = "--{$boundary}\r\n"
            . "Content-Type: application/json; charset=UTF-8\r\n\r\n"
            . $metadata . "\r\n"
            . "--{$boundary}\r\n"
            . "Content-Type: {$mimeType}\r\n\r\n"
            . file_get_contents($filePath) . "\r\n"
            . "--{$boundary}--\r\n";

        $ch = curl_init('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id,webViewLink');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: multipart/related; boundary=' . $boundary,
            ],
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => (bool) ($this->config['verify_ssl'] ?? true),
            CURLOPT_SSL_VERIFYHOST => ($this->config['verify_ssl'] ?? true) ? 2 : 0,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode((string) $response, true);

        return !empty($data['id']) ? [
            'drive_file_id' => $data['id'],
            'drive_web_view_link' => $data['webViewLink'] ?? '',
        ] : null;
    }

    public function download($fileId) {
        $token = $this->accessToken();
        if (!$token || trim((string) $fileId) === '') return null;

        $ch = curl_init('https://www.googleapis.com/drive/v3/files/' . rawurlencode($fileId) . '?alt=media');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => (bool) ($this->config['verify_ssl'] ?? true),
            CURLOPT_SSL_VERIFYHOST => ($this->config['verify_ssl'] ?? true) ? 2 : 0,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status >= 200 && $status < 300 ? $body : null;
    }

    private function accessToken() {
        if ($this->accessToken !== null) return $this->accessToken;
        $refreshToken = $this->setting('google_calendar_refresh_token');
        if (!$refreshToken || !function_exists('curl_init')) return null;

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'refresh_token',
                'client_id' => $this->config['client_id'] ?? '',
                'client_secret' => $this->config['client_secret'] ?? '',
                'refresh_token' => $refreshToken,
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => (bool) ($this->config['verify_ssl'] ?? true),
            CURLOPT_SSL_VERIFYHOST => ($this->config['verify_ssl'] ?? true) ? 2 : 0,
        ]);
        $response = json_decode((string) curl_exec($ch), true);
        curl_close($ch);
        $this->accessToken = $response['access_token'] ?? null;
        return $this->accessToken;
    }

    private function setting($key) {
        $stmt = $this->db->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
        if (!$stmt) return null;
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['setting_value'] ?? null;
    }
}
