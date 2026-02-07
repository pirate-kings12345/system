<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn) || !has_permission('student', 'view_student_schedule', $conn)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'You do not have permission to view your schedule.'];
    // Redirect back to the dashboard or the previous page
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit();
}

$user_id = $_SESSION['user_id'];
$user_display_name = $_SESSION['username'];

// Fetch active school year and semester from settings
$current_sy = get_active_sy($conn);
$current_sem = get_active_semester($conn);

$student_info = null;
$schedules = [];
$student_section_id = null;
$enrollment_status = 'not_enrolled';

// 1. Find the student_id from the user_id
$stmt = $conn->prepare(
    "SELECT s.student_id, sp.full_name, c.course_name, s.year_level 
     FROM students s 
     LEFT JOIN student_profiles sp ON s.user_id = sp.user_id 
     LEFT JOIN courses c ON s.course_id = c.course_id 
     WHERE s.user_id = ?"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($student_row = $result->fetch_assoc()) {
    $student_id = $student_row['student_id'];
    
    // Fetch student info for display. We get year level from the enrollment later if possible.
    $student_info = [
        'course_name' => $student_row['course_name'],
        'year_level' => $student_row['year_level'] // Fallback year level
    ];

    $user_display_name = $student_row['full_name'] ?? $_SESSION['username'];

    // 2. Find the student's approved enrollment for the current SY and Semester to get their section_id
    $enrollment_stmt = $conn->prepare(
        "SELECT e.section_id, e.status, sec.year_level 
         FROM enrollments e
         LEFT JOIN sections sec ON e.section_id = sec.section_id
         WHERE e.student_id = ? AND e.school_year = ? AND e.semester = ? AND e.status = 'approved'
         ORDER BY e.date_submitted DESC LIMIT 1"
    );
    $enrollment_stmt->bind_param("iss", $student_id, $current_sy, $current_sem);
    $enrollment_stmt->execute();
    $enrollment_result = $enrollment_stmt->get_result();

    if ($enrollment_row = $enrollment_result->fetch_assoc()) {
        $enrollment_status = $enrollment_row['status'];
        if ($enrollment_status === 'approved') {
            $student_section_id = $enrollment_row['section_id']; // This will always be true now, but is safe to keep
            $student_info['year_level'] = $enrollment_row['year_level']; // Use year level from the enrolled section
        }
    }
    $enrollment_stmt->close();
}
$stmt->close();

