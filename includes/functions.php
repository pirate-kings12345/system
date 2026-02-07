<?php

/**
 * Handles setting and unsetting of session-based feedback messages.
 */
function handle_session_messages() {
    if (isset($_SESSION['success'])) {
        $_SESSION['success_message'] = $_SESSION['success'];
        unset($_SESSION['success']);
    }

    if (isset($_SESSION['error'])) {
        $_SESSION['error_message'] = $_SESSION['error'];
        unset($_SESSION['error']);
    }
}

/**
 * Fetches the active school year from system settings.
 */
function get_active_sy($conn) {
    $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_sy' LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    return '2024-2025'; // Fallback value
}

/**
 * Fetches the active semester from system settings.
 */
function get_active_semester($conn) {
    $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'active_semester' LIMIT 1");
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc()['setting_value'];
    }
    return '1st'; // Fallback value
}

/**
 * Fetches the course name for a given course ID.
 */
function get_course_name($conn, $course_id) {
    $stmt = $conn->prepare("SELECT course_name FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['course_name'] ?? 'N/A';
}

/**
 * Converts a year level string (e.g., "1st Year") to its numeric representation.
 */
function get_year_level_numeric($year_level_string) {
    if (empty($year_level_string)) {
        return '';
    }
    switch ($year_level_string) {
        case '1st Year': return '1';
        case '2nd Year': return '2';
        case '3rd Year': return '3';
        case '4th Year': return '4';
        default:
            // Fallback for other formats, just extract numbers
            return preg_replace('/[^0-9]/', '', $year_level_string);
    }
}

/**
 * Checks if a given role has a specific permission.
 *
 * @param string $role The role to check (e.g., 'student', 'admin').
 * @param string $permission_key The permission key to verify (e.g., 'view_student_dashboard').
 * @param mysqli $conn The database connection object.
 * @return bool True if the role has the permission, false otherwise.
 */
function has_permission($role, $permission_key, $conn) {
    // Superadmin has all permissions by default
    if ($role === 'superadmin') {
        return true;
    }

    $stmt = $conn->prepare("SELECT is_allowed FROM role_permissions WHERE role = ? AND permission_key = ?");
    $stmt->bind_param("ss", $role, $permission_key);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    $permission = $result->fetch_assoc();
    return ($permission && $permission['is_allowed'] == 1);
}
?>