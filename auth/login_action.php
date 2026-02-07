<?php
session_start();
require_once '../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $role_from_form = trim($_POST['role'] ?? '');
        $_SESSION['error'] = "⚠️ Please fill out all fields.";
        header("Location: ../login.php?role=" . urlencode($role_from_form));
        exit();
    }

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role_from_form = trim($_POST['role'] ?? '');

    // Prepare the SQL to fetch the user based on username and their database role
    $sql = "SELECT user_id, username, password, role, status, email FROM users WHERE username = ? AND status = 'active' LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) { // Database error
        $_SESSION['error'] = "❌ Database error. Please try again later.";
        header("Location: ../login.php?role=" . urlencode($role_from_form));
        exit();
    }
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // --- Role Verification ---
            // Ensure the role selected in the form matches the user's actual role in the database.
            if ($user['role'] !== $role_from_form) {
                $_SESSION['error'] = "❌ Invalid credentials for the selected role.";
                header("Location: ../login.php?role=" . urlencode($role_from_form));
                exit();
            }
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);
            
            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // Redirect based on the actual role from the database
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
                    // Fallback for unknown roles
                    header("Location: ../index.php"); 
                    break;
            }
            exit();
        }
    }

    $_SESSION['error'] = "❌ Invalid credentials or inactive account.";
    header("Location: ../login.php?role=" . urlencode($role_from_form));
    exit();
}
?>