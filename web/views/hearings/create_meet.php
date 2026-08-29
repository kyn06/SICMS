<?php
require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Case.php';
require_once __DIR__ . '/../../services/GoogleCalendarService.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

Security::requireCsrfToken();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

User::setConnection($db);
CaseRecord::setConnection($db);

$user = User::findByEmail($_SESSION['email']);

$staffRoles = ['super-admin', 'admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'head-of-sdru', 'sdru-head'];
$roleKey = strtolower(str_replace(['_', ' '], '-', $user['role'] ?? ''));

if (!$user || $user['status'] !== 'active' || !in_array($roleKey, $staffRoles, true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Staff access required.']);
    exit;
}

$complaintId = (int) ($_POST['complaint_id'] ?? 0);
$datetime = trim((string) ($_POST['hearing_datetime'] ?? ''));
$venue = trim((string) ($_POST['venue'] ?? ''));
$existingLink = trim((string) ($_POST['google_meet_link'] ?? ''));
$existingEventId = trim((string) ($_POST['google_event_id'] ?? ''));

if ($complaintId <= 0 || $datetime === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Select a case and hearing date/time first.']);
    exit;
}

$case = CaseRecord::findCase($complaintId);

if (!$case) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Case not found.']);
    exit;
}

$service = GoogleCalendarService::instance($db);

if (!$service->isConfigured() || !$service->isConnected()) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Connect Google Calendar in Settings first.']);
    exit;
}

$pseudoHearing = [
    'hearing_id'       => random_int(1, 999999),
    'complaint_id'     => $complaintId,
    'case_number'      => $case['case_number'],
    'complainant_name' => $case['complainant_name'],
    'hearing_datetime' => date('Y-m-d H:i:s', strtotime($datetime)),
    'venue'            => $venue,
    'google_meet_link' => $existingLink,
    'remarks'          => '',
];

if ($existingEventId !== '' && preg_match('/^[A-Za-z0-9_\-]{3,}$/', $existingEventId)) {
    $result = $service->updateEvent($existingEventId, $pseudoHearing);
} else {
    $result = $service->createEvent($pseudoHearing, true);
}

if (!$result['success']) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $result['message']]);
    exit;
}

$meetLink = $result['hangout_link'] ?? $existingLink;

echo json_encode([
    'success'          => true,
    'google_event_id' => $result['event_id'] ?? $existingEventId,
    'meet_link'       => $meetLink,
]);
exit;