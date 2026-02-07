<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

// Security check for superadmin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../../auth/login.php");
    exit;
}

$page_title = 'Admin Permissions';

// Fetch all non-superadmin admins
$admins = [];
$admin_sql = "SELECT user_id, username, email FROM users WHERE role = 'admin' ORDER BY username";
$admin_result = $conn->query($admin_sql);
while ($row = $admin_result->fetch_assoc()) {
    $admins[] = $row;
}

// Fetch all courses
$courses = [];
$course_sql = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
$course_result = $conn->query($course_sql);
while ($row = $course_result->fetch_assoc()) {
    $courses[] = $row;
    $course_map[$row['course_id']] = $row; // For easy lookup
}

// Fetch current assignments
$assignments = [];
$assignment_sql = "SELECT user_id, course_id FROM admin_course_assignments";
$assignment_result = $conn->query($assignment_sql);
while ($row = $assignment_result->fetch_assoc()) {
    $assignments[$row['user_id']][] = $row['course_id'];
}

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
    <style>
        .permissions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .permissions-table th, .permissions-table td {
            border: 1px solid var(--light-gray);
            padding: 12px;
            text-align: center;
            vertical-align: middle;
        }
        .permissions-table th {
            background-color: #f8f9fa;
        }
        .permissions-table td:first-child {
            text-align: left;
            font-weight: 600;
        }
        .permissions-table th.course-header {
            writing-mode: vertical-rl;
            text-orientation: mixed;
            white-space: nowrap;
            padding: 15px 8px;
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-user-shield"></i> <?= htmlspecialchars($page_title) ?></h2>
            </div>

            <?php if (isset($_SESSION['permission_msg'])): ?>
                <div class="alert-message <?= $_SESSION['permission_msg_type'] === 'success' ? 'success' : 'error' ?>">
                    <?= $_SESSION['permission_msg']; ?>
                </div>
                <?php unset($_SESSION['permission_msg'], $_SESSION['permission_msg_type']); ?>
            <?php endif; ?>

            <div class="card">
                <h3>Assign Course Management to Admins</h3>
                <p>Use the toggles to grant or revoke an admin's permission to manage a specific course. This includes managing sections, schedules, and enrollments for that course.</p>
                <form action="../actions/save_admin_permissions.php" method="POST">
                    <div class="table-responsive">
                        <table class="permissions-table">
                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <?php foreach ($courses as $course): ?>
                                        <th class="course-header" title="<?= htmlspecialchars($course['course_name']) ?>"><?= htmlspecialchars($course['course_code']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($admin['username']) ?></td>
                                        <?php foreach ($courses as $course): ?>
                                            <td>
                                                <label class="toggle-switch">
                                                    <input type="checkbox" name="assignments[<?= $admin['user_id'] ?>][]" value="<?= $course['course_id'] ?>" 
                                                        <?= (isset($assignments[$admin['user_id']]) && in_array($course['course_id'], $assignments[$admin['user_id']])) ? 'checked' : '' ?>>
                                                    <span class="slider"></span>
                                                </label>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: right; margin-top: 2rem;">
                        <button type="submit" class="button"><i class="fas fa-save"></i> Save Permissions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>