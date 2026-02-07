<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if ($_SESSION['role'] !== 'instructor' || !has_permission('instructor', 'view_instructor_dashboard', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Fetch Instructor Details ---
$stmt = $conn->prepare("SELECT i.instructor_id, i.first_name, i.last_name FROM instructors i WHERE i.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();
$instructor_id = $instructor['instructor_id'];
$user_display_name = trim(($instructor['first_name'] ?? '') . ' ' . ($instructor['last_name'] ?? ''));
if (empty($user_display_name)) {
    $user_display_name = $_SESSION['username'];
}
$stmt->close();

// --- Fetch Active Term ---
$active_sy = get_active_sy($conn);
$active_semester = get_active_semester($conn);

// --- Dashboard Widgets Data ---
$total_classes = 0;
$total_students = 0;
$current_classes = [];

// --- Create semester variants for a more robust query ---
$semester_short = '1st';
$semester_long = '1st Semester';
if (stripos($active_semester, '2') !== false) {
    $semester_short = '2nd';
    $semester_long = '2nd Semester';
}

// Get total classes for the current term
$class_count_sql = "SELECT COUNT(DISTINCT schedule_id) as class_count FROM schedules WHERE instructor_id = ? AND school_year = ? AND (semester = ? OR semester = ?)";
$stmt = $conn->prepare($class_count_sql);
$stmt->bind_param("isss", $instructor_id, $active_sy, $semester_short, $semester_long);
$stmt->execute();
$total_classes = $stmt->get_result()->fetch_assoc()['class_count'] ?? 0;
$stmt->close();

// Get total unique students for the current term
$student_count_sql = "SELECT COUNT(DISTINCT e.student_id) as student_count
                      FROM schedules sch
                      JOIN enrollments e ON sch.section_id = e.section_id AND sch.school_year = e.school_year AND sch.semester = e.semester
                      WHERE sch.instructor_id = ? AND sch.school_year = ? AND (sch.semester = ? OR sch.semester = ?) AND e.status = 'approved'";
$stmt = $conn->prepare($student_count_sql);
$stmt->bind_param("isss", $instructor_id, $active_sy, $semester_short, $semester_long);
$stmt->execute();
$total_students = $stmt->get_result()->fetch_assoc()['student_count'] ?? 0;
$stmt->close();

// Get list of current classes
$classes_sql = "SELECT sub.subject_code, sub.subject_name, sec.section_name, crs.course_code, sec.year_level
                FROM schedules sch
                JOIN subjects sub ON sch.subject_id = sub.subject_id
                JOIN sections sec ON sch.section_id = sec.section_id
                JOIN courses crs ON sec.course_id = crs.course_id
                WHERE sch.instructor_id = ? AND sch.school_year = ? AND (sch.semester = ? OR sch.semester = ?)
                ORDER BY sub.subject_code";
$stmt = $conn->prepare($classes_sql);
$stmt->bind_param("isss", $instructor_id, $active_sy, $semester_short, $semester_long);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $current_classes[] = $row;
}
$stmt->close();

// --- Fetch Recent Announcements ---
$announcements = [];
$announcement_sql = "SELECT title, content, date_posted FROM announcements WHERE target_role = 'all' OR target_role = 'instructor' ORDER BY date_posted DESC LIMIT 2";
$announcement_result = $conn->query($announcement_sql);
while ($announcement_row = $announcement_result->fetch_assoc()) { $announcements[] = $announcement_row; }

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
        .widget-card .value { font-size: 2.5rem; font-weight: 700; color: var(--primary); }
        .widget-card .desc { font-size: 0.9rem; color: var(--gray); }
        .announcement-item { border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 10px; }
        .announcement-item:last-child { border-bottom: none; margin-bottom: 0; }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2>Welcome, <?= htmlspecialchars(explode(' ', $user_display_name)[0]) ?>!</h2>
            </div>

            <div class="dashboard-grid">
                <div class="widget-card">
                    <h4><i class="fas fa-chalkboard"></i> Classes Handled</h4>
                    <div class="value"><?= $total_classes ?></div>
                    <p class="desc">For S.Y. <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_semester) ?></p>
                </div>
                <div class="widget-card">
                    <h4><i class="fas fa-users"></i> Total Students</h4>
                    <div class="value"><?= $total_students ?></div>
                    <p class="desc">Across all your classes this term.</p>
                </div>
            </div>

            <div class="dashboard-grid" style="grid-template-columns: 2fr 1fr; align-items: flex-start;">
                <div class="card">
                    <h3>My Classes</h3>
                    <p>Your assigned classes for the current term.</p>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr><th>Subject Code</th><th>Subject Name</th><th>Class</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($current_classes)): ?>
                                    <?php foreach ($current_classes as $class): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($class['subject_code']) ?></strong></td>
                                            <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($class['course_code'] . ' ' . $class['year_level'] . ' - ' . $class['section_name']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align: center; padding: 20px;">You have no classes assigned for this term.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-bullhorn"></i> Recent Announcements</h3>
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                                <small>Posted on: <?= date('F j, Y', strtotime($announcement['date_posted'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="desc">No recent announcements.</p>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>