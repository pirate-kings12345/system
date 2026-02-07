<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

// Only allow Admins to execute this script
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve form data
    $department_name = trim($_POST['department_name']);

    // --- Validation (basic) ---
    if (empty($department_name)) {
        header("Location: ../admin/dashboard.php?error=emptyfields_dept#manage-departments");
        exit;
    }

    try {
        // Check if department already exists
        $stmt_check = $conn->prepare("SELECT department_id FROM departments WHERE department_name = ?");
        $stmt_check->bind_param("s", $department_name);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            header("Location: ../admin/dashboard.php?error=dept_exists#manage-departments");
            exit;
        }
        $stmt_check->close();

        // Insert into 'departments' table
        $stmt = $conn->prepare("INSERT INTO departments (department_name) VALUES (?)");
        $stmt->bind_param("s", $department_name);
        $stmt->execute();
        $stmt->close();

        header("Location: ../admin/dashboard.php?success=dept_added#manage-departments");
    } catch (mysqli_sql_exception $exception) {
        // You might want to log the error: error_log($exception->getMessage());
        header("Location: ../admin/dashboard.php?error=dberror#manage-departments");
    } finally {
        $conn->close();
        exit;
    }
} else {
    // Redirect if not a POST request
    header("Location: ../admin/dashboard.php");
    exit;
}