<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $max_size = 5 * 1024 * 1024; // 5 MB

    if (in_array($_FILES['profile_picture']['type'], $allowed_types) && $_FILES['profile_picture']['size'] <= $max_size) {
        
        // Define upload directory
        $upload_dir = '../../assets/images/profile_pictures/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Create a unique filename
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = 'user_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // --- Delete old profile picture if it exists ---
        $stmt_old = $conn->prepare("SELECT profile_picture_path FROM student_profiles WHERE user_id = ?");
        $stmt_old->bind_param("i", $user_id);
        $stmt_old->execute();
        $result_old = $stmt_old->get_result();
        if ($row_old = $result_old->fetch_assoc()) {
            if (!empty($row_old['profile_picture_path']) && file_exists('../../' . $row_old['profile_picture_path'])) {
                unlink('../../' . $row_old['profile_picture_path']);
            }
        }
        $stmt_old->close();

        // Move the new file
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            // The path to store in DB should be relative from the root of the project
            $db_path = 'assets/images/profile_pictures/' . $new_filename;

            // Update database
            // Use INSERT...ON DUPLICATE KEY UPDATE to handle cases where the profile row doesn't exist yet.
            $sql = "INSERT INTO student_profiles (user_id, profile_picture_path) VALUES (?, ?) ON DUPLICATE KEY UPDATE profile_picture_path = VALUES(profile_picture_path)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $user_id, $db_path);
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
        if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
            $response['message'] = 'Invalid file type. Please upload a JPG, PNG, or GIF.';
        } elseif ($_FILES['profile_picture']['size'] > $max_size) {
            $response['message'] = 'File is too large. Maximum size is 5 MB.';
        }
    }
} else {
    $response['message'] = 'No file uploaded or an upload error occurred.';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>