<?php
session_start(); // Start the session to access session variables
require_once '../../config/db_connect.php';

header('Content-Type: application/json');
// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    $_SESSION['permission_msg'] = 'Unauthorized action.';
    $_SESSION['permission_msg_type'] = 'danger';
    header("Location: ../superadmin/manage_admin_permissions.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$posted_assignments = $_POST['assignments'] ?? [];

$conn->begin_transaction();

try {
    // First, clear all existing assignments to reset permissions
    $conn->query("DELETE FROM admin_course_assignments");

    // Prepare the statement for inserting new assignments
    $stmt = $conn->prepare("INSERT INTO admin_course_assignments (user_id, course_id) VALUES (?, ?)");

    // Loop through the submitted data and insert new assignments
    foreach ($posted_assignments as $admin_id => $course_ids) {
        if (is_array($course_ids)) {
            foreach ($course_ids as $course_id) {
                $stmt->bind_param("ii", $admin_id, $course_id);
                $stmt->execute();
            }
        }
    }

    $stmt->close();
    $conn->commit();
    $_SESSION['permission_msg'] = 'Admin permissions have been updated successfully!';
    $_SESSION['permission_msg_type'] = 'success';

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['permission_msg'] = 'Database error: ' . $e->getMessage();
    $_SESSION['permission_msg_type'] = 'danger';
}

$conn->close();
header("Location: ../superadmin/manage_admin_permissions.php");
?>