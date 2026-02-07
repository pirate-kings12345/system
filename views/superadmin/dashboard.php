<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';

// Allow both superadmin and admin to access this dashboard
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/db_connect.php';

// --- Fetch permissions for the currently logged-in user ---
$user_permissions = [];
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    $stmt_perms = $conn->prepare("SELECT permission_key FROM role_permissions WHERE role = ? AND is_allowed = 1");
    $stmt_perms->bind_param("s", $role);
    $stmt_perms->execute();
    $result_perms = $stmt_perms->get_result();
    while ($row_perm = $result_perms->fetch_assoc()) { $user_permissions[$row_perm['permission_key']] = true; }
    $stmt_perms->close();
}

// --- Data Fetching for Widgets ---
$pendingEnrollmentsResult = $conn->query("SELECT COUNT(enrollment_id) as total FROM enrollments WHERE status = 'pending'");
$pendingEnrollmentsCount = $pendingEnrollmentsResult->fetch_assoc()['total'] ?? 0;

$totalStudentsResult = $conn->query("SELECT COUNT(user_id) as total FROM users WHERE role = 'student'");
$totalStudents = $totalStudentsResult->fetch_assoc()['total'] ?? 0;

$totalInstructorsResult = $conn->query("SELECT COUNT(user_id) as total FROM users WHERE role = 'instructor'");
$totalInstructors = $totalInstructorsResult->fetch_assoc()['total'] ?? 0;

$activeAdminsResult = $conn->query("SELECT COUNT(user_id) as total FROM users WHERE role = 'admin' AND status = 'active'");
$activeAdminsCount = $activeAdminsResult->fetch_assoc()['total'] ?? 0;

$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

// --- Data Fetching for Analytics ---
$analytics_data = [
    'user_roles' => [],
    'enrollments_per_course' => []
];

// 1. User role distribution
$role_result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
if ($role_result) {
    while ($row = $role_result->fetch_assoc()) {
        $analytics_data['user_roles'][$row['role']] = $row['count'];
    }
}

// 2. Enrollment per course for active term
$active_sy = $settings['active_sy'] ?? '';
$active_sem = $settings['active_semester'] ?? '';
$enrollment_sql = "SELECT c.course_code, COUNT(e.enrollment_id) as enrollment_count 
                   FROM enrollments e 
                   JOIN students s ON e.student_id = s.student_id 
                   JOIN courses c ON s.course_id = c.course_id 
                   WHERE e.status = 'approved' AND e.school_year = ? AND e.semester = ? 
                   GROUP BY c.course_id ORDER BY enrollment_count DESC";
$stmt_enroll = $conn->prepare($enrollment_sql);
$stmt_enroll->bind_param("ss", $active_sy, $active_sem);
$stmt_enroll->execute();
$enrollment_result = $stmt_enroll->get_result();
if ($enrollment_result) {
    while ($row = $enrollment_result->fetch_assoc()) {
        $analytics_data['enrollments_per_course'][] = $row;
    }
}
$stmt_enroll->close();

// Fetch Admins (excluding superadmin itself)
$admins = [];
$adminSql = "SELECT user_id, username, email, status FROM users WHERE role = 'admin' AND username != 'superadmin'";
$adminResult = $conn->query($adminSql);
if ($adminResult->num_rows > 0) {
    while($row = $adminResult->fetch_assoc()) {
        $admins[] = $row;
    }
}

// Fetch Activity Logs
$logs = [];
$logSql = "SELECT al.action, al.log_time, u.username 
           FROM audit_logs al 
           LEFT JOIN users u ON al.user_id = u.user_id 
           ORDER BY al.log_time DESC LIMIT 5";
$logResult = $conn->query($logSql);
if ($logResult->num_rows > 0) {
    while($row = $logResult->fetch_assoc()) {
        $logs[] = $row;
    }
}

// Fetch Announcements for Admin/Superadmin
$announcements_for_admin = [];
$announcementSql = "SELECT title, content, date_posted, target_role FROM announcements WHERE target_role IN ('all', 'admin', 'superadmin', 'student', 'instructor') ORDER BY date_posted DESC";
$announcementResult = $conn->query($announcementSql);
if ($announcementResult && $announcementResult->num_rows > 0) {
    while($row = $announcementResult->fetch_assoc()) {
        $announcements_for_admin[] = $row;
    }
}

// --- Permissions ---
// Include the central permission definitions
require_once '../../includes/permissions_map.php';

