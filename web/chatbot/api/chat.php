<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/chatbot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'POST requests only.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$message = trim((string)($data['message'] ?? $_POST['message'] ?? ''));

if ($message === '') {
    echo json_encode([
        'success' => true,
        'answer' => 'Please type a question so I can help you.',
        'suggestions' => Chatbot::defaultSuggestions()
    ]);
    exit;
}

$result = Chatbot::respond($message);
echo json_encode(['success' => true] + $result);
