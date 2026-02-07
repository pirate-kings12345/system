<?php
require_once '../../includes/session_check.php';

// Security Check: Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    $_SESSION['permissions_msg'] = "Unauthorized access.";
    $_SESSION['permissions_msg_type'] = 'error';
    header("Location: dashboard.php#database-control");
    exit();
}

require_once '../../config/db_connect.php';

$tables_to_reset = [
    'enrollments',
    'enrollment_subjects',
    'schedules',
    'student_grades',
    'audit_logs',
    'subjects', // from previous request
    'sections', // from previous request
    'courses',
    'departments',
    'students',
    'rooms',
    'announcements'
];

$conn->begin_transaction();

try {
    // Disable foreign key checks to allow truncation
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Delete all users except superadmin
    $conn->query("DELETE FROM users WHERE username != 'superadmin'");

    foreach ($tables_to_reset as $table) {
        // Use DELETE instead of TRUNCATE to ensure it works within a transaction
        $conn->query("DELETE FROM `$table`");
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    $conn->commit();
    $_SESSION['permissions_msg'] = "System data has been reset successfully, preserving superadmin login and core settings.";
    $_SESSION['permissions_msg_type'] = 'success';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['permissions_msg'] = "An error occurred during reset: " . $e->getMessage();
    $_SESSION['permissions_msg_type'] = 'error';
}

$conn->close();
header("Location: dashboard.php#database-control");
exit();
?>