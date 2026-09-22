<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
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

$user = User::findByEmail($_SESSION['email']);
if (!$user || $user['status'] !== 'active') {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Access denied.']));
}

// Staff-only endpoint. Respondent portals and complainants must not be able
// to enumerate existing accounts by student number.
$role = Security::normalizeRole($user['role'] ?? '');
$staffRoles = ['admin', 'sdr-staff', 'sdru-staff', 'coordinator', 'reformation-coordinator', 'head-of-sdru', 'sdru-head'];
if (!in_array($role, $staffRoles, true)) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Access denied.']));
}

$studentNo = trim((string) ($_GET['student_number'] ?? ''));

if ($studentNo === '') {
    http_response_code(422);
    exit(json_encode(['success' => false, 'message' => 'Enter a student number to search.']));
}

try {
    $accounts = User::findActiveStudentsByStudentNumber($studentNo);

    $results = array_map(static function (array $account): array {
        $fullName = trim(($account['first_name'] ?? '') . ' ' . ($account['last_name'] ?? ''));
        $age = null;
        if (!empty($account['birthday'])) {
            $birthday = DateTime::createFromFormat('Y-m-d', $account['birthday']);
            if ($birthday && $birthday <= new DateTime('today')) {
                $age = (int) $birthday->diff(new DateTime('today'))->y;
            }
        }
        return [
            'account_id' => (int) $account['account_id'],
            'full_name' => $fullName,
            'student_number' => (string) ($account['student_number'] ?? ''),
            'college' => (string) ($account['college'] ?? ''),
            'course' => (string) ($account['course'] ?? ''),
            'section' => (string) ($account['section'] ?? ''),
            'phone_number' => (string) ($account['phone_number'] ?? ''),
            'gender' => (string) ($account['gender'] ?? ''),
            'birthday' => (string) ($account['birthday'] ?? ''),
            'address' => (string) ($account['address'] ?? ''),
            'email' => (string) ($account['email'] ?? ''),
            'age' => $age,
        ];
    }, $accounts);

    exit(json_encode([
        'success' => true,
        'accounts' => $results,
    ], JSON_THROW_ON_ERROR));
} catch (Throwable $exception) {
    http_response_code(500);
    exit(json_encode(['success' => false, 'message' => 'Unable to search for student accounts.']));
}