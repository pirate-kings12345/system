<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../admin/dashboard.php?error=missingid");
    exit();
}

$enrollment_id = $_GET['id'];

// Start a transaction
$conn->begin_transaction();

try {
    // 1. Get student_id and section_id from the enrollment request
    $stmt1 = $conn->prepare("SELECT student_id, section_id FROM enrollments WHERE enrollment_id = ?");
    $stmt1->bind_param("i", $enrollment_id);
    $stmt1->execute();
    $result = $stmt1->get_result()->fetch_assoc();
    $student_id = $result['student_id'];
    $section_id = $result['section_id'];
    $stmt1->close();

    if (!$student_id || !$section_id) {
        throw new Exception("Student or Section not found for this enrollment.");
    }

    // 2. Update the student's official section in the `students` table
    // This is the crucial step to make the schedule appear.
    $stmt_update_student = $conn->prepare("UPDATE students SET section_id = ? WHERE student_id = ?");
    $stmt_update_student->bind_param("ii", $section_id, $student_id);
    $stmt_update_student->execute();
    $stmt_update_student->close();

    // --- Generate Student Number if it doesn't exist ---
    $stmt_check = $conn->prepare("SELECT student_number FROM students WHERE student_id = ?");
    $stmt_check->bind_param("i", $student_id);
    $stmt_check->execute();
    $current_student_number = $stmt_check->get_result()->fetch_assoc()['student_number'];
    $stmt_check->close();

    if (empty($current_student_number)) {
        $year = date('y'); // e.g., "24"
        $month = date('m'); // e.g., "07"

        // Find the last sequence number for the current year and month
        $sequence_sql = "SELECT COUNT(student_id) as count FROM students WHERE student_number LIKE ?";
        $stmt_seq = $conn->prepare($sequence_sql);
        $like_pattern = $year . "-" . $month . "-%";
        $stmt_seq->bind_param("s", $like_pattern);
        $stmt_seq->execute();
        $sequence_count = $stmt_seq->get_result()->fetch_assoc()['count'];
        $stmt_seq->close();

        $new_sequence = $sequence_count + 1;
        $new_student_number = sprintf("%s-%s-%04d", $year, $month, $new_sequence);

        $stmt_update_num = $conn->prepare("UPDATE students SET student_number = ? WHERE student_id = ?");
        $stmt_update_num->bind_param("si", $new_student_number, $student_id);
        $stmt_update_num->execute();
        $stmt_update_num->close();
    }

    // 3. Update the enrollment request status to 'approved'
    $stmt_approve = $conn->prepare("UPDATE enrollments SET status = 'approved' WHERE enrollment_id = ?");
    $stmt_approve->bind_param("i", $enrollment_id);
    $stmt_approve->execute();
    $stmt_approve->close();    

    // If all queries succeed, commit the transaction
    $conn->commit();
    header("Location: ../admin/dashboard.php?tab=pending-enrollments&success=approved");

} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    header("Location: ../admin/dashboard.php?tab=pending-enrollments&error=db_error");
}

$conn->close();
exit();
?>