<?php

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../helpers/Security.php';

class AccountController {
    private $database;
    private $db;
    private $user;
    private $allowedRoles = ['head-of-sdru', 'sdru-head'];
    private $creatableRoles = [
        'coordinator' => 'Discipline Coordinator',
        'reformation-coordinator' => 'Reformation Coordinator',
        'sdru-staff' => 'SDRU Staff',
    ];

    public function __construct() {
        Security::startSession();

        $this->authenticate();
    }

    public function index() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            Security::requireCsrfToken();
            $action = $_POST['action'] ?? '';
            if ($action === 'toggle_status') {
                $this->toggleStatus();
            }
            if ($action === 'update_respondent_contact') $this->updateRespondentContact();
            if ($action === 'update_respondent_profile') $this->updateRespondentProfile();
            $this->store();
        }

        $filters = $this->filters($_GET);

        return [
            'user' => $this->user,
            'accounts' => $this->isHead() ? User::listAccounts(array_merge($filters, ['group' => 'staff'])) : [],
            'complainants' => $this->isHead() ? User::listAccounts(array_merge($filters, ['group' => 'complainants'])) : [],
            'respondents' => $this->isHead() ? $this->respondents($filters) : [],
            'isHead' => $this->isHead(),
            'filters' => $filters,
            'roles' => $this->creatableRoles,
            'message' => $_SESSION['account_message'] ?? null,
            'errors' => $_SESSION['account_errors'] ?? [],
            'fieldErrors' => $_SESSION['account_field_errors'] ?? [],
            'old' => $_SESSION['account_old'] ?? [],
        ];
    }

    public function search() {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $filters = $this->filters($_GET);
            $accounts = $this->isHead() ? User::listAccounts(array_merge($filters, ['group' => 'staff'])) : [];
            $complainants = $this->isHead() ? User::listAccounts(array_merge($filters, ['group' => 'complainants'])) : [];
            $respondents = $this->isHead() ? $this->respondents($filters) : [];
            echo json_encode([
                'success' => true,
                'accounts' => $accounts,
                'complainants' => $complainants,
                'respondents' => $respondents,
                'summary' => $this->summaryFor(array_merge($accounts, $complainants)),
            ]);
        } catch (Throwable $exception) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Unable to load accounts.']);
        }
        exit;
    }

    private function toggleStatus() {
        $isAjax = ($_POST['ajax'] ?? '') === '1';

        $fail = function (string $message) use ($isAjax): void {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(422);
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }

            $_SESSION['account_errors'] = [$message];
            header('Location: index.php');
            exit;
        };

        $target = User::findRow((int) ($_POST['account_id'] ?? 0));

        $targetRole = $target
            ? strtolower(str_replace(['_', ' '], '-', (string) ($target['role'] ?? '')))
            : '';
        if (!$target || in_array($targetRole, ['student', 'respondent'], true)) {
            $fail('Only staff accounts can be enabled or disabled here.');
        }

        if ((int) $target['account_id'] === (int) $this->user['account_id']) {
            $fail('You cannot change the status of your own account.');
        }

        $status = ($_POST['status'] ?? '') === 'active' ? 'active' : 'inactive';

        if (!User::updateById((int) $target['account_id'], [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            $fail('Unable to update the account status.');
        }

        AuditLog::record(
            $this->user,
            'Account Status Update',
            ucwords(str_replace(['-', '_'], ' ', (string) $target['role'])) . ' account ' . $target['email'] . ' set to ' . $status . '.'
        );

        $message = $status === 'active' ? 'Account enabled successfully.' : 'Account disabled successfully.';

        if ($isAjax) {
            $all = array_merge(
                User::listAccounts(['group' => 'staff']),
                User::listAccounts(['group' => 'complainants'])
            );

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => $message,
                'status' => $status,
                'summary' => $this->summaryFor($all),
            ]);
            exit;
        }

        $_SESSION['account_message'] = $message;
        header('Location: index.php');
        exit;
    }

    private function updateRespondentContact() {
        $respondentId = (int) ($_POST['respondent_id'] ?? 0);
        $respondent = $this->respondentById($respondentId);
        if (!$respondent) $this->flashError('Respondent record not found.');
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $contact = substr(trim((string) ($_POST['contact_info'] ?? '')), 0, 255);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $this->flashError('Please enter a valid respondent email address.');
        if (!empty($respondent['account_id'])) {
            $existing = User::findByEmailInexact($email);
            if ($existing && (int) $existing['account_id'] !== (int) $respondent['account_id']) $this->flashError('That email is already used by another account.');
        }
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare('UPDATE complaint_respondents SET email = ?, contact_info = ? WHERE respondent_id = ?');
            $stmt->bind_param('ssi', $email, $contact, $respondentId);
            if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            if (!empty($respondent['account_id'])) {
                $stmt = $this->db->prepare('UPDATE accounts SET email = ?, phone_number = ?, updated_at = ? WHERE account_id = ?');
                $now = date('Y-m-d H:i:s');
                $phone = substr($contact, 0, 20);
                $stmt->bind_param('sssi', $email, $phone, $now, $respondent['account_id']);
                if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            }
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollback();
            error_log('SICMS respondent contact update failed: ' . $exception->getMessage());
            $this->flashError('Unable to update respondent contact information.');
        }
        $changed = [];
        if ($email !== (string) $respondent['email']) $changed[] = 'email';
        if ($contact !== (string) $respondent['contact_info']) $changed[] = 'contact number';
        AuditLog::record($this->user, 'Respondent Contact Updated', 'Updated ' . ($changed ? implode(' and ', $changed) : 'contact information') . ' for respondent #' . $respondentId . '.');
        $_SESSION['account_message'] = 'Respondent contact information updated.';
        header('Location: index.php'); exit;
    }

    private function respondents(array $filters) {
        $sql = "SELECT a.account_id, a.first_name, a.last_name, a.email, a.phone_number, a.student_number,
                       a.college, a.course, a.status AS account_status,
                       MIN(r.respondent_id) AS respondent_id, MAX(r.respondent_type) AS respondent_type,
                       MAX(r.employee_no) AS employee_no, MAX(r.office_department) AS office_department,
                       MAX(r.affiliation) AS affiliation,
                       GROUP_CONCAT(DISTINCT c.case_number ORDER BY c.case_number SEPARATOR ', ') AS case_numbers
                FROM accounts a
                LEFT JOIN complaint_respondents r ON r.account_id = a.account_id
                LEFT JOIN complaints c ON c.complaint_id = r.complaint_id AND (c.case_source IS NULL OR c.case_source <> 'Legacy')
                WHERE LOWER(REPLACE(REPLACE(a.role, '_', '-'), ' ', '-')) = 'respondent'";
        $params = []; $types = '';
        if (!empty($filters['search'])) { $sql .= ' AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR a.phone_number LIKE ? OR c.case_number LIKE ?)'; $like = '%' . $filters['search'] . '%'; $params = [$like, $like, $like, $like, $like]; $types = 'sssss'; }
        $sql .= ' GROUP BY a.account_id ORDER BY a.created_at DESC, a.account_id DESC';
        $stmt = $this->db->prepare($sql); if (!$stmt) return [];
        if ($params) $stmt->bind_param($types, ...$params); $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC) ?: [];
    }

    private function respondentById(int $respondentId) {
        $stmt = $this->db->prepare('SELECT respondent_id, account_id, email, contact_info FROM complaint_respondents WHERE respondent_id = ? LIMIT 1');
        $stmt->bind_param('i', $respondentId); $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    private function updateRespondentProfile() {
        if (!$this->isHead()) $this->flashError('Only the Head of SDRU can edit respondent details.');
        $respondentId = (int) ($_POST['respondent_id'] ?? 0);
        $respondent = $this->respondentById($respondentId);
        if (!$respondent || empty($respondent['account_id'])) $this->flashError('Respondent record not found.');
        $account = User::findRow((int) $respondent['account_id']);
        if (!$account || $this->roleKey($account['role'] ?? '') !== 'respondent') $this->flashError('Respondent account not found.');

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flashError('Provide a name and valid email address.');
        }
        $existing = User::findByEmailInexact($email);
        if ($existing && (int) $existing['account_id'] !== (int) $account['account_id']) $this->flashError('That email is already used by another account.');

        $contact = substr(trim((string) ($_POST['contact_info'] ?? '')), 0, 20);
        $fullName = $firstName . ' ' . $lastName;
        $this->db->begin_transaction();
        try {
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare('UPDATE accounts SET first_name = ?, last_name = ?, email = ?, phone_number = ?, updated_at = ? WHERE account_id = ?');
            $stmt->bind_param('sssssi', $firstName, $lastName, $email, $contact, $now, $account['account_id']);
            if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            $stmt = $this->db->prepare('UPDATE complaint_respondents SET full_name = ?, email = ?, contact_info = ? WHERE respondent_id = ? AND account_id = ?');
            $stmt->bind_param('sssii', $fullName, $email, $contact, $respondentId, $account['account_id']);
            if (!$stmt->execute()) throw new RuntimeException($stmt->error);
            $this->db->commit();
        } catch (Throwable $exception) {
            $this->db->rollback();
            error_log('SICMS respondent profile update failed: ' . $exception->getMessage());
            $this->flashError('Unable to update respondent details.');
        }
        AuditLog::record($this->user, 'Respondent Profile Updated', 'Updated respondent account ' . $email . '.');
        $_SESSION['account_message'] = 'Respondent details updated. Case links were preserved.';
        header('Location: index.php'); exit;
    }

    private function isHead(): bool { return in_array($this->roleKey(), ['head-of-sdru', 'sdru-head'], true); }
    private function roleKey($role = null): string { return strtolower(str_replace(['_', ' '], '-', (string) ($role ?? $this->user['role'] ?? ''))); }
    private function flashError(string $message): void { $_SESSION['account_errors'] = [$message]; header('Location: index.php'); exit; }

    private function summaryFor(array $all) {
        $active = count(array_filter($all, fn($account) => strtolower((string) $account['status']) === 'active'));

        return ['total' => count($all), 'active' => $active, 'inactive' => count($all) - $active];
    }

    private function filters(array $input) {
        return [
            'search' => substr(trim((string) ($input['search'] ?? '')), 0, 255),
            'role' => substr(trim((string) ($input['role'] ?? '')), 0, 50),
            'status' => substr(trim((string) ($input['status'] ?? '')), 0, 30),
        ];
    }

    public function clearFlash() {
        unset($_SESSION['account_message'], $_SESSION['account_errors'], $_SESSION['account_field_errors'], $_SESSION['account_old']);
    }

    private function store() {
        if (!$this->isHead()) $this->flashError('Only the Head SDRU can create staff accounts.');
        $validation = $this->validate($_POST);
        $errors = $validation['errors'];

        if (!empty($errors) || !empty($validation['fields'])) {
            $_SESSION['account_errors'] = $errors;
            $_SESSION['account_field_errors'] = $validation['fields'];
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
        $fieldErrors = [];
        $field = function (string $key, string $message) use (&$fieldErrors) {
            $fieldErrors[$key] = $message;
        };
        $firstName = trim($post['first_name'] ?? '');
        $lastName = trim($post['last_name'] ?? '');
        $email = strtolower(trim($post['email'] ?? ''));
        $password = $post['password'] ?? '';
        $confirm = $post['confirm_password'] ?? '';
        $role = trim($post['role'] ?? '');

        foreach (['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'password' => 'Temporary password', 'confirm_password' => 'Password confirmation', 'role' => 'Role'] as $key => $label) {
            if (trim((string) ($post[$key] ?? '')) === '') {
                $field($key, $label . ' is required.');
            }
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $field('email', 'Please enter a valid email address.');
        }

        if ($email !== '') {
            $allowedDomains = ['clsu2.edu.ph', 'sicms.local'];
            $emailDomain = substr($email, strpos($email, '@') + 1);
            if (!in_array($emailDomain, $allowedDomains, true)) {
                $field('email', 'Only @clsu2.edu.ph and @sicms.local email addresses are allowed.');
            }
        }

        if ($password !== $confirm) {
            $field('confirm_password', 'Passwords do not match.');
        }

        $errors = array_merge($errors, Security::strongPasswordErrors($password));

        if (!array_key_exists($role, $this->creatableRoles)) {
            $field('role', 'Please select a valid role.');
        }

        if ($email !== '' && User::findByEmail($email)) {
            $field('email', 'An account with that email already exists.');
        }

        return ['errors' => $errors, 'fields' => $fieldErrors];
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
