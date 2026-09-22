<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Case.php';
require_once __DIR__ . '/../helpers/Security.php';

header('Content-Type: application/json; charset=utf-8');

Security::startSession();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Authentication required.']));
}

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);
CaseRecord::setConnection($db);

$user = User::findByEmail($_SESSION['email']);
if (!$user || $user['status'] !== 'active') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Access denied.']));
}

// This endpoint powers the staff Case Management status banner. Participant
// portals have their own case views and must not be able to enumerate case IDs.
$role = Security::normalizeRole($user['role'] ?? '');
$staffRoles = ['admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head'];
if (!in_array($role, $staffRoles, true)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Access denied.']));
}

$complaintId = (int) ($_GET['id'] ?? 0);

try {
    $case = CaseRecord::findCase($complaintId);

    if (!$case) {
        http_response_code(404);
        exit(json_encode(['success' => false, 'message' => 'Case not found.']));
    }

    exit(json_encode([
        'success' => true,
        'status' => $case['status'] ?? '',
        'updated_at' => $case['updated_at'] ?? null,
    ], JSON_THROW_ON_ERROR));
} catch (Throwable $exception) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Unable to load case status.']));
}
