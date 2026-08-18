<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class AccountController {
    private $database;
    private $db;
    private $user;
    private $allowedRoles = ['head-of-sdru', 'sdru-head', 'super-admin'];
    private $creatableRoles = [
        'coordinator' => 'Coordinator',
        'sdru-staff' => 'SDRU Staff',
    ];

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $this->store();
        }

        $filters = $this->filters($_GET);

        return [
            'user' => $this->user,
            'accounts' => User::listAccounts($filters),
            'filters' => $filters,
            'roles' => $this->creatableRoles,
            'message' => $_SESSION['account_message'] ?? null,
            'errors' => $_SESSION['account_errors'] ?? [],
            'old' => $_SESSION['account_old'] ?? [],
        ];
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $accounts = User::listAccounts($this->filters($_GET));
            $active = count(array_filter($accounts, fn($account) => strtolower((string) $account['status']) === 'active'));
            echo json_encode(['success' => true, 'accounts' => $accounts, 'summary' => ['total' => count($accounts), 'active' => $active, 'inactive' => count($accounts) - $active]]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to load accounts.']);
        }
        exit;
    }

    private function filters(array $input) {
        return [
            'search' => substr(trim((string) ($input['search'] ?? '')), 0, 255),
            'role' => substr(trim((string) ($input['role'] ?? '')), 0, 50),
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 30),
        ];
    }

    public function clearFlash() {
        unset($_SESSION['account_message'], $_SESSION['account_errors'], $_SESSION['account_old']);
    }

    private function store() {
        $errors = $this->validate($_POST);

        if (!empty($errors)) {
            $_SESSION['account_errors'] = $errors;
            $_SESSION['account_old'] = $_POST;
            header('Location: index.php');
            exit;
        }

        $now = date('Y-m-d H:i:s');
        $created = User::create([
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'password_hash' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => trim($_POST['role']),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($created) {
            AuditLog::record(
                $this->user,
                'User Creation',
                'Created ' . $this->creatableRoles[trim($_POST['role'])] . ' account for ' . trim($_POST['email']) . '.'
            );
            $_SESSION['account_message'] = 'Account created successfully.';
        } else {
            $_SESSION['account_errors'] = ['Unable to create account.'];
        }

        header('Location: index.php');
        exit;
    }

    private function validate(array $post) {
        $errors = [];
        $firstName = trim($post['first_name'] ?? '');
        $lastName = trim($post['last_name'] ?? '');
        $email = trim($post['email'] ?? '');
        $password = $post['password'] ?? '';
        $confirm = $post['confirm_password'] ?? '';
        $role = trim($post['role'] ?? '');

        if ($firstName === '' || $lastName === '' || $email === '' || $password === '' || $confirm === '' || $role === '') {
            $errors[] = 'All account fields are required.';
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if ($password !== $confirm) {
            $errors[] = 'Passwords do not match.';
        }

        $errors = array_merge($errors, Security::strongPasswordErrors($password));

        if (!array_key_exists($role, $this->creatableRoles)) {
            $errors[] = 'Please select a valid role.';
        }

        if ($email !== '' && User::findByEmail($email)) {
            $errors[] = 'An account with that email already exists.';
        }

        return $errors;
    }

    private function authenticate() {
        if (!isset($_SESSION['email'])) {
            header('Location: ../auth/login.php');
            exit;
        }

        $this->database = new Database();
        $this->db = $this->database->getConnection();

        User::setConnection($this->db);
        AuditLog::setConnection($this->db);

        $this->user = User::findByEmail($_SESSION['email']);
        $roleKey = strtolower(str_replace(['_', ' '], '-', $this->user['role'] ?? ''));

        if (!$this->user || $this->user['status'] !== 'active' || !in_array($roleKey, $this->allowedRoles, true)) {
            http_response_code(403);
            echo 'Access denied. Head SDRU account required.';
            exit;
        }
    }
}
