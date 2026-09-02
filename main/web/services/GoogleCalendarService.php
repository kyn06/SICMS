<?php

require_once __DIR__ . '/../config/Database.php';

class GoogleCalendarService {
    private $db;
    private $config;

    private function __construct($db = null) {
        $this->db = $db ?: (new Database())->getConnection();
        $this->config = require __DIR__ . '/../config/google_config.php';
    }

    public static function instance($db = null) {
        return new self($db);
    }

    // -------------------------------------------------------------
    // Settings storage (system_settings table)
    // -------------------------------------------------------------
    private function settingGet($key) {
        $sql = "SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result && $result->num_rows > 0 ? $result->fetch_assoc()['setting_value'] : null;
    }

    private function settingSet($key, $value) {
        $sql = "INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = VALUES(updated_at)";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $updatedAt = date('Y-m-d H:i:s');
        $stmt->bind_param('sss', $key, $value, $updatedAt);

        return $stmt->execute();
    }

    private function settingDelete($key) {
        $sql = "DELETE FROM system_settings WHERE setting_key = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $key);

        return $stmt->execute();
    }

    // -------------------------------------------------------------
    // Connection state
    // -------------------------------------------------------------
    public function isConfigured() {
        $clientId = trim((string) ($this->config['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->config['client_secret'] ?? ''));

        return $clientId !== '' && $clientSecret !== '' && !str_starts_with($clientId, 'YOUR_');
    }

    public function isConnected() {
        return (bool) $this->settingGet('google_calendar_refresh_token');
    }

    public function connectedEmail() {
        return $this->settingGet('google_calendar_email');
    }

    public function disconnect() {
        $this->settingDelete('google_calendar_refresh_token');
        $this->settingDelete('google_calendar_email');
    }

    // -------------------------------------------------------------
    // OAuth flow
    // -------------------------------------------------------------
    public function redirectUri() {
        $configured = trim((string) ($this->config['calendar_redirect_uri'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $base = preg_replace('#/web/views/.*$#', '', $script);

        return $scheme . '://' . $host . rtrim($base, '/') . '/web/views/auth/google_connect_calendar.php';
    }

    public function authUrl() {
        $hostedDomain = trim((string) ($this->config['hosted_domain'] ?? ''));
        $state = bin2hex(random_bytes(16));

        $_SESSION['google_calendar_oauth_state'] = $state;

        $params = [
            'client_id'             => trim((string) $this->config['client_id']),
            'redirect_uri'          => $this->redirectUri(),
            'response_type'         => 'code',
            'scope'                 => $this->scopes(),
            'state'                 => $state,
            'access_type'           => 'offline',
            'prompt'                => 'consent',
            'include_granted_scopes' => 'true',
        ];

        if ($hostedDomain !== '') {
            $params['hd'] = $hostedDomain;
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function scopes() {
        return trim((string) ($this->config['calendar_scopes'] ?? 'https://www.googleapis.com/auth/calendar.events'));
    }

    public function consumeState() {
        $sent = (string) ($_GET['state'] ?? '');
        return $sent !== '' && !empty($_SESSION['google_calendar_oauth_state']) && hash_equals($_SESSION['google_calendar_oauth_state'], $sent);
    }

    public function handleCallback($code, $verifySsl = true) {
        $response = $this->httpJson('https://oauth2.googleapis.com/token', 'POST', [
            'code'          => $code,
            'client_id'     => trim((string) $this->config['client_id']),
            'client_secret' => trim((string) $this->config['client_secret']),
            'redirect_uri'  => $this->redirectUri(),
            'grant_type'    => 'authorization_code',
        ], null, $verifySsl, true);

        $refreshToken = (string) ($response['refresh_token'] ?? '');

        if ($refreshToken === '') {
            return ['success' => false, 'message' => $response['error_description'] ?? $response['curl_error'] ?? 'Google did not return a refresh token.'];
        }

        $email = $this->profileEmail($response['access_token'] ?? '', $verifySsl);
        $email = $email ?: '';

        $hostedDomain = trim((string) ($this->config['hosted_domain'] ?? ''));
        if ($hostedDomain !== '' && $email !== '' && strtolower((string) $email) !== strtolower($hostedDomain) && !str_ends_with(strtolower($email), '@' . strtolower($hostedDomain))) {
            return ['success' => false, 'message' => 'Only ' . $hostedDomain . ' accounts may connect the calendar.'];
        }

        $this->settingSet('google_calendar_refresh_token', $refreshToken);
        $this->settingSet('google_calendar_email', $email);

        return ['success' => true, 'email' => $email];
    }

    private function profileEmail($accessToken, $verifySsl = true) {
        if ($accessToken === '') {
            return null;
        }

        $response = $this->httpJson('https://www.googleapis.com/oauth2/v2/userinfo', 'GET', [], $accessToken, $verifySsl);

        return $response['email'] ?? null;
    }

    // -------------------------------------------------------------
    // Access tokens
    // -------------------------------------------------------------
    private $accessTokenCache = null;

    public function accessToken() {
        if ($this->accessTokenCache !== null) {
            return $this->accessTokenCache;
        }

        $refreshToken = $this->settingGet('google_calendar_refresh_token');

        if (!$refreshToken) {
            return null;
        }

        $response = $this->httpJson('https://oauth2.googleapis.com/token', 'POST', [
            'grant_type'    => 'refresh_token',
            'client_id'     => trim((string) $this->config['client_id']),
            'client_secret' => trim((string) $this->config['client_secret']),
            'refresh_token' => $refreshToken,
        ], null, null, true);

        if (empty($response['access_token'])) {
            return null;
        }

        $this->accessTokenCache = $response['access_token'];

        return $this->accessTokenCache;
    }

    // -------------------------------------------------------------
    // Calendar API
    // -------------------------------------------------------------
    public function createEvent(array $hearing, $withConference = false) {
        $hasManualMeet = trim((string) ($hearing['google_meet_link'] ?? '')) !== '';

        if ($withConference && $hasManualMeet) {
            $withConference = false;
        }

        $event = $this->buildEventPayload($hearing, $withConference);
        $token = $this->accessToken();

        if (!$token) {
            return ['success' => false, 'message' => 'Google Calendar is not connected.'];
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events'
            . ($withConference ? '?conferenceDataVersion=1' : '');

        $response = $this->httpJson($url, 'POST', $event, $token);

        if (empty($response['id'])) {
            return ['success' => false, 'message' => $this->apiError($response)];
        }

        return [
            'success'      => true,
            'event_id'     => $response['id'],
            'html_link'    => $response['htmlLink'] ?? null,
            'hangout_link' => $response['hangoutLink'] ?? null,
        ];
    }

    public function updateEvent($eventId, array $hearing) {
        $event = $this->buildEventPayload($hearing, false);
        $token = $this->accessToken();

        if (!$token || !preg_match('/^[A-Za-z0-9_\-]{3,}$/', (string) $eventId)) {
            return ['success' => false, 'message' => 'Google Calendar is not connected.'];
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode($eventId);

        $response = $this->httpJson($url, 'PATCH', $event, $token);

        if (empty($response['id'])) {
            return ['success' => false, 'message' => $this->apiError($response)];
        }

        return ['success' => true, 'html_link' => $response['htmlLink'] ?? null, 'hangout_link' => $response['hangoutLink'] ?? null];
    }

    public function deleteEvent($eventId) {
        $token = $this->accessToken();

        if (!$token || !preg_match('/^[A-Za-z0-9_\-]{3,}$/', (string) $eventId)) {
            return ['success' => false, 'message' => 'Google Calendar is not connected.'];
        }

        $url = 'https://www.googleapis.com/calendar/v3/calendars/primary/events/' . rawurlencode($eventId);

        $response = $this->httpJson($url, 'DELETE', [], $token);

        if (isset($response['error'])) {
            return ['success' => false, 'message' => $this->apiError($response)];
        }

        return ['success' => true];
    }

    public function upcomingEvents($maxResults = 10) {
        $token = $this->accessToken();

        if (!$token) {
            return ['success' => false, 'events' => []];
        }

        $params = http_build_query([
            'maxResults'   => max(1, min(50, (int) $maxResults)),
            'orderBy'      => 'startTime',
            'singleEvents' => 'true',
            'timeMin'      => gmdate('c', time() - 60),
        ]);

        $response = $this->httpJson(
            'https://www.googleapis.com/calendar/v3/calendars/primary/events?' . $params,
            'GET',
            [],
            $token
        );

        if (!empty($response['error'])) {
            return ['success' => false, 'events' => []];
        }

        $events = [];
        foreach (($response['items'] ?? []) as $item) {
            $start = $item['start']['dateTime'] ?? ($item['start']['date'] ?? null);

            $events[] = [
                'id'       => $item['id'] ?? '',
                'summary'  => $item['summary'] ?? $item['description'] ?? 'Untitled',
                'start'    => $start,
                'link'     => $item['htmlLink'] ?? '#',
                'hangout'  => $item['hangoutLink'] ?? null,
            ];
        }

        return ['success' => true, 'events' => $events];
    }

    // -------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------
    private function buildEventPayload(array $hearing, $includeConference = false) {
        $summary = 'Hearing - ' . ($hearing['case_number'] ?? ('Case #' . (int) ($hearing['complaint_id'] ?? 0)));
        $venue = trim((string) ($hearing['venue'] ?? ''));
        $remarks = trim((string) ($hearing['remarks'] ?? ''));
        $meetLink = trim((string) ($hearing['google_meet_link'] ?? ''));

        $lines = [
            'Case: ' . ($hearing['case_number'] ?? ''),
            'Complainant: ' . ($hearing['complainant_name'] ?? ''),
            'Venue: ' . ($venue !== '' ? $venue : 'TBD'),
        ];

        if ($meetLink !== '') {
            $lines[] = 'Google Meet: ' . $meetLink;
        }

        if ($remarks !== '') {
            $lines[] = 'Remarks: ' . $remarks;
        }

        $datetime = new DateTime($hearing['hearing_datetime'], new DateTimeZone('Asia/Manila'));
        $durationMinutes = max(1, (int) ($this->config['event_duration_minutes'] ?? 60));
        $end = clone $datetime;
        $end->modify('+' . $durationMinutes . ' minutes');

        $payload = [
            'summary'     => $summary,
            'description' => implode("\n", $lines),
            'start'       => ['dateTime' => $datetime->format('c'), 'timeZone' => 'Asia/Manila'],
            'end'         => ['dateTime' => $end->format('c'), 'timeZone' => 'Asia/Manila'],
        ];

        if ($venue !== '') {
            $payload['location'] = $venue;
        }

        if ($includeConference && $meetLink === '') {
            $payload['conferenceData'] = [
                'createRequest' => [
                    'requestId' => 'sicms-hearing-' . (int) ($hearing['hearing_id'] ?? 0) . '-' . bin2hex(random_bytes(6)),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ];
        }

        return $payload;
    }

    private function apiError(array $response) {
        $message = $response['error']['message'] ?? ($response['error_description'] ?? $response['curl_error'] ?? null);

        if ($message === null) {
            return 'Unknown error communicating with Google Calendar.';
        }

        return 'Google Calendar error: ' . $message;
    }

    private function httpJson($url, $method = 'GET', array $payload = [], $accessToken = null, $verifySsl = null, $formEncoded = false) {
        if (!function_exists('curl_init')) {
            return ['curl_error' => 'cURL is not available on this server.'];
        }

        if ($verifySsl === null) {
            $verifySsl = (bool) ($this->config['verify_ssl'] ?? true);
        }

        $headers = [$formEncoded ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json'];

        if (is_string($accessToken) && $accessToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $accessToken;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER    => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT       => 25,
            CURLOPT_SSL_VERIFYPEER => (bool) $verifySsl,
            CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
        ]);

        if (!empty($payload)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formEncoded ? http_build_query($payload) : json_encode($payload));
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