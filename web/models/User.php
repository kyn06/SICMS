<?php

require_once 'Model.php';
require_once 'AuditLog.php';

class User extends Model {
    protected static $table = 'accounts';
    protected static $primaryKey = 'account_id';

    public $account_id;
    public $first_name;
    public $last_name;
    public $email;
    public $password_hash;
    public $auth_provider;
    public $role;
    public $status;
    public $college_id;
    public $created_at;
    public $updated_at;

    public function __construct(array $data = []) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function all() {
        $results = parent::all();

        return $results
            ? array_map(fn($user) => new self($user), $results)
            : null;
    }

    public static function find($id) {
        $result = parent::find($id);

        return $result
            ? new self($result)
            : null;
    }

    public static function findByEmail($email) {
        $query = "SELECT * FROM accounts WHERE email = ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public static function login($email, $password) {
        $userData = self::findByEmail($email);

        if ($userData) {
            if (password_verify($password, $userData['password_hash'])) {
                if ($userData['status'] == 'inactive') {
                    $_SESSION['error'] = "Your account is deactivated. Please contact the super-admin.";
                    return false;
                }
                session_regenerate_id(true);
                $_SESSION['email'] = $email;
                $_SESSION['role']  = $userData['role'];
                AuditLog::record($userData, 'User Login', 'User logged in successfully.');
                return true;
            }
        }

        $_SESSION['error'] = "Invalid email or password.";
        return false;
    }

    public static function create(array $data) {
        $result = parent::create($data);
        if ($result) {
            AuditLog::record($result, 'User Creation', 'User account created for ' . ($result['email'] ?? 'new account') . '.');
        }

        return $result
            ? new self($result)
            : null;
    }

    public function update(array $data) {
        $previousRole = $this->role;
        $previousStatus = $this->status;
        $result = parent::updateById($this->account_id, $data);

        if ($result) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }

            AuditLog::record($this, 'User Update', 'User account updated.');

            if (array_key_exists('role', $data) && $previousRole !== $data['role']) {
                AuditLog::record($this, 'Role Change', 'Role changed from ' . $previousRole . ' to ' . $data['role'] . '.');
            }

            if (array_key_exists('status', $data) && $previousStatus !== $data['status']) {
                $action = $data['status'] === 'active' ? 'User Activation' : 'User Deactivation';
                AuditLog::record($this, $action, 'User status changed from ' . $previousStatus . ' to ' . $data['status'] . '.');
            }

            return true;
        }

        return false;
    }

    public function save() {
        $data = [
            'first_name'    => $this->first_name,
            'last_name'     => $this->last_name,
            'email'         => $this->email,
            'password_hash' => $this->password_hash,
            'status'        => $this->status,
            'role'          => $this->role,
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $this->update($data);
    }

    public function delete() {
        $result = parent::deleteById($this->account_id);

        if ($result) {
            foreach ($this as $key => $value) {
                if (property_exists($this, $key)) {
                    unset($this->$key);
                }
            }
            return true;
        }

        return false;
    }

    public function getUsers() {
        $users = self::all();

        if (empty($users)) {
            http_response_code(404);
            echo "<h1 style='text-align: center; 
                font-size: 70px; font-family: Verdana, sans-serif; 
                margin-top: 250px; 
                background: -webkit-linear-gradient(rgb(88, 10, 10),rgb(182, 98, 98)); 
                -webkit-background-clip: text;  
                -webkit-text-fill-color: transparent;'>
                    No Users Found!
                    <br>  ｡°(°.◜ᯅ◝°)°｡  
                  </h1>";
            exit();
        }

        return $users;
    }

    public static function countAllUsers() {
        return self::countAll();
    }

    public static function countNewUsers($startDate, $endDate) {
        return self::countNew($startDate, $endDate);
    }

    public static function countUsersByStatus($status) {
        return self::countByStatus($status);
    }

    public static function listAccounts(array $filters = []) {
        $sql = "SELECT account_id, first_name, last_name, email, role, status, created_at, updated_at
                FROM accounts
                WHERE 1 = 1";
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            array_push($params, $like, $like, $like);
            $types .= 'sss';
        }

        if (!empty($filters['role'])) {
            $sql .= " AND role = ?";
            $params[] = $filters['role'];
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        $sql .= " ORDER BY created_at DESC, account_id DESC";

        $stmt = self::$conn->prepare($sql);

        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    private $user;

    public function authenticateUser() {
        if (!isset($_SESSION['email'])) {
            header("Location: ../auth/login.php");
            exit();
        }

        $user = self::findByEmail($_SESSION['email']);

        if (!$user) {
            session_destroy();
            header("Location: ../auth/login.php");
            exit();
        }

        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $isBooksPath = strpos($currentPath, '/books/') !== false;
        $isUsersPath = strpos($currentPath, '/users/') !== false;

        $this->user = $user;

        $role = $_SESSION['role'];

        if ($role === 'super-admin') {
            return $user;
        } elseif (in_array($role, ['librarian', 'admin'])) {
            if ($isBooksPath) {
                return $user;
            } else {
                http_response_code(403);
                echo "<h1 style='font-size: 60px; text-align: center'>
                        Access Denied. You can only access book-related pages.
                      </h1>";
                echo '<div style="font-size: 30px; text-align: center">
                        <a href="../books/index.php" class="btn btn-outline-secondary">Go to Books</a>
                      </div>';
                exit();
            }
        } else {
            http_response_code(403);
            echo "<h1 style='font-size: 60px; text-align: center'>
                    Access Denied. Contact your super-admin to access this page.
                  </h1>";
            echo '<div style="font-size: 30px; text-align: center">
                    <a href="../index.php" class="btn btn-outline-secondary">Back to Home</a>
                  </div>';
            exit();
        }
    }

    public function getUserName() {
        return $this->user['first_name'];
    }
}
