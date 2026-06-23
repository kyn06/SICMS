<?php

require_once 'Model.php';

class User extends Model{
    protected static $table = 'accounts';

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

    public function __construct(array $data = []){
        foreach($data as $key => $value){
            if(property_exists($this, $key)){
                $this->$key = $value;
            }
        }
    }

    public static function all(){
        $results = parent::all();

        return $results
            ? array_map(fn($user) => new self($user), $results)
            : null;
    }

    public static function find($id){
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

    public static function login($email, $password_hash) {
        $userData = self::findByEmail($email);

        if ($userData) {
            if (password_hash_verify($password_hash, $userData['password'])) {
                if ($userData['status'] == 'inactive') {
                    $_SESSION['error'] = "Your account is deactivated. 
                                            Please contact the super-admin.";
                    return false;
                }
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $userData['role'];
                return true;
            }
        }
        $_SESSION['error'] = "Invalid email or password.";
        return false;
    }

    public static function create(array $data){
        $result = parent::create($data);

        return $result 
            ? new self($result)
            : null;
    }

    public function update(array $data){
        $result = parent::updateById($this->account_id, $data);

        if($result){
            foreach($data as $key => $value){
                if(property_exists($this, $key)){
                    $this->$key = $value;
                }
            }
            return true;
        }
        else{
            return false;
        }
    }

    public function save(){
        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'password_hash' => $this->password_hash,
            'status' => $this->status,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];

        $this->update($data);
    }

    public function delete(){
        $result = parent::deleteById($this->account_id);

        if($result){
            foreach($this as $key => $value){
                if(property_exists($this, $key)){
                    unset($this->$key);
                }
            }
            return true;
        }
        else{
            return false;
        }
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

    //UserController Methods
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

        // Get the current URL path
        $currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $isBooksPath = strpos($currentPath, '/books/') !== false;
        $isUsersPath = strpos($currentPath, '/users/') !== false;

        $this->user = $user;

        // Role-based access control
        $role = $_SESSION['role'];

        if ($role === 'super-admin') {
            // Super-admin can access both /books and /users
            return $user;
        } elseif (in_array($role, ['librarian', 'admin'])) {
            // Librarian and Admin can only access /books
            if ($isBooksPath) {
                return $user;
            } else {
                // If trying to access /users or other paths, deny access
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
            // Any other role (if applicable) is denied access
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
