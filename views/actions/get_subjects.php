<?php
// DEBUG MODE FOR JSON APIS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start(); // Capture all output

header('Content-Type: application/json');

require_once '../../config/db_connect.php';

$response = ['status' => 'error', 'message' => 'Invalid request', 'subjects' => []];

if (isset($_GET['section_id'])) {
    $section_id = $_GET['section_id'];

    // Prepare a query to get all subjects for a given course, year, and semester
    $sql = "SELECT s.subject_id, s.subject_code, s.subject_name, s.units
            FROM schedules sch
            JOIN subjects s ON sch.subject_id = s.subject_id
            WHERE sch.section_id = ?
            GROUP BY s.subject_id
            ORDER BY s.subject_code";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $response['message'] = 'DB prepare failed: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $response['status'] = 'success';
    $response['subjects'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();

// --- Final JSON Output with Debugging ---
$debug_output = ob_get_clean();
if (!empty($debug_output)) {
    // If there was any unexpected output (errors, warnings, html), return it as a debug message
    echo json_encode([
        'status' => 'error',
        'message' => 'PHP produced unexpected output which breaks JSON parsing.',
        'debug_output' => $debug_output,
        'sent_params' => $_GET // Also show what was sent to the script
    ]);
    exit;
}

echo json_encode($response);
exit;
?>