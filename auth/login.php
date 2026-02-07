<?php
session_start();
require_once '../config/db_connect.php';  // ✅ correct path

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($username) || empty($password) || empty($role)) {
        $_SESSION['error'] = "⚠️ Please fill out all fields.";
        header("Location: ../index.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE username = ? AND role = ? AND status = 'active' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // --- Role Verification ---
            // Ensure the role selected in the form matches the user's actual role in the database.
            if ($user['role'] !== $role) {
                $_SESSION['error'] = "❌ Invalid credentials for the selected role.";
                header("Location: ../index.php");
                exit();
            }

            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            // --- Permission Check before redirecting student ---
            if ($user['role'] === 'student') {
                // Use the has_permission function for consistency
                require_once '../includes/functions.php';
                if (!has_permission('student', 'view_student_dashboard', $conn)) {
                    session_unset();
                    session_destroy(); // Log them out completely
                    $_SESSION['error'] = "❌ Access to the dashboard is currently disabled.";
                    header("Location: ../index.php"); // Redirect to the main login page
                    exit();
                }
            }
            // --- End Permission Check ---

            switch ($user['role']) {
                case 'admin':
                    if ($user['username'] === 'superadmin') {
                        header("Location: ../views/superadmin/dashboard.php");
                    } else {
                        header("Location: ../views/admin/dashboard.php");
                    }
                    break;
                case 'instructor':
                    header("Location: ../views/instructor/dashboard.php");
                    break;
                case 'student':
                    header("Location: ../views/student/dashboard.php");
                    break;
                default:
                    $_SESSION['error'] = "❌ Unknown role.";
                    header("Location: ../index.php");
                    break;
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
