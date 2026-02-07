<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Security Check: Ensure only users with admin role can perform this action
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error_msg'] = "You are not authorized to perform this action.";
    header("Location: ../admin/dashboard.php#manage-schedules");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve POST data
    $schedule_id = filter_input(INPUT_POST, 'schedule_id', FILTER_VALIDATE_INT);
    $subject_id = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $instructor_id = filter_input(INPUT_POST, 'instructor_id', FILTER_VALIDATE_INT);
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);
    $day = filter_input(INPUT_POST, 'day', FILTER_SANITIZE_STRING);
    $time_start = filter_input(INPUT_POST, 'time_start', FILTER_SANITIZE_STRING);
    $time_end = filter_input(INPUT_POST, 'time_end', FILTER_SANITIZE_STRING);
    $school_year = trim(filter_input(INPUT_POST, 'school_year', FILTER_SANITIZE_STRING));
    $semester = filter_input(INPUT_POST, 'semester', FILTER_SANITIZE_STRING);
    $section_id = filter_input(INPUT_POST, 'section_id', FILTER_VALIDATE_INT);

    // Basic validation
    if (!$schedule_id || !$subject_id || !$instructor_id || !$room_id || empty($day) || empty($time_start) || empty($time_end) || empty($school_year) || empty($semester)) {
        $_SESSION['error_msg'] = "All fields are required.";
        header("Location: ../admin/dashboard.php#manage-schedules");
        exit();
    }

    // Prepare the UPDATE statement
    $stmt = $conn->prepare(
        "UPDATE schedules SET 
            subject_id = ?, instructor_id = ?, room_id = ?, day = ?, 
            time_start = ?, time_end = ?, school_year = ?, semester = ?, section_id = ?
        WHERE schedule_id = ?"
    );

    $stmt->bind_param("iiisssssii", $subject_id, $instructor_id, $room_id, $day, $time_start, $time_end, $school_year, $semester, $section_id, $schedule_id);

    if ($stmt->execute()) {
        $_SESSION['success_msg'] = "Schedule updated successfully.";
    } else {
        $_SESSION['error_msg'] = "Database update failed: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
header("Location: ../admin/dashboard.php#manage-schedules");
exit();