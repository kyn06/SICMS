<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

header('Content-Type: application/json');

Security::startSession();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    exit(json_encode(['ok' => false, 'error' => 'Authentication required.']));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit(json_encode(['ok' => false, 'error' => 'Method not allowed.']));
}

$data = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!is_string($token) || $token === '') {
    $token = $data['csrf_token'] ?? '';
}

if (!Security::validateCsrfToken(is_string($token) ? $token : '')) {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Invalid CSRF token.']));
}

$events = [
    'printscreen' => ['action' => 'Screenshot Attempt', 'label' => 'PrintScreen'],
    'print_attempt' => ['action' => 'Print Attempt', 'label' => 'Print'],
];

$eventKey = (string) ($data['event'] ?? '');
if (!isset($events[$eventKey])) {
    http_response_code(422);
    exit(json_encode(['ok' => false, 'error' => 'Unknown event type.']));
}

$throttleKey = 'security_event_' . $eventKey;
$now = time();
$last = (int) ($_SESSION[$throttleKey] ?? 0);

if ($now - $last < 30) {
    http_response_code(204);
    exit;
}

$_SESSION[$throttleKey] = $now;

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);
AuditLog::setConnection($db);

$user = User::findByEmail($_SESSION['email']);
if (!$user || $user['status'] !== 'active') {
    http_response_code(403);
    exit(json_encode(['ok' => false, 'error' => 'Access denied.']));
}

$normalized = strtolower(str_replace('\\', '/', preg_replace('/[^A-Za-z0-9\/._-]/', '', (string) ($data['page'] ?? ''))));

$pageLabels = [
    'cases/show.php' => 'Case Details',
    'cases/index.php' => 'Case Management',
    'hearings/' => 'Hearings',
    'accounts/' => 'Users',
    'messages/' => 'Chat & Messaging',
    'audit_logs/' => 'Audit Logs',
    'reports/' => 'Reports',
    'settings/' => 'Settings',
    'notifications/' => 'Notification',
    'complaints/create.php' => 'Submit Complaint',
    'complaints/case_details.php' => 'Case Details',
    'complaints/my_cases.php' => 'Track My Cases',
    'complaints/revise.php' => 'Revise Complaint',
];

$pageLabel = '';

foreach ($pageLabels as $fragment => $label) {
    if (strpos($normalized, $fragment) !== false) {
        $pageLabel = $label;
        break;
    }
}

if ($pageLabel === ''
    && (strpos($normalized, 'views/dashboard/') !== false || substr($normalized, -10) === '/index.php')) {
    $pageLabel = 'Dashboard';
}

AuditLog::record(
    $user,
    $events[$eventKey]['action'],
    $events[$eventKey]['label'] . ' key detected while viewing ' . ($pageLabel !== '' ? $pageLabel : 'the system') . '.'
);

http_response_code(200);
exit(json_encode(['ok' => true]));
