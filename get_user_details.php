<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

header('Content-Type: application/json');

// --- Security Check: Ensure user is a logged-in superadmin ---
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Access Denied.']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID not provided.']);
    exit();
}

$user_id_to_fetch = intval($_GET['id']);

$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id_to_fetch);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    echo json_encode(['success' => true, 'data' => $user]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found.']);
}

$stmt->close();
$conn->close();