<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if ($_SESSION['role'] !== 'instructor' || !has_permission('instructor', 'view_instructor_schedule', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}

$page_title = 'My Schedule';
$user_id = $_SESSION['user_id'];

// --- Fetch Instructor's ID and Name ---
$stmt_instructor = $conn->prepare("SELECT instructor_id, CONCAT(first_name, ' ', last_name) AS full_name FROM instructors WHERE user_id = ?");
$stmt_instructor->bind_param("i", $user_id);
$stmt_instructor->execute();
$instructor_result = $stmt_instructor->get_result();
$instructor_info = $instructor_result->fetch_assoc();
$instructor_id = $instructor_info['instructor_id'] ?? 0;
$instructor_name = $instructor_info['full_name'] ?? $_SESSION['username'];
$stmt_instructor->close();

// --- Fetch Schedule for the logged-in instructor ---
$schedules = [];
$current_sy = get_active_sy($conn);
$current_sem = get_active_semester($conn);

if ($instructor_id > 0) {
    // --- Create semester variants for a more robust query ---
    $semester_short = '1st';
    $semester_long = '1st Semester';
    if (stripos($current_sem, '2') !== false) {
        $semester_short = '2nd';
        $semester_long = '2nd Semester';
    }

    $scheduleSql = "SELECT 
                        sch.day, 
                        sch.time_start, 
                        sch.time_end, 
                        TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start_formatted, 
                        TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end_formatted, 
                        sub.subject_code, 
                        sub.subject_name, 
                        sub.units, 
                        sec.section_name,
                        rm.room_name 
                    FROM schedules sch 
                    LEFT JOIN subjects sub ON sch.subject_id = sub.subject_id 
                    LEFT JOIN sections sec ON sch.section_id = sec.section_id
                    LEFT JOIN rooms rm ON sch.room_id = rm.room_id 
                    WHERE sch.instructor_id = ? 
                      AND sch.school_year = ? 
                      AND (sch.semester = ? OR sch.semester = ?) 
                    ORDER BY sch.day, sch.time_start";

    $stmt_schedule = $conn->prepare($scheduleSql);
    $stmt_schedule->bind_param("isss", $instructor_id, $current_sy, $semester_short, $semester_long);
    $stmt_schedule->execute();
    $scheduleResult = $stmt_schedule->get_result();

    if ($scheduleResult) {
        while($row = $scheduleResult->fetch_assoc()) {
            $schedules[] = $row;
        }
    }
    $stmt_schedule->close();
}

// --- Color Coding for Subjects ---
$subject_colors = [];
if (!empty($schedules)) {
    $color_palette = ['#d1e7dd', '#f8d7da', '#cce5ff', '#fff3cd', '#e2d9f3', '#d1ecf1', '#fadadd'];
    $color_index = 0;
    $unique_subjects = array_unique(array_column($schedules, 'subject_code'));
    foreach ($unique_subjects as $subject_code) {
        $subject_colors[$subject_code] = $color_palette[$color_index % count($color_palette)];
        $color_index++;
    }
}

// --- Prepare Schedule Grid Data ---
$schedule_grid = [];
if (!empty($schedules)) {
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
                            'section_name' => $class['section_name'] ?? 'TBA',
                            'room_name' => $class['room_name'] ?? 'TBA',
                            'color' => $subject_colors[$class['subject_code']] ?? '#f8f9fa'
                        ];
                        $is_first_slot = false;
                    } else {
                        $schedule_grid[$slot][$day]['status'] = 'covered';
                    }
                }
            }
        }
    }
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
    <link rel="stylesheet" href="../../assets/css/my_schedule.css">
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><?= $page_title ?></h2>
                <?php include '../../includes/user_info_header.php'; ?>
            </div>
            <div class="card">
                <?php if (!empty($schedules)): ?>
                    <div class="schedule-header">
                        <div class="schedule-header-info">
                            <div><strong>Instructor:</strong> <?= htmlspecialchars($instructor_name) ?></div>
                            <div><strong>S.Y. / Semester:</strong> <?= htmlspecialchars($current_sy) ?> / <?= htmlspecialchars($current_sem) ?></div>
                        </div>
                        <div class="schedule-header-actions">
                            <button class="button" onclick="window.print();"><i class="fas fa-print"></i> Print Schedule</button>
                        </div>
                    </div>

                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th class="time-column">Time</th>
                                <?php foreach ($days as $day): ?><th><?= htmlspecialchars($day) ?></th><?php endforeach; ?>
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
                                                <small><?= htmlspecialchars($cell['content']['section_name']) ?> | <?= htmlspecialchars($cell['content']['room_name']) ?></small>
                                            </td>
                                        <?php endif; // 'covered' cells are not rendered ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="subject-legend">
                        <h4>Subject Details</h4>
                        <table class="subject-legend-table">
                            <thead><tr><th>Subject Code & Name</th><th>Section</th><th>Schedule</th></tr></thead>
                            <tbody>
                                <?php foreach ($schedules as $class): ?>
                                <tr>
                                    <td><span class="color-swatch" style="background-color: <?= $subject_colors[$class['subject_code']] ?>;"></span><strong><?= htmlspecialchars($class['subject_code']) ?></strong> - <?= htmlspecialchars($class['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($class['section_name']) ?></td>
                                    <td><?= htmlspecialchars($class['day']) ?>, <?= htmlspecialchars($class['time_start_formatted']) ?> - <?= htmlspecialchars($class['time_end_formatted']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; color: var(--primary); margin-bottom: 20px;"></i>
                        <h2>No Schedule Found</h2>
                        <p>You do not have any classes scheduled for the current semester (<?= htmlspecialchars($current_sy) ?>, <?= htmlspecialchars($current_sem) ?>).</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>