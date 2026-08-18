<?php

class Database{
    
    private $host = "127.0.0.1";
    private $port = 3307;
    private $username= "root";
    private $password = "";
    private $database = "sicms";
    private $conn;

    public function __construct(){
        $this->conn = mysqli_init();
        mysqli_options($this->conn, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

        $connected = mysqli_real_connect(
            $this->conn,
            $this->host,
            $this->username,
            $this->password,
            $this->database,
            $this->port
        );

        if(!$connected){
            die("Connection failed: " . mysqli_connect_error() . ". Please check MySQL, database name, username, password, and port.");
        }
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