$roles = ['instructor', 'student']; // Admin is now handled separately
$current_permissions = [];
$rolePermResult = $conn->query("SELECT role, permission_key, is_allowed FROM role_permissions");
if ($rolePermResult) {
    while ($row = $rolePermResult->fetch_assoc()) {
        $current_permissions['role'][$row['role']][$row['permission_key']] = $row['is_allowed'];
    }
}

// Fetch user-specific permissions for admins
// Check if the user_permissions table exists before querying it
$table_exists_result = $conn->query("SHOW TABLES LIKE 'user_permissions'");
if ($table_exists_result && $table_exists_result->num_rows > 0) {
    $userPermResult = $conn->query("SELECT up.user_id, up.permission_key, up.is_allowed FROM user_permissions up JOIN users u ON up.user_id = u.user_id WHERE u.role = 'admin'");
    if ($userPermResult) {
        while ($row = $userPermResult->fetch_assoc()) {
            $current_permissions['user'][$row['user_id']][$row['permission_key']] = $row['is_allowed'];
        }
    }
} else {
    // If the table doesn't exist, initialize an empty array to prevent errors
    $current_permissions['user'] = [];
}

// Modules
$defined_modules = [
    'module_enrollment' => ['name' => 'Student Enrollment', 'description' => 'Allow students to enroll in courses.'],
    'module_grades' => ['name' => 'Grade Management', 'description' => 'Allow instructors to submit and students to view grades.'],
    'module_registration' => ['name' => 'Public Registration', 'description' => 'Allow new users to create accounts.']
];

$conn->close();
handle_session_messages();

$page_title = 'Superadmin Dashboard';
include '_header.php'; 
?>
<style>
    /* Animation for content switching */
    .content-section {
        display: none;
        opacity: 0;
        transition: opacity 0.4s ease-in-out;
    }
    .permission-group-header td {
        background-color: #f8f9fa;
        font-weight: bold;
        padding-top: 15px;
    }
    /* New styles for permission tabs */
    .permission-tabs {
        display: flex;
        border-bottom: 2px solid #dee2e6;
        margin-bottom: 20px;
    }
    .permission-tab-link {
        padding: 10px 20px;
        cursor: pointer;
        border: none;
        background: none;
        font-size: 1rem;
        font-weight: 600;
        color: var(--gray);
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
    }
    .permission-tab-link.active {
        color: var(--primary);
        border-bottom-color: var(--primary);
        transition: all 0.3s ease;
    }
    .content-section.active {
        display: block;
        opacity: 1;
    }

    /* Professional Modal Styling */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 9999; 
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
        animation: fadeIn 0.3s;
    }
    @keyframes fadeIn { from {opacity: 0;} to {opacity: 1;} }

    /* --- Professional UI Enhancements --- */

    /* Card hover effect */
    .card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.12);
    }

    /* Table row hover effect */
    table tbody tr {
        transition: background-color 0.2s ease-in-out;
    }

    /* Button enhancements */
    .button {
        transition: all 0.3s ease;
    }
    .button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    /* Quick Links hover effect */
    .quick-links-list li a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        border-radius: 8px;
        text-decoration: none;
        color: var(--dark);
        transition: background-color 0.2s ease, color 0.2s ease;
    }
    .quick-links-list li a:hover {
        background-color: var(--primary);
        color: white;
    }
    .quick-links-list li a i { width: 20px; text-align: center; }

    /* Accordion Styles for Admin Permissions */
    .admin-permissions-accordion .accordion-item {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 10px;
        overflow: hidden;
    }
    .admin-permissions-accordion .accordion-header {
        width: 100%;
        background-color: #f8f9fa;
        padding: 15px 20px;
        border: none;
        text-align: left;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .admin-permissions-accordion .accordion-header i { transition: transform 0.3s ease; }
    .admin-permissions-accordion .accordion-header.active i { transform: rotate(180deg); }
    .admin-permissions-accordion .accordion-content { padding: 20px; display: none; }
</style>

<!-- Default Dashboard Overview -->
<div id="dashboard-home" class="content-section" data-required-permission="view_admin_dashboard">
    <h2><i class="fa-solid fa-tachometer-alt"></i> Dashboard Overview</h2>
    <div class="dashboard-grid" style="grid-template-columns: repeat(4, 1fr);">
        <div class="card stats-card">
            <div>
                <h3>Pending Enrollments</h3>
                <div class="stats-value" style="color: #e67e22;"><?= $pendingEnrollmentsCount ?></div>
                <div class="stats-change positive">
                    <i class="fas fa-hourglass-half"></i>
                    Awaiting Approval
                </div>
            </div>
        </div>
        <div class="card stats-card">
            <div>
                <h3>Total Students</h3>
                <div class="stats-value" style="color: #3498db;"><?= $totalStudents ?></div>
                <div class="stats-change positive">
                    <i class="fas fa-user-graduate"></i>
                    Registered Students
                </div>
            </div>
        </div>
        <div class="card stats-card">
            <div>
                <h3>Total Instructors</h3>
                <div class="stats-value" style="color: #2ecc71;"><?= $totalInstructors ?></div>
                <div class="stats-change positive">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Faculty Members
                </div>
            </div>
        </div>
        <div class="card stats-card">
            <div>
                <h3>Active Admins</h3>
                <div class="stats-value" style="color: #9b59b6;"><?= $activeAdminsCount ?></div>
                <div class="stats-change positive">
                    <i class="fas fa-user-shield"></i>
                    System Administrators
                </div>
            </div>
        </div>
    </div>
    <div class="data-grid" style="margin-top: 20px; grid-template-columns: 1fr 1fr; align-items: flex-start;">
        <div class="card">
            <h3>Enrollment per Course (S.Y. <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_sem) ?>)</h3>
            <canvas id="enrollmentChartDashboard"></canvas>
        </div>
        <div class="card list-card">
            <h3>Recent System Activity</h3>
            <ul class="activity-list">
                <?php if (!empty($logs)): foreach ($logs as $log): ?>
                    <li><div class="activity-icon"><i class="fas fa-history"></i></div><div><strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong> <?= htmlspecialchars($log['action']) ?> <span style="color: #999; font-size: 0.8em;"><?= date('M d, h:i A', strtotime($log['log_time'])) ?></span></div></li>
                <?php endforeach; else: ?>
                    <li>No recent activity found.</li>
                <?php endif; ?>
            </ul>
            <a href="#" onclick="showContent('view-logs', document.querySelector('a[onclick*=\'view-logs\']'))" style="display: block; text-align: right; margin-top: 1rem;">View All Logs &rarr;</a>
        </div>
    </div>