// --- Fetch Schedule if Enrolled ---
if ($student_section_id) {
    // --- Create semester variants for a more robust query ---
    $semester_short = '1st';
    $semester_long = '1st Semester';
    if (stripos($current_sem, '2') !== false) {
        $semester_short = '2nd';
        $semester_long = '2nd Semester';
    }

    $scheduleSql = "SELECT sch.day, sch.time_start, sch.time_end, 
                           TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start_formatted, 
                           TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end_formatted, 
                           sub.subject_code, sub.subject_name, sub.units, 
                           CONCAT(ins.first_name, ' ', ins.last_name) AS instructor_name, 
                           rm.room_name 
                    FROM schedules sch 
                    LEFT JOIN subjects sub ON sch.subject_id = sub.subject_id 
                    LEFT JOIN instructors ins ON sch.instructor_id = ins.instructor_id 
                    LEFT JOIN rooms rm ON sch.room_id = rm.room_id 
                    WHERE sch.section_id = ? AND sch.school_year = ? AND (sch.semester = ? OR sch.semester = ?)
                    ORDER BY sch.day, sch.time_start";

    $stmt_schedule = $conn->prepare($scheduleSql);
    $stmt_schedule->bind_param("isss", $student_section_id, $current_sy, $semester_short, $semester_long);
    $stmt_schedule->execute();
    $scheduleResult = $stmt_schedule->get_result();

    if ($scheduleResult && $scheduleResult->num_rows > 0) {
        while($row = $scheduleResult->fetch_assoc()) {
            $schedules[] = $row;
        }
    }
    $stmt_schedule->close();

    // --- Color Coding for Subjects ---
    $subject_colors = [];
    $color_palette = ['#d1e7dd', '#f8d7da', '#cce5ff', '#fff3cd', '#e2d9f3', '#d1ecf1', '#fadadd'];
    $color_index = 0;
    $unique_subjects = array_unique(array_column($schedules, 'subject_code'));
    foreach ($unique_subjects as $subject_code) {
        $subject_colors[$subject_code] = $color_palette[$color_index % count($color_palette)];
        $color_index++;
    }

    // --- Prepare Schedule Grid Data ---
    $schedule_grid = [];
    $time_slots = [];
    $start_time = strtotime('07:00');
    $end_time = strtotime('21:00');

    while ($start_time <= $end_time) {
        $time_slots[] = date('H:i', $start_time);
        $start_time = strtotime('+30 minutes', $start_time);
    }

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    // Initialize grid
    foreach ($time_slots as $slot) {
        foreach ($days as $day) {
            $schedule_grid[$slot][$day] = ['status' => 'free', 'content' => null, 'rowspan' => 1];
        }
    }

    // Populate grid with schedules
    foreach ($schedules as $class) {
        $class_start_time = strtotime($class['time_start']);
        $class_end_time = strtotime($class['time_end']);
        $day = $class['day'];

        if (in_array($day, $days)) {
            // Calculate duration and rowspan
            $duration_minutes = ($class_end_time - $class_start_time) / 60;
            $rowspan = $duration_minutes / 30;

            $is_first_slot = true;
            foreach ($time_slots as $slot) {
                $slot_time = strtotime($slot);

                if ($slot_time >= $class_start_time && $slot_time < $class_end_time) {
                    if ($is_first_slot) {
                        $schedule_grid[$slot][$day]['status'] = 'occupied';
                        $schedule_grid[$slot][$day]['rowspan'] = $rowspan;
                        $schedule_grid[$slot][$day]['content'] = [
                            'subject_code' => $class['subject_code'],
                            'room_name' => $class['room_name'] ?? 'TBA',
                            'instructor_name' => $class['instructor_name'] ?? 'TBA',
                            'color' => $subject_colors[$class['subject_code']] ?? '#f8f9fa'
                        ];
                        $is_first_slot = false;
                    } else {
                        $schedule_grid[$slot][$day]['status'] = 'covered'; // This slot is covered by a rowspan
                    }
                }
            }
        }
    }
}

