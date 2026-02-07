<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h1><i class="fas fa-calendar-alt"></i> SchedMaster</h1>
    </div>
    
    <!-- Super Admin Sidebar -->
    <div class="menu-section">
        <h3>Main Menu</h3>
        <ul class="menu-items">
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('dashboard-home', this, 'view_admin_dashboard')"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('manage-admin', this, 'manage_instructors,manage_students')"><i class="fas fa-users-cog"></i> User Management</a></li>
            <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['superadmin', 'admin'])): ?>
                <li><a href="manage_announcements.php" class="nav-link"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <?php endif; ?>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('analytics', this, 'view_admin_dashboard')"><i class="fas fa-chart-pie"></i> Analytics</a></li>
        </ul>
    </div>
    <div class="menu-section">
        <h3>Administration</h3>
        <ul class="menu-items">
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('settings', this, 'manage_system_settings')"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('permissions', this, 'manage_role_permissions')"><i class="fas fa-user-shield"></i> Permissions</a></li>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('modules', this, 'manage_system_modules')"><i class="fas fa-puzzle-piece"></i> Modules</a></li>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('database-control', this, 'manage_database')"><i class="fas fa-database"></i> Database Control</a></li>
            <li><a href="#" class="nav-link" onclick="showContentWithPermission('view-logs', this, 'view_admin_dashboard')"><i class="fas fa-clipboard-list"></i> System Logs</a></li>
        </ul>
    </div>
    <div class="menu-section">
        <h3>Account</h3>
        <ul class="menu-items">
            <li><a href="../../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>
<style>
    .sidebar .menu-items a {
        color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; width: 100%; height: 100%;
    }
</style>