<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// This check is for API endpoints. It returns a JSON error instead of redirecting.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode([
        'status' => 'error',
        'message' => 'Authentication required. Please log in again.'
    ]);
    exit();
}
?>