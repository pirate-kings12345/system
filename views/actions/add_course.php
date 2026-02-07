<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

// Only allow Admins to execute this script
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $course_code = trim($_POST['course_code']);
    $course_name = trim($_POST['course_name']);
    $department_id = $_POST['department_id'];

    // Basic validation
    if (empty($course_code) || empty($course_name) || empty($department_id)) {
        header("Location: ../admin/dashboard.php?error=emptyfields#manage-courses");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, department_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $course_code, $course_name, $department_id);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=courseadded#manage-courses");
    } else {
        header("Location: ../admin/dashboard.php?error=dberror#manage-courses");
    }

    $stmt->close();
    $conn->close();
    exit;
}