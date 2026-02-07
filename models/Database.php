<?php
class Database {
    private $host = "localhost";
    private $db_name = "course_system"; // Assuming your database name
    private $username = "root"; // Default XAMPP username
    private $password = ""; // Default XAMPP password
    public $conn;

    // Get the database connection
    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
?>