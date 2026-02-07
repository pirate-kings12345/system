<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation
    // --- Definitive Validation ---
    // Check that all required fields are submitted and that ID fields are valid.
    if (
        !isset($_POST['subject_id']) || !isset($_POST['instructor_id']) || !isset($_POST['section_id']) || !isset($_POST['room_id']) ||
        empty($_POST['day']) || empty($_POST['time_start']) || empty($_POST['time_end']) || empty($_POST['school_year']) || empty($_POST['semester'])
    ) {
        header("Location: ../admin/dashboard.php?error=emptyfields&tab=manage-schedules");
        exit();
    }

    $subject_id = $_POST['subject_id'];
    $instructor_id = $_POST['instructor_id'];
    $section_id = $_POST['section_id'];
    $room_id = $_POST['room_id'];
    $day = $_POST['day'];
    $time_start = $_POST['time_start'];
    $time_end = $_POST['time_end'];
    $school_year = $_POST['school_year'];
    $semester = $_POST['semester'];

    // Ensure IDs are greater than 0 after assignment. This is the final safeguard.
    if ((int)$subject_id <= 0 || (int)$instructor_id <= 0 || (int)$section_id <= 0 || (int)$room_id <= 0) {
        // One of the IDs is invalid (likely a dropdown wasn't selected)
        header("Location: ../admin/dashboard.php?error=emptyfields&tab=manage-schedules");
        exit();
    }

    // --- Conflict Detection ---
    $conflict_sql = "SELECT schedule_id FROM schedules 
                     WHERE day = ? AND school_year = ? AND semester = ?
                     AND (
                         (instructor_id = ? AND (? < time_end AND ? > time_start)) OR
                         (room_id = ? AND (? < time_end AND ? > time_start)) OR
                         (section_id = ? AND (? < time_end AND ? > time_start))
                     )";

    $conflict_stmt = $conn->prepare($conflict_sql);
    if ($conflict_stmt === false) {
        header("Location: ../admin/dashboard.php?error=dberror&tab=manage-schedules");
        exit();
    }

    $conflict_stmt->bind_param(
        "sssissississ",
        $day, $school_year, $semester,
        $instructor_id, $time_start, $time_end,
        $room_id, $time_start, $time_end,
        $section_id, $time_start, $time_end
    );

    $conflict_stmt->execute();
    $result = $conflict_stmt->get_result();

    if ($result->num_rows > 0) {
        // Conflict found
        header("Location: ../admin/dashboard.php?error=conflict&tab=manage-schedules");
        exit();
    }
    $conflict_stmt->close();

    // --- No Conflict, Proceed with Insertion ---
    $sql = "INSERT INTO schedules (subject_id, instructor_id, section_id, room_id, day, time_start, time_end, school_year, semester) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header("Location: ../admin/dashboard.php?error=dberror&tab=manage-schedules");
        exit();
    }

    $stmt->bind_param("iiiisssss", $subject_id, $instructor_id, $section_id, $room_id, $day, $time_start, $time_end, $school_year, $semester);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=scheduleadded&tab=manage-schedules");
    } else {
        header("Location: ../admin/dashboard.php?error=dberror&tab=manage-schedules");
    }

    $stmt->close();
    $conn->close();
}
?>