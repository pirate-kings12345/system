<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php'; // Needed for get_active_sy and get_active_semester
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student' || !has_permission('student', 'submit_enrollment', $conn)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'You do not have permission to access the enrollment page.'];
    // Redirect back to the dashboard or the previous page
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit();
}

// Fetch enrollment module status from system settings
$is_enrollment_open = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_enrollment'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    if ($setting['setting_value'] == '1') {
        $is_enrollment_open = true;
    }
}

// --- Check for Existing Enrollment Request for the current term ---
$existing_enrollment = null;
$user_id = $_SESSION['user_id'];

// First, get the student_id from the user_id
$stmt_student = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
if ($student_info = $result_student->fetch_assoc()) {
    $student_id_to_check = $student_info['student_id'];
    $current_sy = get_active_sy($conn);
    $current_sem = get_active_semester($conn);

    $sql_check = "SELECT status FROM enrollments WHERE student_id = ? AND school_year = ? AND semester = ? ORDER BY date_submitted DESC LIMIT 1";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iss", $student_id_to_check, $current_sy, $current_sem);
    $stmt_check->execute();
    $existing_enrollment = $stmt_check->get_result()->fetch_assoc();
}
$page_title = 'Enrollment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css"> <!-- For card styles -->
    <style>
        .enrollment-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .enrollment-table th, .enrollment-table td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        .enrollment-table th { background-color: #f8f9fa; }
    </style>
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
            <?php if ($existing_enrollment && $existing_enrollment['status'] === 'approved'): ?>
                <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #2ecc71; margin-bottom: 20px;"></i>
                    <h2>You are Already Enrolled!</h2>
                    <p style="max-width: 500px; margin: 1rem auto;">Your enrollment for the current semester has been approved.</p>
                    <p>You can view your schedule on the <a href="my_schedule.php">My Schedule</a> page.</p>
                </div>
            <?php elseif ($is_enrollment_open): ?>
                 <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                     <?php if ($existing_enrollment && $existing_enrollment['status'] === 'pending'): ?>
                         <i class="fas fa-hourglass-half" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                         <h2>Your Enrollment is Pending</h2>
                         <p style="max-width: 500px; margin: 1rem auto;">Your enrollment request is currently being reviewed by the administration.</p>
                     <?php else: ?>
                        <i class="fas fa-door-open" style="font-size: 48px; color: #2ecc71; margin-bottom: 20px;"></i>
                        <h2>Enrollment is Now Open!</h2>
                        <p style="max-width: 500px; margin: 1rem auto;">You can now proceed with the enrollment process for the upcoming semester.</p>
                     <?php endif; ?>
                     <!-- Button to proceed to enrollment -->
                     <button id="proceedToEnrollmentBtn" class="button" style="margin-top: 1rem; background-color: #2ecc71;"><i class="fas fa-arrow-right"></i> Proceed to Enrollment</button>
                 </div>
            <?php elseif (!$is_enrollment_open): ?>
                <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                    <i class="fas fa-lock" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                    <h2>Enrollment is Currently Closed</h2>
                    <p style="max-width: 500px; margin: 1rem auto;">The enrollment period has not yet started or has already ended.</p>
                    <p style="max-width: 500px; margin: 0 auto;">Please wait for an announcement from the administration regarding the enrollment schedule.</p>
                </div>
            <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', function() {
        const proceedBtn = document.getElementById('proceedToEnrollmentBtn');
        if (proceedBtn) {
            proceedBtn.addEventListener('click', function(e) {
                <?php if ($existing_enrollment && $existing_enrollment['status'] === 'approved'): ?>
                    // If already approved, show an alert and do not navigate.
                    alert('This student is already enrolled.');
                <?php else: ?>
                    // For new, pending, or rejected enrollments, proceed to the form.
                    window.location.href = 'enrollment_form.php';
                <?php endif; ?>
            });
        }
    });
    </script>
</body>
</html>