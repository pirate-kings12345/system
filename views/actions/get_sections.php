<?php
header('Content-Type: application/json');
require_once '../../config/db_connect.php';

$response = ['status' => 'error', 'message' => 'Invalid request', 'sections' => []];

if (isset($_GET['course_id']) && isset($_GET['year_level'])) {
    $course_id = $_GET['course_id'];
    $year_level = $_GET['year_level'];

    // Prepare a query to prevent SQL injection
    $sql = "SELECT section_id, section_name 
            FROM sections 
            WHERE course_id = ? 
              AND year_level = ?
            ORDER BY section_name";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $course_id, $year_level);
    $stmt->execute();
    $result = $stmt->get_result();

    $response['status'] = 'success';
    $response['sections'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>