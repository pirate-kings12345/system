<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_name = trim($_POST['room_name']);
    $department_id = $_POST['department_id'];

    if (empty($room_name) || empty($department_id)) {
        header("Location: ../admin/dashboard.php?error=emptyfields#room-setup");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO rooms (room_name, department_id) VALUES (?, ?)");
    $stmt->bind_param("si", $room_name, $department_id);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=roomadded#room-setup");
    } else {
        header("Location: ../admin/dashboard.php?error=dberror#room-setup");
    }
    $stmt->close();
    $conn->close();
    exit;
}