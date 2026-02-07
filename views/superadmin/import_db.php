<?php
require_once '../../includes/session_check.php';

// Security Check: Ensure only superadmin can perform this action
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    $_SESSION['permissions_msg'] = "Unauthorized access.";
    $_SESSION['permissions_msg_type'] = 'error';
    header("Location: dashboard.php#database-control");
    exit();
}

require_once '../../config/db_connect.php';

if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] == 0) {
    $filename = $_FILES['backup_file']['name'];
    $filetype = pathinfo($filename, PATHINFO_EXTENSION);

    if ($filetype != 'sql') {
        $_SESSION['permissions_msg'] = "Invalid file type. Please upload a .sql file.";
        $_SESSION['permissions_msg_type'] = 'error';
        header("Location: dashboard.php#database-control");
        exit();
    }

    $sql = file_get_contents($_FILES['backup_file']['tmp_name']);

    if ($conn->multi_query($sql)) {
        // Clear the results of the multi_query
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        $_SESSION['permissions_msg'] = "Database imported successfully.";
        $_SESSION['permissions_msg_type'] = 'success';
    } else {
        $_SESSION['permissions_msg'] = "Error importing database: " . $conn->error;
        $_SESSION['permissions_msg_type'] = 'error';
    }
} else {
    $_SESSION['permissions_msg'] = "No file uploaded or an error occurred during upload.";
    $_SESSION['permissions_msg_type'] = 'error';
}

$conn->close();
header("Location: dashboard.php#database-control");
exit();
?>