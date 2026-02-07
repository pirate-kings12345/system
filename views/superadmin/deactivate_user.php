<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

// Set header to return JSON
header('Content-Type: application/json');

// --- Security Check: Ensure user is a logged-in admin ---
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied. You must be an admin to perform this action.']);
    exit();
}
// --- End Security Check ---

$admin_user_id = $_SESSION['user_id'];

if (isset($_GET['id']) && isset($_GET['action'])) {
    $user_id_to_change = intval($_GET['id']);
    $action = $_GET['action'];

    // Prevent an admin from deactivating their own account
    if ($user_id_to_change === $admin_user_id) {
        echo json_encode(['success' => false, 'message' => 'Error: You cannot change the status of your own account.']);
        exit();
    }

    // Determine the new status based on the action
    $new_status = ($action === 'deactivate') ? 'inactive' : 'active';

    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_status, $user_id_to_change);

    if ($stmt->execute()) {
        // Log the action to the audit trail
        $log_action = ucfirst($new_status) . "d user with ID: " . $user_id_to_change;
        $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_user_id, $log_action);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode(['success' => true, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $conn->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit();
}