</div>

<!-- Manage Admin Section -->
<div id="manage-admin" class="content-section" data-required-permission="manage_instructors,manage_students">
    <h2>
        <i class="fa-solid fa-user-shield"></i> Manage Admins
        <a href="#" id="addAdminBtn" class="button add-btn"><i class="fa-solid fa-plus"></i> Add New Admin</a>
    </h2>

    <!-- Display Success/Failure Messages -->
    <?php if(isset($_SESSION['add_admin_msg'])): ?>
        <div class="alert <?= $_SESSION['add_admin_msg_type'] ?? 'success' ?>" style="margin-bottom:15px;">
            <?= $_SESSION['add_admin_msg'] ?>
        </div>
    <?php unset($_SESSION['add_admin_msg'], $_SESSION['add_admin_msg_type']); endif; ?>

    <?php if(isset($_SESSION['edit_admin_msg'])): ?>
        <div class="alert <?= $_SESSION['edit_admin_msg_type'] ?? 'success' ?>" style="margin-bottom:15px;">
            <?= $_SESSION['edit_admin_msg'] ?>
        </div>
    <?php 
        unset($_SESSION['edit_admin_msg'], $_SESSION['edit_admin_msg_type']);
    endif; 
    ?>

    <p>Here you can view, edit, and manage other administrators.</p>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($admins)): ?>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['username']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td>
                            <?php 
                                $status_class = $admin['status'] === 'deactivated' ? 'inactive' : $admin['status'];
                            ?>
                            <span class="status-<?= htmlspecialchars($status_class) ?>">
                                <?= ucfirst(htmlspecialchars($admin['status'])) ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="#" class="edit-btn" onclick='openEditModal(<?= json_encode($admin) ?>); return false;'><i class="fa-solid fa-pencil"></i> Edit</a>
                            <?php if ($admin['status'] == 'active'): ?>
                                <a href="#" onclick="updateAdminStatus(this, <?= $admin['user_id'] ?>, 'deactivate'); return false;">
                                    <i class="fa-solid fa-toggle-on"></i> Deactivate
                                </a>
                            <?php else: ?>
                                <a href="#" onclick="updateAdminStatus(this, <?= $admin['user_id'] ?>, 'activate'); return false;" style="color: #27ae60; --fa-secondary-color: #27ae60;">
                                    <i class="fa-solid fa-toggle-off"></i> Activate
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align:center;">No other administrators found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Announcements Section -->
<div id="announcements" class="content-section" data-required-permission="manage_announcements">
    <h2>
        <i class="fa-solid fa-bullhorn"></i> Announcements
        <a href="manage_announcements.php" class="button add-btn"><i class="fa-solid fa-pen-to-square"></i> Manage Announcements</a>
    </h2>
    <p>This section displays announcements targeted to you and global announcements.</p>
    
    <ul class="activity-list" style="margin-top: 20px;">
        <?php if (!empty($announcements_for_admin)): ?>
            <?php foreach ($announcements_for_admin as $announcement): ?>
                <li style="align-items: flex-start;">
                    <div class="activity-icon" style="margin-top: 4px;"><i class="fas fa-bullhorn"></i></div>
                    <div>
                        <strong><?= htmlspecialchars($announcement['title']) ?></strong><br>
                        <?= nl2br(htmlspecialchars($announcement['content'])) ?><br>
                        <small style="color: #007bff; font-weight: bold;">Audience: <?= ucfirst(htmlspecialchars($announcement['target_role'])) ?></small><br>
                        <small style="color: var(--gray);">Posted on: <?= date('F j, Y, g:i a', strtotime($announcement['date_posted'])) ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li>No announcements found for your role.</li>
        <?php endif; ?>
    </ul>
