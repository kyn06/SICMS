<?php

require_once __DIR__ . '/../../helpers/Security.php';
Security::startSession();

require_once __DIR__ . '/../../config/Database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../../routes.php';

if (!isset($_SESSION['email'])) {
    http_response_code(403);
    exit;
}

$database = new Database();
$db = $database->getConnection();
User::setConnection($db);

$accountId = (int) ($_GET['id'] ?? 0);
if ($accountId <= 0) {
    http_response_code(404);
    exit;
}

$account = User::findRow($accountId);
$picPath = $account['profile_pic'] ?? '';

if (!is_string($picPath) || $picPath === '') {
    http_response_code(404);
    exit;
}

$relative = str_replace('\\', '/', $picPath);
$baseName = '';

if (str_starts_with($relative, 'storage/profile_pics/')) {
    $baseName = basename($relative);
}

if ($baseName === '') {
    http_response_code(404);
    exit;
}

$fullPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'profile_pics' . DIRECTORY_SEPARATOR . $baseName;

if (!is_file($fullPath)) {
    http_response_code(404);
    exit;
}

$extension = strtolower(pathinfo($baseName, PATHINFO_EXTENSION));

$contentTypes = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
];

if (!isset($contentTypes[$extension])) {
    http_response_code(415);
    exit;
}

header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . (string) filesize($fullPath));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');

readfile($fullPath);
exit;