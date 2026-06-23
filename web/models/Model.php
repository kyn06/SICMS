<?php

class Model{
    protected static $conn;
    protected static $table;

    public static function setConnection($conn){
        self::$conn = $conn;
    }

    public static function all(){
        $sql = "SELECT * FROM " . static::$table;
        $result = mysqli_query(self::$conn, $sql);
        return (mysqli_num_rows($result) > 0) ? mysqli_fetch_all($result, MYSQLI_ASSOC) : null;
    }

    public static function find($id){
        $sql = "SELECT * FROM " . static::$table . " WHERE id = " . $id;
        $result = mysqli_query(self::$conn, $sql);
        return (mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    }

    public static function create(array $data){
        $columns = implode(", ", array_keys($data));
        $values = implode(", ", array_fill(0, count($data), '?'));
        $sql = "INSERT INTO " . static::$table . " ($columns) VALUES ($values)";
        $stmt = mysqli_prepare(self::$conn, $sql);

        if(!$stmt){
            die("Error preparing statement " . mysqli_error(self::$conn));
        }

        $types = '';
        $values = [];

        foreach($data as $value){
            if(is_int($value)){
                $types .= 'i';
            } elseif(is_float($value)){
                $types .= 'd';
            } else{
                $types .= 's';
            }
            $values[] = $value;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $result = mysqli_stmt_execute($stmt);

        if(!$result){
            die("Error executing statement " . mysqli_error(self::$conn));
        } else{
            $id = mysqli_insert_id(self::$conn);
            return self::find($id);
        }
    }

    public static function updateById($id, array $data){
        $set = implode(", ", array_map(fn($key) => "$key = ?", array_keys($data)));
        $sql = "UPDATE " . static::$table . " SET $set WHERE id = ?";
        $stmt = mysqli_prepare(self::$conn, $sql);

        if(!$stmt){
            die("Error preparing statement " . mysqli_error(self::$conn));
        }

        $types = '';
        $values = [];

        foreach($data as $value){
            if(is_int($value)){
                $types .= 'i';
            } elseif(is_float($value)){
                $types .= 'd';
            } else{
                $types .= 's';
            }
            $values[] = $value;
        }

        $types .= 'i';
        $values[] = $id;
        mysqli_stmt_bind_param($stmt, $types, ...$values);
        $result = mysqli_stmt_execute($stmt);

        if(!$result){
            die("Error executing statement " . mysqli_error(self::$conn));
        } else{
            return self::find($id);
        }
    }

    public static function deleteById($id){
        $sql = "DELETE FROM " . static::$table . " WHERE id = ?";
        $stmt = mysqli_prepare(self::$conn, $sql);

        if(!$stmt){
            die("Error preparing statement " . mysqli_error(self::$conn));
        }

        mysqli_stmt_bind_param($stmt, 'i', $id);
        $result = mysqli_stmt_execute($stmt);

        if(!$result){
            die("Error executing statement " . mysqli_error(self::$conn));
        } else{
            return true;
        }
    }

    public static function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . static::$table;
        $result = self::$conn->query($query);
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        } else {
            die("Query failed: " . self::$conn->error);
        }
    }

    public static function countNew($startDate, $endDate) {
        $query = "SELECT COUNT(*) as total FROM " . static::$table . " WHERE created_at BETWEEN ? AND ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        } else {
            die("Query failed: " . self::$conn->error);
        }
    }

    public static function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM " . static::$table . " WHERE status = ?";
        $stmt = self::$conn->prepare($query);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            return $row['total'];
        } else {
            die("Query failed: " . self::$conn->error);
        }
    }
}
