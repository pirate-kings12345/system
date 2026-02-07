<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $section_name = trim($_POST['section_name']);
    $course_id = $_POST['course_id'];
    $year_level = $_POST['year_level'];
    $semester = $_POST['semester']; // This was missing

    if (empty($section_name) || empty($course_id) || empty($year_level) || empty($semester)) {
        header("Location: ../admin/dashboard.php?error=emptyfields#room-setup");
        exit;
    }

    // Assuming your sections table has these columns
    // Add the semester to the INSERT query
    $stmt = $conn->prepare("INSERT INTO sections (section_name, course_id, year_level, semester) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("siss", $section_name, $course_id, $year_level, $semester);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=sectionadded#room-setup");
    } else {
        header("Location: ../admin/dashboard.php?error=dberror#room-setup");
    }
    $stmt->close();
    $conn->close();
    exit;
}