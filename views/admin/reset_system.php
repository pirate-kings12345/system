<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// CRITICAL: This action is for superadmin only.
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../auth/login.php?error=unauthorized");
    exit();
}

// Double-check to ensure we don't delete the superadmin.
// It's safer to use a specific username than a role, as there might be multiple admins.
$superadmin_username = 'superadmin';

// The order of truncation is important to avoid foreign key constraint errors.
// Start with tables that are referenced by others, and move towards the tables they reference.
$tables_to_truncate = [
    'announcements',
    'audit_logs',
    'enrollments',
    'enrollment_subjects',
    'grades',
    'student_grades',
    'schedules',
    'subjects',
    'sections',
    'rooms',
    'courses',
    'departments',
    'instructors',
    'students',
    'student_profiles',
    // `users` table is handled separately below.
    // `system_settings` and `role_permissions` are preserved.
];

$conn->begin_transaction();

try {
    // Temporarily disable foreign key checks to allow truncation in any order
    $conn->query("SET FOREIGN_KEY_CHECKS=0;");

    // Truncate all specified tables
    foreach ($tables_to_truncate as $table) {
        $conn->query("TRUNCATE TABLE `$table`;");
    }

    // Delete all users EXCEPT the superadmin
    $stmt = $conn->prepare("DELETE FROM `users` WHERE `username` != ?");
    $stmt->bind_param("s", $superadmin_username);
    $stmt->execute();

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1;");

    // Commit the transaction
    $conn->commit();

    header("Location: ../admin/dashboard.php?success=system_reset#system-settings");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../admin/dashboard.php?error=reset_failed&msg=" . urlencode($e->getMessage()));
    exit();
}
?>