</div>

<!-- Analytics Section -->
<div id="analytics" class="content-section" data-required-permission="view_admin_dashboard">
    <h2><i class="fa-solid fa-chart-pie"></i> System Analytics</h2>
    <p>This area will display charts and statistics about user activity, enrollment trends, and overall system performance.</p>
    <div class="data-grid" style="margin-top: 20px; grid-template-columns: 1fr 2fr; align-items: flex-start;">
        <div class="card">
            <h3>User Distribution</h3>
            <canvas id="userRoleChart"></canvas>
            <ul style="list-style: none; padding-left: 0; margin-top: 1rem; font-size: 0.9rem;" id="user-distribution-list">
                <li><strong>Students:</strong> <?= $analytics_data['user_roles']['student'] ?? 0 ?></li>
                <li><strong>Instructors:</strong> <?= $analytics_data['user_roles']['instructor'] ?? 0 ?></li>
                <li><strong>Admins:</strong> <?= $analytics_data['user_roles']['admin'] ?? 0 ?></li>
                <li><strong>Superadmin:</strong> <?= $analytics_data['user_roles']['superadmin'] ?? 0 ?></li>
            </ul>
        </div>
        <div class="card">
            <h3>Enrollment per Course (S.Y. <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_sem) ?>)</h3>
            <canvas id="enrollmentChartAnalytics"></canvas>
        </div>
    </div>
</div>

<div id="database-control" class="content-section" data-required-permission="manage_database">
    <h2><i class="fa-solid fa-database"></i> Database Management</h2>
    <p>This area provides tools to export (backup), import, and reset system data. Use these features with extreme caution.</p>

    <div class="db-actions-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
        
        <!-- Export/Backup Card -->
        <div class="card">
            <h3><i class="fa-solid fa-download"></i> Export Database</h3>
            <p>Create a complete backup of the database. This will generate an SQL file containing all the data and structures of the tables.</p>
            <a href="backup_db.php" class="button" style="margin-top: 1rem;"><i class="fa-solid fa-download"></i> Create and Download Backup</a>
        </div>

        <!-- Import Card -->
        <div class="card">
            <h3><i class="fa-solid fa-upload"></i> Import Database</h3>
            <p>Restore the database from an SQL backup file. <strong>Warning:</strong> This will overwrite all current data.</p>
            <form action="import_db.php" method="post" enctype="multipart/form-data" style="margin-top: 1rem;" onsubmit="return confirm('Are you sure you want to import this file? This will overwrite the current database.');">
                <input type="file" name="backup_file" accept=".sql" required style="margin-bottom: 10px;">
                <button type="submit" class="button" style="background-color: #e67e22;"><i class="fa-solid fa-upload"></i> Import Backup</button>
            </form>
        </div>

        <!-- Reset Card -->
        <div class="card">
            <h3 style="color: #e74c3c;"><i class="fa-solid fa-trash-arrow-up"></i> Reset System Data</h3>
            <p>Deletes all users (except superadmin), student info, enrollments, schedules, subjects, sections, courses, departments, rooms, announcements, and grades. Core system settings are preserved. <strong>This action is irreversible.</strong></p>
            <button class="button" style="margin-top: 1rem; background-color: #e74c3c;" onclick="showConfirmation(
                'Confirm System Reset',
                'Are you sure you want to reset all transactional data? This action cannot be undone.',
                'fa-solid fa-triangle-exclamation danger',
                'confirm-btn-danger',
                () => { window.location.href = 'reset_db.php'; }
            );"><i class="fa-solid fa-eraser"></i> Reset Data</button>
        </div>
    </div>
