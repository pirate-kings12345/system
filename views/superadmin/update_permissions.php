<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php'; // Include functions for has_permission()

// Security Check: Ensure the user has the permission to manage roles.
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'superadmin' && !has_permission($_SESSION['role'], 'manage_role_permissions', $conn))) {
    $_SESSION['permissions_msg'] = "Unauthorized access.";
    $_SESSION['permissions_msg_type'] = 'error';
    header("Location: dashboard.php#permissions");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $permissions_data = $_POST['permissions'] ?? [];

    // Use the central permission definitions as the single source of truth
    require_once '../../includes/permissions_map.php';

    $all_defined_permissions = [];
    foreach ($role_specific_permissions as $role => $categories) {
        $all_defined_permissions[$role] = [];
        foreach ($categories as $permissions) {
            foreach ($permissions as $key => $desc) {
                $all_defined_permissions[$role][$key] = $desc;
            }
        }
    }

    $conn->begin_transaction();

    try {
        // --- Handle Role-Based Permissions ---
        $role_permissions_data = $permissions_data['role'] ?? [];
        $role_stmt = $conn->prepare("INSERT INTO role_permissions (role, permission_key, is_allowed) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed)");

        foreach ($all_defined_permissions as $role => $permissions) {
            // Skip admin role as it's handled per-user now, but you could still set defaults here if you wanted.
            if ($role === 'admin') continue;

            foreach (array_keys($permissions) as $perm_key) {
                $is_allowed = isset($role_permissions_data[$role][$perm_key]) ? 1 : 0;
                $role_stmt->bind_param("ssi", $role, $perm_key, $is_allowed);
                $role_stmt->execute();
            }
        }
        $role_stmt->close();

        // --- Handle User-Specific (Admin) Permissions ---
        $user_permissions_data = $permissions_data['user'] ?? [];
        if (!empty($user_permissions_data)) {
            $user_stmt = $conn->prepare("INSERT INTO user_permissions (user_id, permission_key, is_allowed) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE is_allowed = VALUES(is_allowed)");
            
            // Loop through each admin that had permissions submitted
            foreach ($user_permissions_data as $admin_id => $admin_perms_submitted) {
                // Loop through all possible admin permissions to ensure we handle unchecked boxes
                foreach ($role_specific_permissions['admin'] as $group => $permissions) {
                    foreach ($permissions as $perm_key => $perm_desc) {
                        // If the checkbox was checked, it will be in the submitted data. Otherwise, it's off (0).
                        $is_allowed = isset($admin_perms_submitted[$perm_key]) ? 1 : 0;
                        $user_stmt->bind_param("isi", $admin_id, $perm_key, $is_allowed);
                        $user_stmt->execute();
                    }
                }

                // --- Add to Audit Log ---
                // Get the username of the admin whose permissions were changed for a more descriptive log.
                $admin_username_stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
                $admin_username_stmt->bind_param("i", $admin_id);
                $admin_username_stmt->execute();
                $admin_username = $admin_username_stmt->get_result()->fetch_assoc()['username'] ?? 'ID ' . $admin_id;
                $admin_username_stmt->close();

                $current_admin_id = $_SESSION['user_id']; // The superadmin performing the action
                $log_action = "Updated permissions for admin: " . htmlspecialchars($admin_username);
                $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
                $log_stmt->bind_param("is", $current_admin_id, $log_action);
                $log_stmt->execute();
                $log_stmt->close();
            }
            $user_stmt->close();
        }

        $conn->commit();
        $_SESSION['permissions_msg'] = "Permissions updated successfully!";
        $_SESSION['permissions_msg_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['permissions_msg'] = "Database error: " . $conn->error;
        $_SESSION['permissions_msg_type'] = 'error';
    }

    $conn->close();
    header("Location: dashboard.php#permissions");
    exit();

} else {
    $_SESSION['permissions_msg'] = "Invalid request method.";
    $_SESSION['permissions_msg_type'] = 'error';
    header("Location: dashboard.php#permissions");
    exit();
}
?>