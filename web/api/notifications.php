<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../helpers/Security.php';

header('Content-Type: application/json; charset=utf-8');

Security::startSession();

if (!isset($_SESSION['email'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Authentication required.']));
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
}

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);
Notification::setConnection($db);

$user = User::findByEmail($_SESSION['email']);
if (!$user || $user['status'] !== 'active') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Access denied.']));
}

$action = $_POST['notification_action'] ?? '';

if ($action === 'mark_all') {
    Notification::markAllAsRead((int) $user['account_id']);
} elseif ($action === 'mark_one') {
    Notification::markAsRead((int) ($_POST['notification_id'] ?? 0), (int) $user['account_id']);
} else {
    http_response_code(422);
    exit(json_encode(['success' => false, 'message' => 'Unknown notification action.']));
}

exit(json_encode([
    'success' => true,
    'unread' => Notification::unreadCount((int) $user['account_id']),
]));
