<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Security Check: Ensure only superadmin or an admin with the correct permission can perform this action.
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'superadmin' && !has_permission($_SESSION['role'], 'manage_system_modules', $conn))) {
    $_SESSION['modules_msg'] = "Unauthorized access.";
    $_SESSION['modules_msg_type'] = 'error';
    header("Location: dashboard.php#modules");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_modules = $_POST['modules'] ?? [];

    // Define the list of valid modules to prevent arbitrary setting updates
    $defined_modules = [
        'module_enrollment' => 'Student Enrollment',
        'module_grades' => 'Grade Management',
        'module_registration' => 'Public Registration'
    ];

    // Prepare the statement for inserting/updating settings
    $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

    // Loop through all defined modules
    foreach (array_keys($defined_modules) as $module_key) {
        // If the checkbox for this module was submitted, it's enabled (1), otherwise disabled (0)
        $is_enabled = isset($submitted_modules[$module_key]) ? '1' : '0';
        
        $stmt->bind_param("ss", $module_key, $is_enabled);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    $_SESSION['modules_msg'] = "System modules updated successfully!";
    $_SESSION['modules_msg_type'] = 'success';
} else {
    $_SESSION['modules_msg'] = "Invalid request method.";
    $_SESSION['modules_msg_type'] = 'error';
}

header("Location: dashboard.php#modules");
exit();