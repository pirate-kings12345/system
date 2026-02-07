<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../admin/dashboard.php?error=missingid");
    exit();
}

$enrollment_id = $_GET['id'];

$stmt = $conn->prepare("UPDATE enrollments SET status = 'rejected' WHERE enrollment_id = ?");
$stmt->bind_param("i", $enrollment_id);

if ($stmt->execute()) {
    header("Location: ../admin/dashboard.php?tab=pending-enrollments&success=rejected");
} else {
    header("Location: ../admin/dashboard.php?tab=pending-enrollments&error=db_error");
}

$stmt->close();
$conn->close();
exit();
?>