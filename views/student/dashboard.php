<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure this is a student
if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn)) {
    session_unset();
    session_destroy();
    $_SESSION['error'] = "❌ Access to the dashboard is currently disabled by the administrator.";
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_display_name = $_SESSION['username'];

// Fetch active school year and semester from settings
$active_sy = get_active_sy($conn);
$active_semester = get_active_semester($conn);

// --- Initialize variables for the dashboard widgets ---
$schedules = [];
$student_section_id = null;
$enrollment_status = 'Not Enrolled';
$profile_completion_percentage = 0;
$current_gwa = null;
$announcements = [];

// 1. Find the student_id from the user_id
$stmt = $conn->prepare("SELECT s.student_id, sp.full_name FROM students s LEFT JOIN student_profiles sp ON s.user_id = sp.user_id WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($student_row = $result->fetch_assoc()) {
    $student_id = $student_row['student_id'];
    $user_display_name = $student_row['full_name'] ?? $user_display_name;
    
    // 2. Find the student's approved enrollment for the current SY and Semester to get their section_id
    // We fetch the latest enrollment record to determine status (approved, pending, etc.)
    $enrollment_stmt = $conn->prepare(
        "SELECT e.section_id, e.status 
         FROM enrollments e
         WHERE e.student_id = ? AND e.school_year = ? AND e.semester = ?
         ORDER BY e.date_submitted DESC LIMIT 1"
    );
    $enrollment_stmt->bind_param("iss", $student_id, $active_sy, $active_semester);
    $enrollment_stmt->execute();
    $enrollment_result = $enrollment_stmt->get_result();
    if ($enrollment_row = $enrollment_result->fetch_assoc()) {
        $enrollment_status = ucfirst($enrollment_row['status']);
        if ($enrollment_status === 'Approved') {
            $student_section_id = $enrollment_row['section_id'];
        }
    }
    $enrollment_stmt->close();
}
$stmt->close();

// --- Fetch Profile Completion Percentage ---
$profile_stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
if ($student_profile = $profile_result->fetch_assoc()) {
    $fields_to_check = ['full_name', 'date_of_birth', 'gender', 'address', 'contact_number', 'father_full_name', 'mother_full_name', 'high_school'];
    $total_fields = count($fields_to_check);
    $filled_fields = 0;
    foreach ($fields_to_check as $field) {
        if (!empty($student_profile[$field])) {
            $filled_fields++;
        }
    }
    $profile_completion_percentage = ($total_fields > 0) ? round(($filled_fields / $total_fields) * 100) : 0;
}
$profile_stmt->close();

// --- Fetch Current GWA for the active term ---
if ($enrollment_status === 'Approved') {
    $gwa_sql = "SELECT sg.final_grade, sub.units
                FROM student_grades sg
                JOIN enrollments e ON sg.enrollment_id = e.enrollment_id
                JOIN subjects sub ON sg.subject_id = sub.subject_id
                WHERE e.student_id = ? AND e.school_year = ? AND e.semester = ?";
    $gwa_stmt = $conn->prepare($gwa_sql);
    $gwa_stmt->bind_param("iss", $student_id, $active_sy, $active_semester);
    $gwa_stmt->execute();
    $gwa_result = $gwa_stmt->get_result();

    $total_units = 0;
    $total_grade_points = 0;
    $has_grades = false;
    while ($grade_row = $gwa_result->fetch_assoc()) {
        if ($grade_row['final_grade'] !== null) {
            $has_grades = true;
            $total_units += $grade_row['units'];
            $total_grade_points += $grade_row['final_grade'] * $grade_row['units'];
        }
    }
    if ($has_grades && $total_units > 0) {
        $current_gwa = round($total_grade_points / $total_units, 2);
    }
    $gwa_stmt->close();
}

// --- Fetch Recent Announcements ---
$announcement_sql = "SELECT title, content, date_posted FROM announcements WHERE target_role = 'all' OR target_role = 'student' ORDER BY date_posted DESC LIMIT 2";
$announcement_result = $conn->query($announcement_sql);
while ($announcement_row = $announcement_result->fetch_assoc()) { $announcements[] = $announcement_row; }

// 3. If a section was found, fetch all schedules for that section
if ($student_section_id) {
    // --- Create semester variants for a more robust query ---
    $semester_short = '1st';
    $semester_long = '1st Semester';
    if (stripos($active_semester, '2') !== false) {
        $semester_short = '2nd';
        $semester_long = '2nd Semester';
    }

    $scheduleSql = "SELECT 
                        sch.schedule_id,
                        sub.subject_code,
                        sub.subject_name,
                        CONCAT(ins.first_name, ' ', ins.last_name) AS instructor_name,
                        sch.day,
                        TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start_formatted,
                        TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end_formatted,
                        rm.room_name
                    FROM schedules AS sch
                    LEFT JOIN subjects AS sub ON sch.subject_id = sub.subject_id
                    LEFT JOIN instructors AS ins ON sch.instructor_id = ins.instructor_id
                    LEFT JOIN rooms AS rm ON sch.room_id = rm.room_id
                    WHERE sch.section_id = ? AND sch.school_year = ? AND (sch.semester = ? OR sch.semester = ?)
                    ORDER BY sch.day, sch.time_start";

    $schedule_stmt = $conn->prepare($scheduleSql);
    $schedule_stmt->bind_param("isss", $student_section_id, $active_sy, $semester_short, $semester_long);
    $schedule_stmt->execute();
    $scheduleResult = $schedule_stmt->get_result();
    if ($scheduleResult && $scheduleResult->num_rows > 0) {
        while ($row = $scheduleResult->fetch_assoc()) {
            $schedules[] = $row;
        }
    }
    $schedule_stmt->close();
}

$page_title = 'Dashboard';
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
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 2rem; }
        .widget-card { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .widget-card h4 { margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; color: var(--dark-gray); }
        .widget-card .value { font-size: 2rem; font-weight: 700; color: var(--primary); }
        .widget-card .desc { font-size: 0.9rem; color: var(--gray); }
        .progress-bar { background-color: #e9ecef; border-radius: 10px; height: 10px; width: 100%; overflow: hidden; margin-top: 5px; }
        .progress-bar-inner { background-color: var(--success); height: 100%; border-radius: 10px; transition: width 0.5s ease; }
        .announcement-item { border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 10px; }
        .announcement-item:last-child { border-bottom: none; margin-bottom: 0; }
        .announcement-item strong { display: block; margin-bottom: 4px; }
        .announcement-item small { color: var(--gray); }
        .status-approved { color: var(--success); }
        .status-pending { color: var(--warning); }
        .status-not-enrolled { color: var(--danger); }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left">
                    <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <h2 id="main-header-title">Welcome, <?= htmlspecialchars(explode(' ', $user_display_name)[0]) ?>!</h2>
                </div>
                 <?php include '../../includes/user_info_header.php'; ?>
            </div>

            <div class="dashboard-grid">
                <!-- Profile Completion Widget -->
                <div class="widget-card">
                    <h4><i class="fas fa-user-check"></i> Profile Completion</h4>
                    <div class="value"><?= $profile_completion_percentage ?>%</div>
                    <div class="progress-bar">
                        <div class="progress-bar-inner" style="width: <?= $profile_completion_percentage ?>%;"></div>
                    </div>
                    <p class="desc">Complete your <a href="personal_info.php">personal info</a> to reach 100%.</p>
                </div>

                <!-- Enrollment Status Widget -->
                <div class="widget-card">
                    <h4><i class="fas fa-clipboard-check"></i> Enrollment Status</h4>
                    <div class="value status-<?= strtolower(str_replace(' ', '-', $enrollment_status)) ?>"><?= htmlspecialchars($enrollment_status) ?></div>
                    <p class="desc">For S.Y. <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_semester) ?></p>
                </div>

                <!-- GWA Widget -->
                <div class="widget-card">
                    <h4><i class="fas fa-graduation-cap"></i> Current GWA</h4>
                    <?php if ($current_gwa !== null): ?>
                        <div class="value"><?= number_format($current_gwa, 2) ?></div>
                        <p class="desc">General Weighted Average for this semester.</p>
                    <?php else: ?>
                        <div class="value" style="font-size: 1.5rem;">N/A</div>
                        <p class="desc">Grades for the current semester are not yet available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; align-items: flex-start;">
                <!-- Schedule Table -->
                <div class="card" style="margin-top: 2rem;">
                    <h3>Class Schedule</h3>
                    <p>Your class schedule for School Year <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_semester) ?> Semester.</p>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Subject Code</th>
                                <th>Subject Name</th>
                                <th>Day & Time</th>
                                <th>Room</th>
                                <th>Instructor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schedules)): ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($schedule['subject_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($schedule['subject_name']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($schedule['day']) ?><br>
                                            <small><?= htmlspecialchars($schedule['time_start_formatted']) ?> - <?= htmlspecialchars($schedule['time_end_formatted']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($schedule['room_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($schedule['instructor_name'] ?? 'TBA') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; padding: 2rem;">
                                        <?php if ($student_section_id): ?>
                                            No schedule has been posted for your section yet. Please check back later.
                                        <?php else: ?>
                                            You are not currently enrolled in a section for this semester, or your enrollment is pending approval.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Announcements Widget -->
                <div class="card" style="margin-top: 2rem;">
                    <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                <small>Posted on: <?= date('F j, Y', strtotime($announcement['date_posted'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                        <a href="announcements.php" style="display: block; margin-top: 15px; text-align: right;">View all...</a>
                    <?php else: ?>
                        <p class="desc">No recent announcements.</p>
                    <?php endif; ?>
                </div>
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
<?php
$conn->close();
?>