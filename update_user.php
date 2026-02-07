<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

header('Content-Type: application/json');

// --- Security Check: Ensure user is a logged-in admin ---
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}
// --- End Security Check ---

$admin_user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id_to_update = intval($_POST['user_id']);
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    // --- Build the SQL query dynamically ---
    $sql_parts = [];
    $params = [];
    $types = "";

    // Always update username and email
    $sql_parts[] = "username = ?";
    $params[] = $username;
    $types .= "s";

    $sql_parts[] = "email = ?";
    $params[] = $email;
    $types .= "s";

    // Only update password if a new one was entered
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $sql_parts[] = "password = ?";
        $params[] = $hashed_password;
        $types .= "s";
    }

    // Add the user_id for the WHERE clause
    $params[] = $user_id_to_update;
    $types .= "i";

    $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE user_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // Log the action
        $log_action = "Updated details for user ID: " . $user_id_to_update;
        $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $log_stmt->bind_param("is", $admin_user_id, $log_action);
        $log_stmt->execute();
        $log_stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'User updated successfully.', 'data' => ['username' => $username, 'email' => $email]]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();
    exit();
}