$page_title = 'My Schedule';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css"> <!-- Base styles -->
    <link rel="stylesheet" href="../../assets/css/admin.css"> <!-- For button styles -->
    <style>
        .schedule-table { width: 100%; border-collapse: collapse; margin-top: 20px; table-layout: fixed; }
        .schedule-table th, .schedule-table td { border: 1px solid #e9ecef; padding: 8px; text-align: center; font-size: 12px; height: 35px; }
        .schedule-table th { background-color: #f2f2f2; font-weight: bold; }
        .time-column { width: 100px; background-color: #f9f9f9; font-weight: bold; }
        .class-cell { background-color: #e6f7ff; font-weight: bold; vertical-align: middle; border-left: 4px solid var(--primary); }
        .class-cell small { font-size: 11px; color: #333; display: block; font-weight: normal; }
        @media (max-width: 992px) {
            .schedule-table { font-size: 10px; }
            .schedule-table th, .schedule-table td { padding: 4px; }
        }

        .schedule-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        .schedule-header-info div { margin-bottom: 5px; }
        .schedule-header-info strong { color: var(--primary); }

        .subject-legend { margin-top: 2rem; }
        .subject-legend h4 { margin-bottom: 1rem; }
        .subject-legend-table { width: 100%; border-collapse: collapse; }
        .subject-legend-table th, .subject-legend-table td { text-align: left; padding: 10px; border-bottom: 1px solid #e9ecef; }
        .subject-legend-table th { background-color: #f8f9fa; }
        .color-swatch { display: inline-block; width: 15px; height: 15px; border-radius: 3px; margin-right: 10px; vertical-align: middle; }

        @media print {
            body, .container { background: #fff; display: block; }
            .sidebar, .header, .schedule-header-actions { display: none; }
            .main-content { padding: 0; }
            .card { box-shadow: none; border: 1px solid #ddd; }
            .schedule-header { margin-bottom: 1rem; padding-bottom: 0.5rem; }
            .schedule-table { margin-top: 1rem; }
            .subject-legend { 
                margin-top: 1.5rem; 
                page-break-before: auto; /* Avoid breaking the page right before the legend if possible */
                page-break-inside: avoid; /* Try to keep the whole legend table on one page */
            }
            .subject-legend-table td, .subject-legend-table th { padding: 6px; }
        }
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

            <?php if ($student_section_id && !empty($schedules)): ?>
                <div class="card">
                    <div class="schedule-header">
                        <div class="schedule-header-info">
                            <div><strong>Student:</strong> <?= htmlspecialchars($user_display_name) ?></div>
                            <div><strong>Program:</strong> <?= htmlspecialchars($student_info['course_name']) ?> - <?= htmlspecialchars($student_info['year_level']) ?></div>
                            <div><strong>S.Y. / Semester:</strong> <?= htmlspecialchars($current_sy) ?> / <?= htmlspecialchars($current_sem) ?></div>
                        </div>
                        <div class="schedule-header-actions">
                            <button class="button" onclick="window.print();"><i class="fas fa-print"></i> Print Schedule</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="schedule-table">
                            <thead>
                                <tr>
                                    <th class="time-column">Time</th>
                                    <?php foreach ($days as $day): ?>
                                        <th><?= htmlspecialchars($day) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($time_slots as $slot): ?>
                                    <tr>
                                        <td class="time-column"><?= date('h:i A', strtotime($slot)) ?></td>
                                        <?php foreach ($days as $day): ?>
                                            <?php $cell = $schedule_grid[$slot][$day]; ?>
                                            <?php if ($cell['status'] === 'free'): ?>
                                                <td></td>
                                            <?php elseif ($cell['status'] === 'occupied'): ?>
                                                <td class="class-cell" rowspan="<?= $cell['rowspan'] ?>" style="background-color: <?= $cell['content']['color'] ?>;">
                                                    <?= htmlspecialchars($cell['content']['subject_code']) ?><br>
                                                    <small><?= htmlspecialchars($cell['content']['room_name']) ?></small>
                                                </td>
                                            <?php endif; // 'covered' cells are simply not rendered ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="subject-legend">
                        <h4>Subject Details</h4>
                        <div class="table-responsive">
                            <table class="subject-legend-table">
                                <thead>
                                    <tr><th>Subject Code & Name</th><th>Units</th><th>Instructor</th><th>Schedule</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedules as $class): ?>
                                    <tr>
                                        <td><span class="color-swatch" style="background-color: <?= $subject_colors[$class['subject_code']] ?>;"></span><strong><?= htmlspecialchars($class['subject_code']) ?></strong> - <?= htmlspecialchars($class['subject_name']) ?></td>
                                        <td><?= htmlspecialchars($class['units'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($class['instructor_name']) ?></td>
                                        <td><?= htmlspecialchars($class['day']) ?>, <?= htmlspecialchars($class['time_start_formatted']) ?> - <?= htmlspecialchars($class['time_end_formatted']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php if ($enrollment_status === 'approved' && empty($schedules)): ?>
                    <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                        <i class="fas fa-check-circle" style="font-size: 48px; color: #2ecc71; margin-bottom: 20px;"></i>
                        <h2>Congratulations, you are officially enrolled!</h2>
                        <p style="max-width: 500px; margin: 1rem auto;">Your enrollment is approved. The administration is now finalizing your class schedule.</p>
                        <p style="max-width: 500px; margin: 0 auto;">Your schedule will appear here as soon as it's ready. Please check back soon.</p>
                    </div>
                <?php elseif ($enrollment_status === 'pending'): ?>
                    <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                        <i class="fas fa-hourglass-half" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h2>Your Enrollment is Pending</h2>
                        <p style="max-width: 500px; margin: 1rem auto;">Your enrollment request is currently being reviewed. Once approved, your schedule will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="card" style="margin-top: 2rem; text-align: center; padding: 40px;">
                        <i class="fas fa-info-circle" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h2>Your Schedule is Not Available.</h2>
                        <p style="max-width: 500px; margin: 1rem auto;">You are not yet fully enrolled in a section for the current semester.</p>
                        <p style="max-width: 500px; margin: 0 auto;">Once you are enrolled by the administration, your class schedule will appear here.</p>
                    </div>
                <?php endif; ?>
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
    </script>
</body>
</html>