<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/permissions_map.php'; // Important: Include the map

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($permissions_map)) {
    $submitted_permissions = $_POST['permissions'] ?? [];

    $conn->begin_transaction();

    try {
        // First, clear all existing permissions
        $conn->query("DELETE FROM role_permissions");

        // Prepare the insert statement
        $stmt = $conn->prepare("INSERT INTO role_permissions (role, permission_key) VALUES (?, ?)");

        // Loop through the DEFINED roles and permissions from the map
        foreach ($permissions_map as $role => $categories) {
            // Check if this role had any permissions submitted
            if (isset($submitted_permissions[$role]) && is_array($submitted_permissions[$role])) {
                // Loop through the submitted keys for this role and insert them
                foreach ($submitted_permissions[$role] as $key) {
                    $stmt->bind_param("ss", $role, $key);
                    $stmt->execute();
                }
            }
        }

        $stmt->close();
        $conn->commit();

        $_SESSION['permissions_msg'] = "Role permissions have been updated successfully.";
        $_SESSION['permissions_msg_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['permissions_msg'] = "An error occurred while saving permissions: " . $e->getMessage();
        $_SESSION['permissions_msg_type'] = 'error';
    }
}

header("Location: ../superadmin/manage_role_permissions.php");
exit();