<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Ensure that the request is an AJAX request
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    die("This script must be accessed via AJAX.");
}

// Ensure that the user is an instructor
if ($_SESSION['role'] !== 'instructor') {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Validate and sanitize the schedule_id
$schedule_id = isset($_GET['schedule_id']) ? filter_var($_GET['schedule_id'], FILTER_VALIDATE_INT) : null;

if ($schedule_id === null || $schedule_id === false) {
    header("HTTP/1.1 400 Bad Request");
    echo json_encode(['success' => false, 'message' => 'Invalid schedule ID.']);
    exit();
}

// --- Create semester variants for a more robust query ---
$semester_short = '1st';
$semester_long = '1st Semester';

// Fetch schedule details to determine the correct semester
$schedule_details_stmt = $conn->prepare("SELECT semester FROM schedules WHERE schedule_id = ?");
$schedule_details_stmt->bind_param("i", $schedule_id);
$schedule_details_stmt->execute();
if ($schedule_details_row = $schedule_details_stmt->get_result()->fetch_assoc()) {
    if (stripos($schedule_details_row['semester'], '2') !== false) {
        $semester_short = '2nd';
        $semester_long = '2nd Semester';
    }
}
$schedule_details_stmt->close();

// Fetch students enrolled in the selected schedule
$students = [];
$sql = "SELECT
            e.enrollment_id,
            s.student_id,
            COALESCE(
                NULLIF(TRIM(sp.full_name), ''),
                NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name)), ''),
                u.username
            ) AS full_name,
            MAX(sg.prelim) as prelim,
            MAX(sg.midterm) as midterm,
            MAX(sg.finals) as finals,
            MAX(sg.final_grade) as final_grade,
            MAX(sg.remarks) as remarks,
            target_schedule.subject_id
        FROM schedules AS target_schedule -- The class the instructor selected
        -- Find all approved enrollments for that class's section and term
        JOIN enrollments e ON e.section_id = target_schedule.section_id 
                          AND e.school_year = target_schedule.school_year 
                          AND (e.semester = ? OR e.semester = ?)
                          AND e.status = 'approved'
        -- Get the student details for each enrollment
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN student_profiles sp ON s.user_id = sp.user_id
        -- Left join to get any existing grades for this student and subject
        LEFT JOIN student_grades sg ON e.enrollment_id = sg.enrollment_id AND sg.subject_id = target_schedule.subject_id
        WHERE target_schedule.schedule_id = ?
        GROUP BY e.enrollment_id, s.student_id, full_name, target_schedule.subject_id
        ORDER BY full_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $semester_short, $semester_long, $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
    $conn->close();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'students' => $students]);
    exit();
} else {
    $stmt->close();
    $conn->close();

    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(['success' => false, 'message' => 'Failed to fetch students: ' . $conn->error]);
    exit();
}
?>