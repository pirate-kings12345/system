<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $_SESSION['role'] !== 'student') {
    header("Location: ../student/dashboard.php");
    exit();
}

// Start a transaction to ensure all database operations succeed or fail together.
$conn->begin_transaction();

try {
    $student_id = null;
    $is_new_student = false;

    // --- Step 1: Identify or Create the Student ---
    if (isset($_POST['user_id'])) { // This is a NEW student
        $is_new_student = true;
        $user_id = $_POST['user_id'];
        $course_id = $_POST['course_id'];
        $year_level = '1st Year'; // New students are always 1st year

        // Create a new record in the 'students' table
        $stmt_create_student = $conn->prepare("INSERT INTO students (user_id, course_id, year_level) VALUES (?, ?, ?)");
        $stmt_create_student->bind_param("iis", $user_id, $course_id, $year_level);
        $stmt_create_student->execute();
        $student_id = $conn->insert_id; // Get the newly created student_id
        $stmt_create_student->close();

    } elseif (isset($_POST['student_id'])) { // This is an EXISTING student
        $student_id = $_POST['student_id'];
    }

    if (!$student_id) {
        throw new Exception("Could not determine student ID.");
    }

    $school_year = $_POST['school_year'];
    $year_level = $_POST['year_level'];
    // The form sends '1st' or '2nd', but other parts of the system might expect '1st Semester' or '2nd Semester'.
    // Let's standardize it to the full text format for consistency with what get_active_semester() returns.
    $semester_short = $_POST['semester'];
    $semester = ($semester_short === '1st') ? '1st Semester' : '2nd Semester';

    $subjects = $_POST['subjects'] ?? [];

    if (empty($subjects)) {
        throw new Exception("No subjects were selected.");
    }

    // --- Step 2: Check for Duplicate Enrollment Request ---
    $check_sql = "SELECT enrollment_id FROM enrollments WHERE student_id = ? AND school_year = ? AND semester = ?";
    $stmt_check = $conn->prepare($check_sql);
    $stmt_check->bind_param("iss", $student_id, $school_year, $semester);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        throw new Exception("Duplicate enrollment request.");
    }
    $stmt_check->close();

    // --- Update student's year level ---
    // This should happen just before creating the new enrollment record.
    $stmt_update_student = $conn->prepare("UPDATE students SET year_level = ? WHERE student_id = ?");
    $stmt_update_student->bind_param("si", $year_level, $student_id);
    $stmt_update_student->execute();
    $stmt_update_student->close();

    // --- Step 3: Create the Main Enrollment Record ---
    // Add section_id to the enrollment record
    $sql_enroll = "INSERT INTO enrollments (student_id, section_id, school_year, semester, status) VALUES (?, ?, ?, ?, 'pending')";
    $stmt_enroll = $conn->prepare($sql_enroll);
    $stmt_enroll->bind_param("iiss", $student_id, $_POST['section_id'], $school_year, $semester);
    $stmt_enroll->execute();
    $enrollment_id = $conn->insert_id; // Get the new enrollment_id
    $stmt_enroll->close();

    // --- Step 4: Link the Selected Subjects to the Enrollment Record ---
    // This assumes you have a table named 'enrollment_subjects' with columns 'enrollment_id' and 'subject_id'
    $sql_subjects = "INSERT INTO enrollment_subjects (enrollment_id, subject_id) VALUES (?, ?)";
    $stmt_subjects = $conn->prepare($sql_subjects);

    foreach ($subjects as $subject_id) {
        $stmt_subjects->bind_param("ii", $enrollment_id, $subject_id);
        $stmt_subjects->execute();
    }
    $stmt_subjects->close();

    // --- All Done, Commit and Redirect ---
    $conn->commit();
    header("Location: ../student/enrollment_form.php?success=true");

} catch (Exception $e) {
    $conn->rollback(); // Something went wrong, undo all changes.
    // You can log the error message for debugging: error_log($e->getMessage());
    header("Location: ../student/enrollment_form.php?error=true");
}

$conn->close();
exit();
?>