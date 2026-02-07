<?php
// This check ensures that we don't try to re-establish a DB connection if it's already open.
if (!isset($conn) || !$conn) {
    require_once __DIR__ . '/../../config/db_connect.php';
}

// Fetch enrollment module status to control sidebar link visibility
$is_enrollment_open = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_enrollment'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    $is_enrollment_open = ($setting['setting_value'] == '1');
}
// The connection should not be closed here, but at the end of the main script.
?>
<!-- Student Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h1><i class="fas fa-calendar-alt"></i> SchedMaster</h1>
    </div>
    
    <div class="menu-section">
        <h3>Main Menu</h3>
        <ul class="menu-items">
            <li class="<?= ($page_title == 'Dashboard') ? 'active' : '' ?>"><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="<?= ($page_title == 'My Schedule') ? 'active' : '' ?>"><a href="my_schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a></li>
            <li class="<?= ($page_title == 'Enrollment') ? 'active' : '' ?>"><a href="enrollment.php"><i class="fas fa-edit"></i> Enrollment</a></li>
            <li class="<?= ($page_title == 'Appointment') ? 'active' : '' ?>"><a href="appointment.php"><i class="fas fa-calendar-check"></i> Appointment</a></li>
            <li class="<?= ($page_title == 'My Grades') ? 'active' : '' ?>"><a href="my_grades.php"><i class="fas fa-star"></i> My Grades</a></li>
            <li class="<?= ($page_title == 'Announcements') ? 'active' : '' ?>"><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        </ul>
    </div>
    
    <div class="menu-section">
        <h3>Account</h3>
        <ul class="menu-items">
            <li class="<?= ($page_title == 'Personal Information') ? 'active' : '' ?>"><a href="personal_info.php"><i class="fas fa-id-card"></i> Personal Info</a></li>
            <li class="<?= ($page_title == 'My Profile') ? 'active' : '' ?>"><a href="my_profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>
<style>
    .sidebar .menu-items a { color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; width: 100%; height: 100%; }
</style>