</div>

<div id="view-logs" class="content-section" data-required-permission="view_admin_dashboard">
    <h2><i class="fa-solid fa-clipboard-list"></i> System Activity Log</h2>
    <p>This table displays the most recent 20 user logins, actions performed by administrators, and other critical system events for auditing purposes.</p>
    <table>
        <thead>
            <tr>
                <th>User</th>
                <th>Action</th>
                <th>Timestamp</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)): ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['log_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3" style="text-align:center;">No activity logs found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Settings Section -->
<div id="settings" class="content-section" data-required-permission="manage_system_settings">
    <h2><i class="fa-solid fa-cog"></i> General Settings</h2>
    <p>Manage core system settings like the active school year, semester, site name, and logo.</p>

    <?php if(isset($_SESSION['settings_msg'])): ?>
        <div class="alert <?= $_SESSION['settings_msg_type'] ?? 'success' ?>" style="margin-bottom:15px;">
            <?= $_SESSION['settings_msg'] ?>
        </div>
    <?php unset($_SESSION['settings_msg'], $_SESSION['settings_msg_type']); endif; ?>

    <form action="update_settings.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
        <div class="modal-form-grid" style="max-width: 800px;">
            <div class="form-input-group">
                <label for="active_sy">Active School Year</label>
                <input type="text" id="active_sy" name="settings[active_sy]" value="<?= htmlspecialchars($settings['active_sy'] ?? '2024-2025') ?>" placeholder="e.g., 2024-2025">
            </div>
            <div class="form-input-group">
                <label for="active_semester">Active Semester</label>
                <select id="active_semester" name="settings[active_semester]">
                    <option value="1st Semester" <?= (isset($settings['active_semester']) && $settings['active_semester'] == '1st Semester') ? 'selected' : '' ?>>1st Semester</option>
                    <option value="2nd Semester" <?= (isset($settings['active_semester']) && $settings['active_semester'] == '2nd Semester') ? 'selected' : '' ?>>2nd Semester</option>
                    <option value="Summer" <?= (isset($settings['active_semester']) && $settings['active_semester'] == 'Summer') ? 'selected' : '' ?>>Summer</option>
                </select>
            </div>
            <div class="form-input-group full-width">
                <label for="site_name">Site Name</label>
                <input type="text" id="site_name" name="settings[site_name]" value="<?= htmlspecialchars($settings['site_name'] ?? 'SchedMaster') ?>">
            </div>
            <div class="form-input-group full-width">
                <label for="site_logo">Site Logo</label>
                <input type="file" id="site_logo" name="site_logo" accept="image/png, image/jpeg, image/gif">
                <p style="font-size: 0.9em; color: #777; margin-top: 5px;">Current logo: <strong><?= htmlspecialchars($settings['site_logo'] ?? 'logo.png') ?></strong>. Upload a new file to replace it.</p>
            </div>
        </div>
        <button type="submit" class="button" style="margin-top: 1rem;"><i class="fa-solid fa-save"></i> Save Settings</button>
    </form>
</div>

<!-- Permissions Section -->
<div id="permissions" class="content-section" data-required-permission="manage_role_permissions">
    <h2><i class="fa-solid fa-user-shield"></i> Role Permissions Management</h2>
    <p>Define what each user role can see and do within the system. Changes will apply to all users within that role.</p>

    <?php if(isset($_SESSION['permissions_msg'])): ?>
        <div class="alert <?= $_SESSION['permissions_msg_type'] ?? 'success' ?>" style="margin-bottom:15px;">
            <?= $_SESSION['permissions_msg'] ?>
        </div>
    <?php unset($_SESSION['permissions_msg'], $_SESSION['permissions_msg_type']); endif; ?>

    <form action="update_permissions.php" method="POST">
        <!-- Tabs for Roles vs Admins -->
        <div class="permission-tabs">
            <button type="button" class="permission-tab-link active" onclick="showPermissionTab('admin-management', this)">Admins</button>
            <?php foreach ($roles as $role): ?>
                <button type="button" class="permission-tab-link" onclick="showPermissionTab('<?= $role ?>', this)"><?= ucfirst($role) ?></button>
            <?php endforeach; ?>
        </div>

        <!-- Admin Specific Permissions -->
        <div id="permissions-admin-management" class="permission-tab-content">
            <h4>Manage Individual Admin Permissions</h4>
            <p>Click on an admin's name to expand and configure their specific permissions. These settings will override the default 'Admin' role permissions.</p>
            <div class="admin-permissions-accordion" style="margin-top: 20px;">
                <?php foreach ($admins as $admin): ?>
                    <div class="accordion-item">
                        <button type="button" class="accordion-header">
                            <span><i class="fas fa-user-shield"></i> <?= htmlspecialchars($admin['username']) ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="accordion-content">
                            <?php foreach ($role_specific_permissions['admin'] as $group_name => $permissions): ?>
                                <h5 style="margin-top: 20px; margin-bottom: 10px;"><?= htmlspecialchars($group_name) ?></h5>
                                <?php foreach ($permissions as $perm_key => $perm_desc): 
                                    $is_checked = isset($current_permissions['user'][$admin['user_id']][$perm_key]) && $current_permissions['user'][$admin['user_id']][$perm_key] ? 'checked' : '';
                                ?>
                                    <div class="permission-item-card"><div class="info"><strong><?= htmlspecialchars($perm_desc) ?></strong></div><label class="switch"><input type="checkbox" name="permissions[user][<?= $admin['user_id'] ?>][<?= $perm_key ?>]" value="1" <?= $is_checked ?>><span class="slider"></span></label></div>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Role-Based Permissions -->
        <?php foreach ($roles as $role): ?>
            <div id="permissions-<?= $role ?>" class="permission-tab-content" style="display:none;">
                <h4>Permissions for <?= ucfirst($role) ?></h4>
                <?php foreach ($role_specific_permissions[$role] as $group_name => $permissions): ?>
                    <h5 style="margin-top: 20px; margin-bottom: 10px;"><?= htmlspecialchars($group_name) ?></h5>
                    <?php foreach ($permissions as $perm_key => $perm_desc): ?>
                        <div class="permission-item-card">
                            <div class="info">
                                <strong><?= htmlspecialchars($perm_desc) ?></strong>
                            </div>
                            <label class="switch">
                                <input type="checkbox" name="permissions[role][<?= $role ?>][<?= $perm_key ?>]" value="1" 
                                    <?php if (isset($current_permissions['role'][$role][$perm_key]) && $current_permissions['role'][$role][$perm_key]): ?>checked<?php endif; ?>>
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <button type="submit" class="button" style="margin-top: 20px;"><i class="fa-solid fa-save"></i> Save Permissions</button>
    </form>
</div>

<!-- Modules Section -->
<div id="modules" class="content-section" data-required-permission="manage_system_modules">
    <h2><i class="fa-solid fa-puzzle-piece"></i> System Modules Maintenance</h2>
    <p>Use this page to enable or disable major features of the system, such as enrollment, grade submission, etc.</p>

    <?php if(isset($_SESSION['modules_msg'])): ?>
        <div class="alert <?= $_SESSION['modules_msg_type'] ?? 'success' ?>" style="margin-bottom:15px;">
            <?= $_SESSION['modules_msg'] ?>
        </div>
    <?php unset($_SESSION['modules_msg'], $_SESSION['modules_msg_type']); endif; ?>

    <form action="update_modules.php" method="POST">
        <div class="module-grid">
            <?php foreach ($defined_modules as $module_key => $module_details): ?>
                <div class="module-card">
                    <div class="info">
                        <h4><?= htmlspecialchars($module_details['name']) ?></h4>
                        <p><?= htmlspecialchars($module_details['description']) ?></p>
                    </div>
                    <label class="switch">
                        <input type="checkbox" name="modules[<?= $module_key ?>]" value="1"
                            <?php if (isset($settings[$module_key]) && $settings[$module_key] == '1'): ?>checked<?php endif; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="button" style="margin-top: 20px;"><i class="fa-solid fa-save"></i> Save Module Settings</button>
    </form>
</div>

<?php include '_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass PHP permissions to JavaScript
    const userPermissions = <?= json_encode($user_permissions) ?>;
    // The superadmin role should always have all permissions, client-side.
    const isSuperAdmin = <?= json_encode($_SESSION['role'] === 'superadmin') ?>;
    // Pass all permission structures to JS
    const allPermissionsMap = <?= json_encode($role_specific_permissions) ?>;
    const currentPermissions = <?= json_encode($current_permissions) ?>;

    function showPermissionTab(role, element) {
        // Hide all tab content
        document.querySelectorAll('.permission-tab-content').forEach(tab => {
            tab.style.display = 'none';
        });
        // Deactivate all tab links
        document.querySelectorAll('.permission-tab-link').forEach(link => {
            link.classList.remove('active');
        });

        // Show the selected tab content and activate the link
        document.getElementById('permissions-' + role.replace('-management', '')).style.display = 'block';
        element.classList.add('active');
    }

    // Override the showConfirmation function to also be an alert modal
    function showAlert(title, text, iconClass = 'fa-solid fa-circle-info') {
        const modal = document.getElementById('confirmationModal');
        const titleEl = document.getElementById('confirmationModalTitle');
        const textEl = document.getElementById('confirmationModalText');
        const iconEl = document.getElementById('confirmationModalIcon');
        const confirmBtn = document.getElementById('confirmationModalConfirm');
        const cancelBtn = document.getElementById('confirmationModalCancel');

        titleEl.textContent = title;
        textEl.textContent = text;
        iconEl.className = `modal-icon ${iconClass}`;
        
        // Configure for alert mode (one button)
        confirmBtn.textContent = 'Close';
        cancelBtn.style.display = 'none';
        confirmBtn.className = 'button'; // Reset to default

        modal.style.display = 'block';

        confirmBtn.onclick = () => {
            modal.style.display = 'none';
            // Reset for next use as a confirmation modal
            cancelBtn.style.display = 'inline-block';
            confirmBtn.textContent = 'Confirm';
        };
    }

    function showContentWithPermission(sectionId, element, requiredPermission) {
        const permissions = requiredPermission.split(',');
        const hasAnyPermission = permissions.some(p => userPermissions[p.trim()]);

        if (isSuperAdmin || hasAnyPermission) {
            showContent(sectionId, element);
        } else {
            showAlert('Access Denied', 'You do not have permission to access this section. Please contact a superadmin if you believe this is an error.', 'fa-solid fa-lock danger');
        }
    }

    // --- Accordion Logic for Admin Permissions ---
    document.querySelectorAll('.accordion-header').forEach(button => {
        button.addEventListener('click', () => {
            const content = button.nextElementSibling;
            button.classList.toggle('active');
            if (content.style.display === 'block') {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }
        });
    });

    // --- Chart.js Analytics ---
    document.addEventListener('DOMContentLoaded', function() {
        // User Role Chart
        const userRoleCtx = document.getElementById('userRoleChart');
        if (userRoleCtx) {
            new Chart(userRoleCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Students', 'Instructors', 'Admins', 'Superadmin'],
                    datasets: [{
                        label: 'User Roles',
                        data: [
                            <?= $analytics_data['user_roles']['student'] ?? 0 ?>,
                            <?= $analytics_data['user_roles']['instructor'] ?? 0 ?>,
                            <?= $analytics_data['user_roles']['admin'] ?? 0 ?>,
                            <?= $analytics_data['user_roles']['superadmin'] ?? 0 ?>
                        ],
                        backgroundColor: ['#3498db', '#2ecc71', '#f39c12', '#e74c3c'],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } }
                }
            });
        }

        // Enrollment per Course Chart
        function createEnrollmentChart(canvasId) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;
            const enrollmentData = <?= json_encode($analytics_data['enrollments_per_course']) ?>;
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: enrollmentData.map(row => row.course_code),
                    datasets: [{
                        label: 'Number of Enrolled Students',
                        data: enrollmentData.map(row => row.enrollment_count),
                        backgroundColor: 'rgba(26, 107, 179, 0.7)',
                        borderColor: 'rgba(26, 107, 179, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // Horizontal bar chart
                    scales: { x: { beginAtZero: true } },
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });
        }

        createEnrollmentChart('enrollmentChartDashboard');
        createEnrollmentChart('enrollmentChartAnalytics');
    });
</script>
