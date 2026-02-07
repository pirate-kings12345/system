<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

if ($_SESSION['role'] !== 'instructor' || !isset($_GET['schedule_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized or missing parameters.']);
    exit();
}

$schedule_id = filter_var($_GET['schedule_id'], FILTER_VALIDATE_INT);

if (!$schedule_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Schedule ID.']);
    exit();
}

$students = [];
$sql = "SELECT 
            s.student_number,
            COALESCE(
                NULLIF(TRIM(sp.full_name), ''),
                NULLIF(TRIM(CONCAT(s.last_name, ', ', s.first_name)), ''),
                u.username
            ) AS full_name
        FROM schedules AS target_schedule
        JOIN enrollment_subjects es ON es.subject_id = target_schedule.subject_id
        JOIN enrollments e ON es.enrollment_id = e.enrollment_id AND e.section_id = target_schedule.section_id
        JOIN students s ON e.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN student_profiles sp ON s.user_id = sp.user_id
        WHERE target_schedule.schedule_id = ? AND e.status = 'approved'
        GROUP BY s.student_id
        ORDER BY full_name ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'students' => $students]);