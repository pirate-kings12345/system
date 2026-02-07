<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Allow both superadmin and admin to perform this action
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'You are not authorized to perform this action.'];
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? '../views/superadmin/dashboard.php'));
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $target_role = $_POST['target_role'];

    if (empty($title) || empty($content)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Title and content cannot be empty.'];
    } else {
        $sql = "INSERT INTO announcements (title, content, target_role, date_posted) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $title, $content, $target_role);

        if ($stmt->execute()) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Announcement posted successfully.'];
        } else {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Failed to post announcement.'];
        }
        $stmt->close();
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement_id = $_POST['announcement_id'];

    if (empty($announcement_id)) {
        $_SESSION['alert'] = ['type' => 'error', 'message' => 'Invalid announcement ID.'];
    } else {
        $sql = "DELETE FROM announcements WHERE announcement_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $announcement_id);

        if ($stmt->execute()) {
            $_SESSION['alert'] = ['type' => 'success', 'message' => 'Announcement deleted successfully.'];
        } else {
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Failed to delete announcement.'];
        }
        $stmt->close();
    }
}

$conn->close();

// Redirect back to the management page
header("Location: manage_announcements.php");
exit();