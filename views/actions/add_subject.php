<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Basic validation
    if (empty($_POST['subject_code']) || empty($_POST['subject_name']) || empty($_POST['course_id']) || empty($_POST['units']) || empty($_POST['semester'])) {
        header("Location: ../admin/dashboard.php?error=emptyfields");
        exit();
    }

    $subject_code = $_POST['subject_code'];
    $subject_name = $_POST['subject_name'];
    $course_id = $_POST['course_id'];
    $units = $_POST['units'];
    $semester = $_POST['semester'];

    // 1. Check for duplicate subject_code before inserting
    $checkSql = "SELECT subject_id FROM subjects WHERE subject_code = ?";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt === false) {
        // Handle prepare error
        header("Location: ../admin/dashboard.php?error=dberror");
        exit();
    }

    $checkStmt->bind_param("s", $subject_code);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows > 0) {
        // Duplicate found, redirect with a specific error
        header("Location: ../admin/dashboard.php?error=duplicate_subject");
        exit();
    }
    $checkStmt->close();

    // 2. No duplicate found, proceed with insertion
    $sql = "INSERT INTO subjects (subject_code, subject_name, course_id, units, semester) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        header("Location: ../admin/dashboard.php?error=dberror");
        exit();
    }

    $stmt->bind_param("ssiis", $subject_code, $subject_name, $course_id, $units, $semester);

    if ($stmt->execute()) {
        header("Location: ../admin/dashboard.php?success=subjectadded&tab=manage-subjects");
    } else {
        header("Location: ../admin/dashboard.php?error=dberror");
    }

    $stmt->close();
    $conn->close();
}
?>