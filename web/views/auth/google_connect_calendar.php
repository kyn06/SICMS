<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../services/GoogleCalendarService.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);

$user = User::findByEmail($_SESSION['email']);

if (!$user || $user['status'] !== 'active') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

function calendar_redirect_to_settings() {
    header('Location: ../settings/index.php');
    exit;
}

$staffRoles = ['admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head'];
$roleKey = strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));

if (!in_array($roleKey, $staffRoles, true)) {
    $_SESSION['cal_error'] = 'Only SDRU staff can connect the office Google Calendar.';
    calendar_redirect_to_settings();
}

$service = GoogleCalendarService::instance($db);

if (($_GET['diag'] ?? '') === '1') {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Add this EXACT URL under 'Authorized redirect URIs' in Google Cloud Console:\n\n";
    echo $service->redirectUri() . "\n\n";
    echo "Calendar API scope:  " . $service->scopes() . "\n";
    echo "Client ID set:       " . ($service->isConfigured() ? 'yes' : 'NO - edit web/config/google_config.php') . "\n";
    exit;
}

if (!$service->isConfigured()) {
    $_SESSION['cal_error'] = 'Google Calendar is not configured. Edit web/config/google_config.php with your OAuth credentials.';
    calendar_redirect_to_settings();
}

if (!empty($_GET['error'])) {
    $_SESSION['cal_error'] = 'Google Calendar connection was cancelled. You can try again from Settings.';
    calendar_redirect_to_settings();
}

if (!isset($_GET['code'])) {
    unset($_SESSION['google_calendar_oauth_state']);
    header('Location: ' . $service->authUrl());
    exit;
}

if (!$service->consumeState()) {
    unset($_SESSION['google_calendar_oauth_state']);
    $_SESSION['cal_error'] = 'Invalid Google Calendar connection state. Please try again.';
    calendar_redirect_to_settings();
}

unset($_SESSION['google_calendar_oauth_state']);

$result = $service->handleCallback((string) $_GET['code']);

if ($result['success']) {
    $_SESSION['cal_message'] = 'Google Calendar connected' . (!empty($result['email']) ? ' (' . $result['email'] . ')' : '') . '. New hearings will be added to the calendar automatically.';
} else {
    $_SESSION['cal_error'] = $result['message'] ?? 'Could not connect Google Calendar. Please try again.';
}

calendar_redirect_to_settings();