<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Only superadmin and admin can perform this action
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Unauthorized access.'];
    header("Location: ../../auth/login.php");
    exit();
}

// Check if 'module_registration' is active
$is_registration_module_active = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_registration'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    if ($setting['setting_value'] == '1') {
        $is_registration_module_active = true;
    }
}

if (!$is_registration_module_active) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Cannot add new instructors. The "Public Registration" module is currently disabled.'];
    header("Location: ../admin/manage_instructors.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Placeholder for actual instructor creation logic ---
    // In a real application, you would get username, email, password, etc. from POST
    // and insert into 'users' and 'instructors' tables.
    
    // For demonstration, let's just simulate success/failure
    $success = true; // Assume success for now

    if ($success) {
        $_SESSION['alert'] = ['type' => 'success', 'message' => 'Instructor account created successfully.'];
    } else {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Failed to create instructor account.'];
    }
    header("Location: ../admin/manage_instructors.php");
    exit();
} else {
    header("Location: ../admin/manage_instructors.php");
    exit();
}