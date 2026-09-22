<?php

/**
 * UserGoogleMailer
 * ----------------
 * Sends correspondence FROM a single user's OWN connected Gmail using the
 * Gmail API (users.messages.send). Tokens are stored per-user in the
 * `user_google_connections` table managed by GoogleCalendarService.
 *
 * This class intentionally does NOT touch GoogleCalendarService — it is a
 * small, isolated, self-contained sender used by CaseController when the
 * acting head/coordinator is connected to their own Google account.
 *
 * IF the actor is not connected, the caller falls back to the shared SMTP
 * Mailer (no error here, we simply return an explicit result).
 */

require_once __DIR__ . '/../config/Database.php';

class UserGoogleMailer {
    private $db;
    private $config;

    private function __construct($db = null) {
        $this->db = $db ?: (new Database())->getConnection();
        $this->config = require __DIR__ . '/../config/google_config.php';
    }

    public static function instance($db = null) {
        return new self($db);
    }

    /**
     * Send an email FROM the actor's connected Gmail address via the Gmail
     * API. Returns:
     *   ['sent' => false, 'reason' => 'not_connected']  -> caller uses SMTP
     *   ['sent' => false, 'reason' => 'invalid']        -> bad args
     *   ['sent' => false, 'reason' => 'token']          -> token refresh failed
     *   ['sent' => false, 'reason' => 'api']            -> Google rejected
     *   ['sent' => true,  'id' => '...']
     */
    public function sendFromUser($accountId, $to, $subject, $htmlBody, $fromName = 'SICMS') {
        $accountId = (int) $accountId;
        $to        = trim((string) $to);
        $subject   = trim((string) $subject);
        $htmlBody  = (string) $htmlBody;
        $fromName  = trim((string) $fromName) ?: 'SICMS';

        if ($accountId <= 0 || $to === '' || $subject === '') {
            return ['sent' => false, 'reason' => 'invalid'];
        }

        $row = $this->userConnectionRow($accountId);

        if ($row === null || trim((string) ($row['refresh_token'] ?? '')) === '') {
            return ['sent' => false, 'reason' => 'not_connected'];
        }

        $accessToken = $this->userAccessToken($accountId);

        if ($accessToken === null || $accessToken === '') {
            return ['sent' => false, 'reason' => 'token'];
        }

        $fromEmail = trim((string) ($row['email'] ?? '')) !== '' ? (string) $row['email'] : $to;
        $raw       = $this->rawMessage($to, $subject, $htmlBody, $fromName, $fromEmailbacks);
        $encoded   = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $response = $this->httpJson(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages',
            'POST',
            ['raw' => $encoded],
            $accessToken
        );

        if (empty($response['id'])) {
            return ['sent' => false, 'reason' => 'api'];
        }

        return ['sent' => true, 'id' => (string) $response['id']];
    }

    // -------------------------------------------------------------
    // Token helpers (reuse same per-user table as GoogleCalendarService)
    // -------------------------------------------------------------
    private function userConnectionRow($accountId) {
        $sql = "SELECT refresh_token, email FROM user_google_connections WHERE account_id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $accountId);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result && $result->num_rows > 0 ? $result->fetch_assoc() : null;
    }

    private function userAccessToken($accountId) {
        $row = $this->userConnectionRow($accountId);

        if ($row === null || trim((string) ($row['refresh_token'] ?? '')) === '') {
            return null;
        }

        $response = $this->httpJson('https://oauth2.googleapis.com/token', 'POST', [
            'grant_type'    => 'refresh_token',
            'client_id'     => trim((string) $this->config['client_id']),
            'client_secret' => trim((string) $this->config['client_secret']),
            'refresh_token' => (string) $row['refresh_token'],
        ], null);

        return empty($response['access_token']) ? null : (string) $response['access_token'];
    }

    private function rawMessage($to, $subject, $htmlBody, $fromName, $fromEmail) {
        $crlf = "\r\n";

        $headers = [
            'From: ' . $this->encodeName($fromName) . ' <' . $fromEmail . '>',
            'To: <' . $to . '>',
            'Subject: ' . $this->encodeName($subject),
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'Date: ' . gmdate('D, d M Y H:i:s') . ' +0000',
        ];

        return implode($crlf, $headers) . $crlf . $crlf . $htmlBody;
    }

    private function encodeName($value) {
        return '=?UTF-8?B?' . base64_encode((string) $value) . '?=';
    }

    private function httpJson($url, $method = 'GET', array $payload = [], $accessToken = null, $verifySsl = null) {
        if (!function_exists('curl_init')) {
            return ['curl_error' => 'cURL is not available on this server.'];
        }

        $verifySsl = $verifySsl === null ? (bool) ($this->config['verify_ssl'] ?? true) : (bool) $verifySsl;

        $headers = ['Content-Type: application/json'];

        if (is_string($accessToken) && $accessToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST   => $method,
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => 25,
            CURLOPT_SSL_VERIFYPEER  => (bool) $verifySsl,
            CURLOPT_SSL_VERIFYHOST  => $verifySsl ? 2 : 0,
        ]);

        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            return ['curl_error' => $error];
        }

        return json_decode($body, true) ?? ['raw' => $body];
    }
}
