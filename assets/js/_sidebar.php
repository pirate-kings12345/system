<!-- Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h1><i class="fas fa-calendar-alt"></i> SchedMaster</h1>
    </div>
    
    <!-- Super Admin Sidebar -->
    <div class="menu-section">
        <h3>Main Menu</h3>
        <ul class="menu-items">
            <li><a href="#" class="nav-link" onclick="showContent('dashboard-home', this)"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('manage-admin', this)"><i class="fas fa-user-shield"></i> Manage Admins</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('manage-instructors', this)"><i class="fas fa-chalkboard-teacher"></i> Manage Instructors</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('announcements', this)"><i class="fas fa-bullhorn"></i> Announcements</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('analytics', this)"><i class="fas fa-chart-pie"></i> Analytics</a></li>
        </ul>
    </div>
    <div class="menu-section">
        <h3>Administration</h3>
        <ul class="menu-items">
            <li><a href="manage_admin_permissions.php" class="nav-link"><i class="fas fa-user-shield"></i> Admin Assignments</a></li>
            <li><a href="manage_role_permissions.php" class="nav-link"><i class="fas fa-tasks"></i> Role Permissions</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('settings', this)"><i class="fas fa-cog"></i> Settings</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('modules', this)"><i class="fas fa-puzzle-piece"></i> Modules</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('create-backup', this)"><i class="fas fa-database"></i> Database Control</a></li>
            <li><a href="#" class="nav-link" onclick="showContent('view-logs', this)"><i class="fas fa-clipboard-list"></i> System Logs</a></li>
        </ul>
    </div>
    <div class="menu-section">
        <h3>Account</h3>
        <ul class="menu-items">
            <li><a href="../../auth/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>