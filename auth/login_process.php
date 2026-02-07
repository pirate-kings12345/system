<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $_SESSION['login_error'] = "Username and password are required.";
        header("Location: login.php");
        exit();
    }

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT user_id, username, password, role, status FROM users WHERE username = ?");
    if ($stmt === false) {
        // Handle prepare error
        $_SESSION['login_error'] = "Database error. Please try again later.";
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password AND check if the account is active
        if (password_verify($password, $user['password']) && $user['status'] === 'active') {
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // --- Permission Check before redirecting student ---
            if ($user['role'] === 'student') {
                $perm_stmt = $conn->prepare("SELECT is_allowed FROM role_permissions WHERE role = 'student' AND permission_key = 'view_student_dashboard'");
                $perm_stmt->execute();
                $perm_result = $perm_stmt->get_result();
                $permission = $perm_result->fetch_assoc();
                if (!$permission || $permission['is_allowed'] != 1) {
                    session_destroy(); // Log them out
                    header("Location: login.php?error=permission_denied");
                    exit();
                }
            }

            // Redirect based on role
            switch ($user['role']) {
                case 'superadmin':
                    header("Location: ../views/superadmin/dashboard.php");
                    break;
                case 'admin':
                    header("Location: ../views/admin/dashboard.php");
                    break;
                case 'instructor':
                    header("Location: ../views/instructor/dashboard.php");
                    break;
                case 'student':
                    header("Location: ../views/student/dashboard.php");
                    break;
                default:
                    header("Location: login.php"); // Fallback
                    break;
            }
            exit();
        }
    }

    // If we reach here, it's either wrong credentials or inactive account
    $_SESSION['login_error'] = "Invalid credentials or inactive account.";
    header("Location: login.php");
    exit();
}
?>