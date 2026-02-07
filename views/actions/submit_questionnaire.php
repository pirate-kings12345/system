<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../student/dashboard.php");
    exit();
}

$user_id = $_POST['user_id'] ?? 0;
$responses = $_POST['responses'] ?? [];

if (empty($user_id) || empty($responses)) {
    header("Location: ../student/dashboard.php?q_error=empty");
    exit();
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO questionnaire_responses (user_id, question_id, response_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE response_value = VALUES(response_value)";
    $stmt = $conn->prepare($sql);

    foreach ($responses as $question_id => $response_value) {
        $stmt->bind_param("iii", $user_id, $question_id, $response_value);
        $stmt->execute();
    }

    $conn->commit();
    header("Location: ../student/dashboard.php?q_success=1#forms");
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    header("Location: ../student/dashboard.php?q_error=dberror");
} finally {
    $stmt->close();
    $conn->close();
}