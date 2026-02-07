<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Security Check: Ensure only superadmin or an admin with the correct permission can perform this action.
require_once '../../includes/functions.php';
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'superadmin' && !has_permission($_SESSION['role'], 'manage_system_settings', $conn))) {
    $_SESSION['settings_msg'] = "Unauthorized access.";
    $_SESSION['settings_msg_type'] = 'error';
    header("Location: dashboard.php#settings");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        // --- Handle Text-Based Settings ---
        $settings_data = $_POST['settings'] ?? [];
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($settings_data as $key => $value) {
            $stmt->bind_param("ss", $key, $value);
            $stmt->execute();
        }
        $stmt->close();

        // --- Handle File Upload for Site Logo ---
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
            $file = $_FILES['site_logo'];
            $allowed_types = ['image/png', 'image/jpeg', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2 MB

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception("Invalid file type for logo. Please use PNG, JPG, or GIF.");
            }
            if ($file['size'] > $max_size) {
                throw new Exception("Logo file is too large. Maximum size is 2 MB.");
            }

            // Define upload directory and create a unique filename
            $upload_dir = '../../assets/images/';
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = 'logo.' . $file_extension; // Overwrite the old logo with a consistent name
            $upload_path = $upload_dir . $new_filename;

            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                throw new Exception("Failed to move uploaded logo file.");
            }

            // Update the site_logo setting in the database
            $stmt_logo = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt_logo->bind_param("s", $new_filename);
            $stmt_logo->execute();
            $stmt_logo->close();
        }

        // If all operations are successful, commit the transaction
        $conn->commit();
        $_SESSION['settings_msg'] = "System settings updated successfully!";
        $_SESSION['settings_msg_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['settings_msg'] = "An error occurred: " . $e->getMessage();
        $_SESSION['settings_msg_type'] = 'error';
    }

} else {
    $_SESSION['settings_msg'] = "Invalid request method.";
    $_SESSION['settings_msg_type'] = 'error';
}

$conn->close();
header("Location: dashboard.php#settings");
exit();
?>