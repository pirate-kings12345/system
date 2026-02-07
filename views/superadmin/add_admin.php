<?php
require_once '../../includes/session_check.php';

// Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    // You can set an error message in the session and redirect
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: ../superadmin/dashboard.php?tab=manage-admin");
    exit();
}

require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Data Sanitization and Validation ---
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($email) || empty($password)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: ../superadmin/dashboard.php?tab=manage-admin");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../superadmin/dashboard.php?tab=manage-admin");
        exit();
    }

    // --- Password Hashing ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // --- Database Insertion ---
    // Using prepared statements to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'admin', 'active')");
    if ($stmt === false) {
        // Handle prepare error
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: ../superadmin/dashboard.php?tab=manage-admin");
        exit();
    }

    $stmt->bind_param("sss", $username, $email, $hashed_password);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Admin '$username' created successfully!";
    } else {
        $_SESSION['error'] = "Failed to create admin. Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../superadmin/dashboard.php?tab=manage-admin");
    exit();
}
?>