<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    $_SESSION['error_msg'] = "You are not authorized to perform this action.";
    header("Location: ../superadmin/dashboard.php#manage-admin");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($userId) || empty($username) || empty($email)) {
        $_SESSION['edit_admin_msg'] = "Username and Email are required.";
        $_SESSION['edit_admin_msg_type'] = 'error';
        header("Location: ../superadmin/dashboard.php#manage-admin");
        exit();
    }

    if (!empty($password)) {
        // If password is provided, hash it and update it
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE user_id = ? AND role = 'admin'");
        $stmt->bind_param("sssi", $username, $email, $hashedPassword, $userId);
    } else {
        // If password is not provided, update only username and email
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE user_id = ? AND role = 'admin'");
        $stmt->bind_param("ssi", $username, $email, $userId);
    }

    $stmt->execute();
    $_SESSION['edit_admin_msg'] = "Admin details updated successfully.";
    $_SESSION['edit_admin_msg_type'] = 'success';
    $stmt->close();
}

$conn->close();
header("Location: ../superadmin/dashboard.php#manage-admin");
exit();