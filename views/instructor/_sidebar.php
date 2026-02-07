<!-- Instructor Sidebar -->
<div class="sidebar">
    <div class="logo">
        <h1><i class="fas fa-calendar-alt"></i> SchedMaster</h1>
    </div>
    
    <div class="menu-section">
        <h3>Main Menu</h3>
        <ul class="menu-items">
            <li class="<?= ($page_title == 'Dashboard') ? 'active' : '' ?>"><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
            <li class="<?= ($page_title == 'My Schedule') ? 'active' : '' ?>"><a href="my_schedule.php"><i class="fas fa-calendar-alt"></i> My Schedule</a></li>
            <li class="<?= ($page_title == 'My Classes') ? 'active' : '' ?>"><a href="my_classes.php"><i class="fas fa-users"></i> My Classes</a></li>
            <li class="<?= ($page_title == 'Grade Management') ? 'active' : '' ?>"><a href="grade_management.php"><i class="fas fa-edit"></i> Grade Management</a></li>
            <li class="<?= ($page_title == 'Announcements') ? 'active' : '' ?>"><a href="announcements.php"><i class="fas fa-bullhorn"></i> Announcements</a></li>
        </ul>
    </div>
    
    <div class="menu-section">
        <h3>Account</h3>
        <ul class="menu-items">
            <li class="<?= ($page_title == 'My Profile') ? 'active' : '' ?>"><a href="my_profile.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li><a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
</div>