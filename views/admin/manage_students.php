<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Only superadmin and admin can access this page
$allowed_roles = ['superadmin', 'admin'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    header("Location: ../../auth/login.php");
    exit();
}

$page_title = 'Manage Students';

// Fetch module status for 'module_registration'
$is_registration_module_active = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_registration'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    if ($setting['setting_value'] == '1') {
        $is_registration_module_active = true;
    }
}

// Fetch existing students (simplified for this example)
$students = [];
$sql = "SELECT s.student_id, u.username, u.email, u.status FROM students s JOIN users u ON s.user_id = u.user_id ORDER BY u.username";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
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
        <?php include '../superadmin/_sidebar.php'; // Assuming superadmin sidebar for now ?>
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-user-graduate"></i> <?= htmlspecialchars($page_title) ?></h2>
                <?php include '../../includes/user_info_header.php'; ?>
            </div>

            <?php if (!$is_registration_module_active): ?>
                <div class="alert error" style="margin-bottom: 20px;">
                    <i class="fas fa-exclamation-triangle"></i> The "Public Registration" module is currently disabled. You cannot add new student accounts while this module is off.
                </div>
                <button class="button" onclick="alert('Cannot add new students. The registration module is currently disabled.');"><i class="fa-solid fa-plus"></i> Add New Student</button>
            <?php else: ?>
                <button class="button" onclick="openAddStudentModal()"><i class="fa-solid fa-plus"></i> Add New Student</button>
            <?php endif; ?>

            <div class="card" style="margin-top: 2rem;">
                <h3>Existing Students</h3>
                <table class="data-table">
                    <thead><tr><th>Username</th><th>Email</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!empty($students)): foreach ($students as $student): ?>
                            <tr><td><?= htmlspecialchars($student['username']) ?></td><td><?= htmlspecialchars($student['email']) ?></td><td><?= htmlspecialchars($student['status']) ?></td><td><a href="#">Edit</a> | <a href="#">Deactivate</a></td></tr>
                        <?php endforeach; else: ?><tr><td colspan="4" style="text-align:center;">No students found.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>