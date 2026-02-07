<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Only superadmin and admin can perform this action
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../auth/login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if registration module is active
    $is_registration_module_active = false;
    $settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_registration'");
    if ($settingsResult && $settingsResult->num_rows > 0) {
        $setting = $settingsResult->fetch_assoc();
        if ($setting['setting_value'] == '1') {
            $is_registration_module_active = true;
        }
    }

    if (!$is_registration_module_active) {
        header("Location: dashboard.php?error=module_disabled#manage-instructors");
        exit();
    }

    // Sanitize and retrieve form data
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $department_id = $_POST['department_id'];
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($first_name) || empty($last_name) || empty($department_id) || empty($email) || empty($username) || empty($password)) {
        header("Location: dashboard.php?error=emptyfields#manage-instructors");
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'instructor';
    $status = 'active';

    // Use a transaction to ensure both inserts succeed or fail together
    $conn->begin_transaction();

    try {
        // 1. Insert into 'users' table
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $hashed_password, $role, $status);
        $stmt->execute();
        $user_id = $stmt->insert_id;

        // 2. Insert into 'instructors' table
        $stmt = $conn->prepare("INSERT INTO instructors (user_id, first_name, last_name, department_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $first_name, $last_name, $department_id);
        $stmt->execute();

        // If both inserts are successful, commit the transaction
        $conn->commit();
        header("Location: dashboard.php?success=instructoradded#manage-instructors");
        exit();
    } catch (mysqli_sql_exception $e) {
        $conn->rollback(); // Rollback on error
        // Check for duplicate entry error
        if ($conn->errno == 1062) {
            header("Location: dashboard.php?error=duplicate_user&msg=" . urlencode('Username or email already exists.'));
        } else {
            header("Location: dashboard.php?error=dberror&msg=" . urlencode($e->getMessage()));
        }
        exit();
    }
}
?>