<?php
header('Content-Type: application/json');
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $input['user_id'] ?? null;
    $newStatus = $input['status'] ?? null;

    if ($userId && in_array($newStatus, ['active', 'deactivated'])) {
        // Prevent superadmin from deactivating their own account via this method
        $userCheck = $conn->query("SELECT username FROM users WHERE user_id = $userId");
        $user = $userCheck->fetch_assoc();
        if ($user && $user['username'] === 'superadmin') {
             $response = ['success' => false, 'message' => 'Cannot change the status of the superadmin account.'];
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ? AND role = 'admin'");
            $stmt->bind_param("si", $newStatus, $userId);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Admin status updated successfully.'];
            } else {
                $response['message'] = 'Database update failed.';
            }
            $stmt->close();
        }
    }
}

$conn->close();
echo json_encode($response);