<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Allow both superadmin and admin to access this page
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: " . ($_SESSION['role'] === 'student' ? '../student/dashboard.php' : '../../auth/login.php'));
    exit();
}

$page_title = 'Manage Announcements';

// Fetch existing announcements
$announcements = [];
$sql = "SELECT announcement_id, title, content, target_role, date_posted FROM announcements ORDER BY date_posted DESC";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; // Assuming superadmin has a sidebar include ?>
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-bullhorn"></i> <?= htmlspecialchars($page_title) ?></h2>
                <?php include '../../includes/user_info_header.php'; ?>
            </div>

            <?php if (isset($_SESSION['alert'])): ?>
                <div class="alert-message <?= $_SESSION['alert']['type'] ?>">
                    <?= $_SESSION['alert']['message'] ?>
                    <span class="close-alert" onclick="this.parentElement.style.display='none';">&times;</span>
                </div>
                <?php unset($_SESSION['alert']); ?>
            <?php endif; ?>

            <div class="card">
                <h3>Create New Announcement</h3>
                <form action="../superadmin/announcement_handler.php" method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="form-input-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div class="form-input-group">
                        <label for="content">Content</label>
                        <textarea id="content" name="content" rows="5" required></textarea>
                    </div>
                    <div class="form-input-group">
                        <label for="target_role">Broadcast To</label>
                        <select id="target_role" name="target_role">
                            <option value="all">All Users</option>
                            <option value="student">Students Only</option>
                            <option value="instructor">Instructors Only</option>
                            <option value="admin">Admins Only</option>
                        </select>
                    </div>
                    <button type="submit" class="button">Post Announcement</button>
                </form>
            </div>

            <div class="card" style="margin-top: 2rem;">
                <h3>Posted Announcements</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Broadcast To</th>
                            <th>Date Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($announcements)): ?>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td><?= htmlspecialchars($announcement['title']) ?></td>
                                    <td><?= ucfirst(htmlspecialchars($announcement['target_role'])) ?></td>
                                    <td><?= date('F j, Y, g:i a', strtotime($announcement['date_posted'])) ?></td>
                                    <td class="action-links">
                                        <form action="../superadmin/announcement_handler.php" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="announcement_id" value="<?= $announcement['announcement_id'] ?>">
                                            <button type="submit" style="background:none; border:none; color: #e74c3c; cursor:pointer; padding:0;"><i class="fas fa-trash"></i> Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No announcements have been posted yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>