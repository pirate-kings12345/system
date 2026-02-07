<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Ensure the user is a student
if ($_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'] ?? 0;
    $appointment_date = $_POST['appointment_date'] ?? '';

    // Basic validation
    if (empty($enrollment_id) || empty($appointment_date)) {
        $_SESSION['error'] = "Invalid data provided.";
        header("Location: ../student/appointment.php");
        exit();
    }

    // Update the database
    $stmt = $conn->prepare("UPDATE enrollments SET appointment_date = ? WHERE enrollment_id = ?");
    $stmt->bind_param("si", $appointment_date, $enrollment_id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment set successfully for " . date('F j, Y', strtotime($appointment_date)) . "!";
    } else {
        $_SESSION['error'] = "Failed to set appointment. Please try again.";
    }
    $stmt->close();
}

header("Location: ../student/appointment.php");
exit();