<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "⚠️ Please fill out all fields.";
        header("Location: ../index.php");
        exit();
    }

    // Fetch user by username only
    $sql = "SELECT * FROM users WHERE username = ? AND status = 'active' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Redirect based on role + superadmin check
            if ($user['role'] === 'admin' && $user['username'] === 'superadmin') {
                header("Location: ../views/superadmin/dashboard.php");
            } elseif ($user['role'] === 'admin') {
                header("Location: ../views/admin/dashboard.php");
            } elseif ($user['role'] === 'instructor') {
                header("Location: ../views/instructor/dashboard.php");
            } elseif ($user['role'] === 'student') {
                header("Location: ../views/student/dashboard.php");
            } else {
                $_SESSION['error'] = "❌ Unknown role.";
                header("Location: ../index.php");
            }
            exit();

        } else {
            $_SESSION['error'] = "❌ Invalid password.";
        }

    } else {
        $_SESSION['error'] = "❌ User not found or inactive.";
    }

    header("Location: ../index.php");
    exit();
}
?>
