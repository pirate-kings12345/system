<?php
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
echo json_encode($response);
?>