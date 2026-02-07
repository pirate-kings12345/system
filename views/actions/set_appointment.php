<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

// Ensure the user is a student
if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enrollment_id = $_POST['enrollment_id'] ?? 0;
    $appointment_date = $_POST['appointment_date'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Basic validation
    if (empty($enrollment_id) || empty($appointment_date)) {
        $_SESSION['error'] = "Invalid data provided.";
        header("Location: ../student/appointment.php");
        exit();
    }
    
    // Security Check: Verify that the enrollment_id belongs to the logged-in student
    $verify_stmt = $conn->prepare("SELECT e.enrollment_id FROM enrollments e JOIN students s ON e.student_id = s.student_id WHERE e.enrollment_id = ? AND s.user_id = ?");
    $verify_stmt->bind_param("ii", $enrollment_id, $user_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 1) {
        // Update the database
        $stmt = $conn->prepare("UPDATE enrollments SET appointment_date = ? WHERE enrollment_id = ?");
        $stmt->bind_param("si", $appointment_date, $enrollment_id);
    
        if ($stmt->execute()) {
            $_SESSION['success'] = "Appointment set successfully for " . date('F j, Y', strtotime($appointment_date)) . "!";
        } else {
            $_SESSION['error'] = "Failed to set appointment. Please try again.";
        }
        $stmt->close();
    } else {
        // This is a security failure, the user is trying to modify an enrollment that isn't theirs.
        $_SESSION['error'] = "Unauthorized action detected.";
    }
    $verify_stmt->close();
}

header("Location: ../student/appointment.php");
exit();