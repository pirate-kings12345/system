<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

header('Content-Type: application/json');

// Security check: only admins can access this
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$year_level = isset($_GET['year_level']) ? $_GET['year_level'] : null;
$semester = isset($_GET['semester']) ? $_GET['semester'] : null;

if ($course_id === 0) {
    echo json_encode(['subjects' => [], 'sections' => [], 'rooms' => [], 'instructors' => []]);
    exit();
}

$response = [
    'subjects' => [],
    'sections' => [],
    'rooms' => [],
    'instructors' => []
];

// --- Fetch Subjects ---
$subjectSql = "SELECT subject_id, subject_code, subject_name FROM subjects WHERE (course_id = ? OR course_id IS NULL)";
$subjectParams = [$course_id];
$subjectTypes = "i";
if ($year_level) { $subjectSql .= " AND year_level = ?"; $subjectParams[] = $year_level; $subjectTypes .= "s"; }
if ($semester) { $subjectSql .= " AND semester = ?"; $subjectParams[] = $semester; $subjectTypes .= "s"; }
$subjectSql .= " ORDER BY subject_code";

$stmt = $conn->prepare($subjectSql);
$stmt->bind_param($subjectTypes, ...$subjectParams);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response['subjects'][] = $row;
}
$stmt->close();

// --- Fetch Sections ---
$sectionSql = "SELECT section_id, section_name FROM sections WHERE course_id = ?";
$sectionParams = [$course_id];
$sectionTypes = "i";
if ($year_level) { $sectionSql .= " AND year_level = ?"; $sectionParams[] = $year_level; $sectionTypes .= "s"; }
if ($semester) { $sectionSql .= " AND semester = ?"; $sectionParams[] = $semester; $sectionTypes .= "s"; }
$sectionSql .= " ORDER BY section_name";

$stmt = $conn->prepare($sectionSql);
$stmt->bind_param($sectionTypes, ...$sectionParams);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $response['sections'][] = $row;
}
$stmt->close();

// --- Fetch Rooms and Instructors based on the Course's Department ---
$deptStmt = $conn->prepare("SELECT department_id FROM courses WHERE course_id = ?");
$deptStmt->bind_param("i", $course_id);
$deptStmt->execute();
$deptResult = $deptStmt->get_result();

if ($deptRow = $deptResult->fetch_assoc()) {
    $department_id = $deptRow['department_id'];

    // Fetch Rooms
    $roomStmt = $conn->prepare("SELECT room_id, room_name FROM rooms WHERE department_id = ? ORDER BY room_name");
    $roomStmt->bind_param("i", $department_id);
    $roomStmt->execute();
    $result = $roomStmt->get_result();
    while ($row = $result->fetch_assoc()) { $response['rooms'][] = $row; }
    $roomStmt->close();

    // Fetch Instructors
    $instructorStmt = $conn->prepare("SELECT instructor_id, CONCAT(first_name, ' ', last_name) as full_name FROM instructors WHERE department_id = ? ORDER BY last_name, first_name");
    $instructorStmt->bind_param("i", $department_id);
    $instructorStmt->execute();
    $result = $instructorStmt->get_result();
    while ($row = $result->fetch_assoc()) { $response['instructors'][] = $row; }
    $instructorStmt->close();
}
$deptStmt->close();

echo json_encode($response);
$conn->close();