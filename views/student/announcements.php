<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';
if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn)) { // Announcements are part of the dashboard view
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}
$page_title = 'Announcements';

// Fetch announcements
$announcements = [];
$sql = "SELECT title, content, date_posted FROM announcements WHERE target_role = 'all' OR target_role = 'student' ORDER BY date_posted DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) { $announcements[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <h2><?= $page_title ?></h2>
                </div>
                 <?php include '../../includes/user_info_header.php'; ?>
            </div>
            <div class="content-section active">
                <h3>Recent Announcements</h3>
                <ul class="activity-list">
                    <?php if (!empty($announcements)): foreach ($announcements as $announcement): ?>
                        <li style="align-items: flex-start;"><div class="activity-icon" style="margin-top: 4px;"><i class="fas fa-bullhorn"></i></div><div><strong><?= htmlspecialchars($announcement['title']) ?></strong><br><?= nl2br(htmlspecialchars($announcement['content'])) ?><br><small style="color: var(--gray);">Posted on: <?= date('F j, Y, g:i a', strtotime($announcement['date_posted'])) ?></small></div></li>
                    <?php endforeach; else: ?><li>No announcements found.</li><?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            const container = document.querySelector('.container');

            // Function to toggle sidebar
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                container.classList.toggle('sidebar-collapsed');
                localStorage.setItem('sidebarCollapsed', container.classList.contains('sidebar-collapsed'));
            });

            // Check local storage on page load
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                container.classList.add('sidebar-collapsed');
                sidebar.classList.add('collapsed');
            }
        });
    </script>
</body>
</html>