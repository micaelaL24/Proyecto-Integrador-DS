<?php
class Database {
    private $host = "localhost";
    private $db_name = "biblioteca";
    private $username = "root"; 
    private $password = ""; 
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch(PDOException $exception) {
            // NO hacer echo, solo devolver null
            error_log("Error de conexión: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}
?>