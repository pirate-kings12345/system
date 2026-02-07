<?php
// This file is included by auth/register.php, which already starts the session
// and includes necessary files like db_connect.php, Database.php, User.php.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    $errors = [];

    // Store old input values in session for repopulation
    $_SESSION['old_username'] = $username;
    $_SESSION['old_email'] = $email;

    $db = new Database();
    $conn = $db->connect();
    $userModel = new User($conn);

    // Validate Username
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    } elseif ($userModel->findUserByUsername($username)) {
        $errors[] = "Username already taken.";
    }

    // Validate Email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($userModel->findUserByEmail($email)) {
        $errors[] = "Email already registered.";
    }

    // Validate Password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Validate Confirm Password
    if (empty($confirm_password)) {
        $errors[] = "Confirm Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Validate Role
    if ($role !== 'student') {
        $errors[] = "Registration is only open to students.";
    }

    if (empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Create user
        $userModel->username = $username;
        $userModel->email = $email;
        $userModel->password = $hashed_password;
        $userModel->role = $role;

        if ($userModel->register()) {
            $_SESSION['register_success'] = "Registration successful! You can now log in.";
            // Clear old input values on success
            unset($_SESSION['old_username']);
            unset($_SESSION['old_email']);
            unset($_SESSION['old_role']);
        } else {
            $_SESSION['register_error'] = "Registration failed. Please try again.";
        }
    } else {
        $_SESSION['register_error'] = implode('<br>', $errors);
    }

    header('Location: ../index.php');
    exit();
} else {
    // If accessed directly without POST, redirect to index
    header('Location: ../index.php');
    exit();
}
?>