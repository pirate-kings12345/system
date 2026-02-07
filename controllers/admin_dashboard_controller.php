<?php
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/db_connect.php';

// Set user details for the view
$user_display_name = $_SESSION['username'] ?? 'Admin'; // Fallback to 'Admin'

// Fetch Students
$students = [];
$studentSql = "SELECT user_id, username, email, status FROM users WHERE role = 'student'";
$studentResult = $conn->query($studentSql);
if ($studentResult && $studentResult->num_rows > 0) {
    while($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch Instructors
$instructors = [];
$instructorSql = "SELECT user_id, username, email, status FROM users WHERE role = 'instructor'";
$instructorResult = $conn->query($instructorSql);
if ($instructorResult && $instructorResult->num_rows > 0) {
    while($row = $instructorResult->fetch_assoc()) {
        $instructors[] = $row;
    }
}

// Fetch Subjects
$subjects = [];
$subjectSql = "SELECT s.subject_id, s.subject_code, s.subject_name, s.units, s.semester, s.status, s.course_id, c.course_code AS course
               FROM subjects s 
               LEFT JOIN courses c ON s.course_id = c.course_id 
               ORDER BY c.course_code, s.semester, s.subject_code";
$subjectResult = $conn->query($subjectSql);
if ($subjectResult && $subjectResult->num_rows > 0) {
    while($row = $subjectResult->fetch_assoc()) {
        $subjects[] = $row;
    }
}

// Fetch Courses for dropdowns
$courses = [];
$coursesSql = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
$coursesResult = $conn->query($coursesSql);
if ($coursesResult && $coursesResult->num_rows > 0) {
    while($row = $coursesResult->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Fetch Instructors for dropdowns
$all_instructors = [];
$instructorsSql = "SELECT instructor_id, CONCAT(first_name, ' ', last_name) AS full_name FROM instructors ORDER BY last_name";
$instructorsResult = $conn->query($instructorsSql);
if ($instructorsResult && $instructorsResult->num_rows > 0) {
    while($row = $instructorsResult->fetch_assoc()) {
        $all_instructors[] = $row;
    }
}

// Fetch Rooms for dropdowns
$rooms = [];
$roomsSql = "SELECT room_id, room_name FROM rooms ORDER BY room_name";
$roomsResult = $conn->query($roomsSql);
if ($roomsResult && $roomsResult->num_rows > 0) {
    while($row = $roomsResult->fetch_assoc()) {
        $rooms[] = $row;
    }
}

// Fetch Schedules
$schedules = [];
$scheduleSql = "SELECT 
                    sch.schedule_id,
                    sch.subject_id,
                    sub.subject_code,
                    sub.subject_name,
                    ins.instructor_id,
                    CONCAT(ins.first_name, ' ', ins.last_name) AS instructor_name,
                    rm.room_id,
                    sch.day,
                    sch.time_start,
                    sch.time_end,
                    TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start_formatted,
                    TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end_formatted,
                    rm.room_name,
                    sch.section_id,
                    sch.school_year,
                    sch.semester
                FROM 
                    schedules AS sch
                LEFT JOIN subjects AS sub ON sch.subject_id = sub.subject_id
                LEFT JOIN instructors AS ins ON sch.instructor_id = ins.instructor_id
                LEFT JOIN rooms AS rm ON sch.room_id = rm.room_id
                ORDER BY sch.school_year DESC, sch.semester, sub.subject_code";
$scheduleResult = $conn->query($scheduleSql);
if ($scheduleResult && $scheduleResult->num_rows > 0) {
    while($row = $scheduleResult->fetch_assoc()) {
        $schedules[] = $row;
    }
}

$conn->close();