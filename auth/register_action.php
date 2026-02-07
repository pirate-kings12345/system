<?php
session_start();

// 1. Use the central database connection file
require_once '../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $course_id = $_POST['course'];

    // --- Validation ---
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($course_id)) {
        $_SESSION['register_error'] = "All fields are required.";
        header("Location: ../index.php");
        exit();
    }

    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Passwords do not match.";
        header("Location: ../index.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Invalid email format.";
        header("Location: ../index.php");
        exit();
    }

    // Check if username or email already exists
    $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        $_SESSION['register_error'] = "Username or email already taken.";
        $stmt_check->close();
        header("Location: ../index.php");
        exit();
    }
    $stmt_check->close();

    // --- Database Insertion ---
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'student';
    $status = 'active'; // Or 'pending' if you want admin approval

    $conn->begin_transaction();

    try {
        // Insert into users table
        $stmt_user = $conn->prepare("INSERT INTO users (username, password, email, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt_user->bind_param("sssss", $username, $hashed_password, $email, $role, $status);
        $stmt_user->execute();
        $user_id = $stmt_user->insert_id;
        $stmt_user->close();

        // Insert into students table
        $stmt_student = $conn->prepare("INSERT INTO students (user_id, last_name, course_id, year_level) VALUES (?, ?, ?, '1st Year')");
        $stmt_student->bind_param("isi", $user_id, $username, $course_id); // Using username as a placeholder for last_name
        $stmt_student->execute();
        $stmt_student->close();

        $conn->commit();
        $_SESSION['register_success'] = "Registration successful! You can now log in.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['register_error'] = "Registration failed. Please try again. Error: " . $e->getMessage();
    }

    header("Location: ../index.php");
    exit();
}
?>