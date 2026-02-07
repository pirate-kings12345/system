<?php

class User {
    private $conn;
    private $table_name = "users"; // Assuming your users table is named 'users'

    public $id;
    public $username;
    public $email;
    public $password;
    public $role; // e.g., 'student', 'instructor', 'admin', 'superadmin'
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Used to find a user by username
    public function findUserByUsername($username) {
        $query = "SELECT user_id, username, email, password, role, date_created AS created_at
                  FROM " . $this->table_name . "
                  WHERE username = :username
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['user_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Used to find a user by email
    public function findUserByEmail($email) {
        $query = "SELECT user_id, username, email, password, role, date_created AS created_at
                  FROM " . $this->table_name . "
                  WHERE email = :email
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['user_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->password = $row['password'];
            $this->role = $row['role'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Create new user record
    public function register() {
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    username = :username,
                    email = :email,
                    password = :password,
                    role = :role";

        $stmt = $this->conn->prepare($query);

        // Sanitize data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password)); // Password is already hashed
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Bind values
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Used to verify password for login
    public function verifyPassword($password_input) {
        return password_verify($password_input, $this->password);
    }
}
?>