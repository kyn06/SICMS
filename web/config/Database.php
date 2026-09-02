<?php

date_default_timezone_set('Asia/Manila');

class Database{
    
    private $host;
    private $port;
    private $username;
    private $password;
    private $database;
    private $conn;

    public function __construct(){
        $this->host = getenv('DB_HOST') ?: '127.0.0.1';
        $this->port = (int) (getenv('DB_PORT') ?: '3307');
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
        $this->database = getenv('DB_NAME') ?: 'sicms';

    public function __construct(){
        $this->conn = mysqli_init();
        mysqli_options($this->conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

        $connected = false;

        try {
            $connected = mysqli_real_connect(
                $this->conn,
                $this->host,
                $this->username,
                $this->password,
                $this->database,
                $this->port
            );
        } catch (mysqli_sql_exception $e) {
            $connected = false;
        }

        if (!$connected) {
            $this->failGracefully();
        }
    }

    private function failGracefully() {
        $detail = mysqli_connect_error();
        $message = 'The database could not be reached. Please check that MySQL is running and try again.';

        if ($detail) {
            $message .= ' (' . $detail . ')';
        }

        $isAjax = strtolower(trim((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''))) === 'xmlhttprequest'
            || strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;

        if ($isAjax) {
            http_response_code(503);
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => false, 'message' => $message]);
        } else {
            http_response_code(503);
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Service Unavailable</title></head>'
                . '<body style="font-family: Verdana, sans-serif; background: #f5f7f4; color: #172017; display: grid; place-items: center; min-height: 100vh; margin: 0;">'
                . '<div style="background: #fff; border: 1px solid #dce5da; border-radius: 12px; padding: 32px 40px; max-width: 460px; box-shadow: 0 12px 32px rgba(18,60,27,.12);">'
                . '<h2 style="margin: 0 0 10px; font-size: 20px;">Service Unavailable</h2>'
                . '<p style="margin: 0; color: #54624f; font-size: 14px; line-height: 1.6;">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
                . '</div></body></html>';
        }

        exit;
    }

    public function setConnection($conn){
        $this->conn = $conn;
    }

    public function getConnection(){
        return $this->conn;
    }

    public function isConnected(){
        return $this->conn !== null && $this->conn !== false;
    }

    public function __destruct(){
        if ($this->isConnected()) {
            mysqli_close($this->conn);
        }
    }
}


