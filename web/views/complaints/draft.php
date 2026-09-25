<?php
require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/ComplaintDraft.php';
require_once __DIR__ . '/../../helpers/Security.php';

Security::startSession();
header('Content-Type: application/json; charset=UTF-8');
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please sign in again.']);
    exit;
}
$database = new Database();
$db = $database->getConnection();
User::setConnection($db);
ComplaintDraft::setConnection($db);
$user = User::findByEmail($_SESSION['email']);
if (!$user || $user['status'] !== 'active' || Security::normalizeRole($user['role']) !== 'student') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}
Security::requireCsrfToken();
$accountId = (int) $user['account_id'];
$action = (string) ($_POST['draft_action'] ?? '');

if ($action === 'discard') {
    ComplaintDraft::deleteForAccount($accountId);
    echo json_encode(['success' => true]);
    exit;
}
if ($action !== 'save') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid draft action.']);
    exit;
}
$submissionToken = (string) ($_POST['submission_token'] ?? '');
$sessionToken = (string) ($_SESSION['complaint_submission_token'] ?? '');
if ($submissionToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $submissionToken)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'This form is outdated. Refresh the page before saving.']);
    exit;
}
$payload = json_decode((string) ($_POST['payload'] ?? ''), true);
if (!is_array($payload)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'The draft data is invalid.']);
    exit;
}
$result = ComplaintDraft::saveVersioned($accountId, $submissionToken, $payload, (int) ($_POST['version'] ?? 0));
if ($result['status'] === 'conflict') {
    http_response_code(409);
    echo json_encode(['success' => false, 'conflict' => true, 'message' => 'A newer draft exists. Refresh before making more changes.']);
    exit;
}
if ($result['status'] !== 'saved') {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Draft could not be saved.']);
    exit;
}
echo json_encode(['success' => true, 'version' => (int) $result['draft']['version'], 'updated_at' => $result['draft']['updated_at']]);
