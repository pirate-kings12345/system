<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

header('Content-Type: application/json');

// Security checks
if ($_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$grades = $_POST['grades'] ?? null;
$subject_id = $_POST['subject_id'] ?? null;

if (empty($grades) || empty($subject_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing grade data or subject ID.']);
    exit();
}

$conn->begin_transaction();

// Helper function to determine remarks based on GWA
function get_remarks_from_gwa($gwa) {
    if ($gwa === null) return 'Incomplete';
    if ($gwa > 0 && $gwa <= 3.00) return 'Passed';
    return 'Failed';
}

try {
    foreach ($grades as $enrollment_id => $grade_values) {
        $prelim = isset($grade_values['prelim']) && $grade_values['prelim'] !== '' ? (float)$grade_values['prelim'] : null;
        $midterm = !empty($grade_values['midterm']) ? (float)$grade_values['midterm'] : null;
        $finals = !empty($grade_values['finals']) ? (float)$grade_values['finals'] : null;
        $final_gwa = null;
        $remarks = 'Incomplete';

        // Calculate Final Grade only if all three components are available
        if ($prelim !== null && $midterm !== null && $finals !== null) {
            // Directly average the GWA-like inputs, as per the user's example.
            $final_gwa = round(($prelim + $midterm + $finals) / 3, 2);
            $remarks = get_remarks_from_gwa($final_gwa);

            // If the calculated GWA is failing (> 3.00), the final recorded GWA is 5.00.
            // Otherwise, it's the calculated average.
            $final_gwa = ($final_gwa > 3.00) ? 5.00 : $final_gwa;
        } else {
            $remarks = 'Incomplete'; // If any grade is missing, it's incomplete
        }

        // Check if a grade record already exists for this student and subject
        $check_stmt = $conn->prepare("SELECT grade_id FROM student_grades WHERE enrollment_id = ? AND subject_id = ?");
        $check_stmt->bind_param("ii", $enrollment_id, $subject_id);
        $check_stmt->execute();
        $existing_grade = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing_grade) {
            // Record exists, so UPDATE it
            $stmt = $conn->prepare(
                "UPDATE student_grades SET prelim = ?, midterm = ?, finals = ?, final_grade = ?, remarks = ? WHERE grade_id = ?"
            );
            $stmt->bind_param("ddddsi", $prelim, $midterm, $finals, $final_gwa, $remarks, $existing_grade['grade_id']);
        } else {
            // No record exists, so INSERT a new one
            $stmt = $conn->prepare(
                "INSERT INTO student_grades (enrollment_id, subject_id, prelim, midterm, finals, final_grade, remarks)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("iidddds", $enrollment_id, $subject_id, $prelim, $midterm, $finals, $final_gwa, $remarks);
        }

        $stmt->execute();
        $stmt->close(); // Close statement inside the loop
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Grades have been saved successfully!']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>