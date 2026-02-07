<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'instructor') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    if (in_array($_FILES['profile_picture']['type'], $allowed_types) && $_FILES['profile_picture']['size'] <= $max_size) {
        
        $upload_dir = '../../assets/images/profile_pictures/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = 'instructor_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // --- Delete old profile picture ---
        $stmt_old = $conn->prepare("SELECT profile_picture_path FROM instructors WHERE user_id = ?");
        $stmt_old->bind_param("i", $user_id);
        $stmt_old->execute();
        if ($row_old = $stmt_old->get_result()->fetch_assoc()) {
            if (!empty($row_old['profile_picture_path']) && file_exists('../../' . $row_old['profile_picture_path'])) {
                unlink('../../' . $row_old['profile_picture_path']);
            }
        }
        $stmt_old->close();

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            $db_path = 'assets/images/profile_pictures/' . $new_filename;

            $sql = "UPDATE instructors SET profile_picture_path = ? WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $db_path, $user_id);
            if ($stmt->execute()) {
                $response['status'] = 'success';
                $response['message'] = 'Profile picture updated successfully!';
                $response['filepath'] = $db_path;
            } else {
                $response['message'] = 'Database update failed.';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Failed to move uploaded file.';
        }
    } else {
        $response['message'] = 'Invalid file type or size is too large (Max 5MB).';
    }
} else {
    $response['message'] = 'No file uploaded or an upload error occurred.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();