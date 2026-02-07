<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

// Set user details for the view
$user_display_name = $_SESSION['username'] ?? 'Admin'; // Fallback to 'Admin'
$user_id = $_SESSION['user_id'] ?? 0;
/**
 * Checks if the current user has a specific permission.
 * For the admin dashboard, we will make this static and always return true
 * to avoid conflicts and show all sections.
 * @param string $permission The permission to check.
 * @return bool True if the user has the permission, false otherwise.
 */
function hasPermission(string $permission): bool {
    return true; // Always show all sections for the admin dashboard.
}

// Fetch Students
$students = [];
$studentSql = "SELECT user_id, username, email, status FROM users WHERE role = 'student'";
$studentResult = $conn->query($studentSql);
if ($studentResult && $studentResult->num_rows > 0) {
    while($row = $studentResult->fetch_assoc()) {
        $students[] = $row;
    }
}

// Fetch Pending Enrollments
$pending_enrollments = [];
$pendingEnrollmentSql = "SELECT 
                            e.enrollment_id,
                            u.user_id,
                            u.username,
                            u.email,
                            sp.full_name, -- Get full name from student_profiles
                            st.year_level,
                            c.course_code,
                            e.school_year,
                            e.semester,
                            e.date_submitted
                         FROM enrollments e
                         JOIN students st ON e.student_id = st.student_id
                         JOIN users u ON st.user_id = u.user_id 
                         LEFT JOIN student_profiles sp ON u.user_id = sp.user_id -- Join to get full name
                         LEFT JOIN courses c ON st.course_id = c.course_id
                         WHERE e.status = 'pending'
                         ORDER BY e.date_submitted ASC";
try {
    $pendingEnrollmentResult = $conn->query($pendingEnrollmentSql);
    if ($pendingEnrollmentResult && $pendingEnrollmentResult->num_rows > 0) {
        while($row = $pendingEnrollmentResult->fetch_assoc()) {
            $pending_enrollments[] = $row;
        }
    }
} catch (mysqli_sql_exception $e) {
    // If the table doesn't exist, we can just ignore it and the array will be empty.
    // This prevents the entire dashboard from crashing.
}

// --- Get Filter values for Enrolled Students ---
$filter_course_id = $_GET['filter_course'] ?? '';
$filter_year_level = $_GET['filter_year'] ?? '';
$filter_appointment_date = $_GET['filter_date'] ?? '';


// Fetch Enrolled Students
$enrolled_students = [];
$enrolledStudentSql = "SELECT 
                            e.enrollment_id,
                            u.user_id,
                            u.username,
                            u.email,
                            sp.full_name,
                            st.year_level,
                            c.course_code,
                            c.course_id,
                            e.school_year,
                            e.semester,
                            e.date_submitted AS date_enrolled, -- Use date_submitted as the enrollment date
                            e.appointment_date
                         FROM enrollments e
                         JOIN students st ON e.student_id = st.student_id
                         JOIN users u ON st.user_id = u.user_id 
                         LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
                         LEFT JOIN courses c ON st.course_id = c.course_id
                         WHERE e.status = 'approved'
                         ORDER BY e.date_submitted DESC";

$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_course_id)) {
    $where_clauses[] = "c.course_id = ?";
    $params[] = $filter_course_id;
    $types .= 'i';
}
if (!empty($filter_year_level)) {
    $where_clauses[] = "st.year_level = ?";
    $params[] = $filter_year_level;
    $types .= 's';
}
if (!empty($filter_appointment_date)) {
    $where_clauses[] = "e.appointment_date = ?";
    $params[] = $filter_appointment_date;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $enrolledStudentSql = str_replace("ORDER BY", " AND " . implode(" AND ", $where_clauses) . " ORDER BY", $enrolledStudentSql);
}

$stmt_enrolled = $conn->prepare($enrolledStudentSql);
if (!empty($params)) { $stmt_enrolled->bind_param($types, ...$params); }
$stmt_enrolled->execute();
$enrolledStudentResult = $stmt_enrolled->get_result();
while($row = $enrolledStudentResult->fetch_assoc()) { $enrolled_students[] = $row; }

// Fetch Instructors
$instructors_by_department = [];
$instructorSql = "SELECT 
                    u.user_id, u.username, u.email, u.status,
                    i.first_name, i.last_name,
                    dep.department_name
                  FROM users u
                  JOIN instructors i ON u.user_id = i.user_id
                  LEFT JOIN departments dep ON i.department_id = dep.department_id
                  WHERE u.role = 'instructor'
                  ORDER BY dep.department_name, i.last_name, i.first_name";

$instructorResult = $conn->query($instructorSql);
if ($instructorResult && $instructorResult->num_rows > 0) {
    while($row = $instructorResult->fetch_assoc()) {
        // Group instructors by department name
        $dept_name = $row['department_name'] ?? 'Unassigned';
        $instructors_by_department[$dept_name][] = $row;
    }
}

// Fetch Subjects
$subjects_raw = [];
$subjectSql = "SELECT s.subject_id, s.subject_code, s.subject_name, s.units, s.year_level, s.semester, s.status, c.course_code AS course, c.course_name, s.course_id
               FROM subjects s 
               LEFT JOIN courses c ON s.course_id = c.course_id 
               ORDER BY c.course_code, s.year_level, s.semester, s.subject_code";
$subjectResult = $conn->query($subjectSql);
if ($subjectResult && $subjectResult->num_rows > 0) {
    while($row = $subjectResult->fetch_assoc()) {
        $subjects_raw[] = $row;
}
}

// Define a custom order for year levels
$year_level_order = ['1st' => 1, '2nd' => 2, '3rd' => 3, '4th' => 4, '5th' => 5];

$grouped_subjects = [];
foreach ($subjects_raw as $subject) {
    $course_code = $subject['course'] ?? 'Unassigned Course';
    $year_level = $subject['year_level'] ?? 'Unassigned Year';
    $semester = $subject['semester'] ?? 'Unassigned Semester';

    if (!isset($grouped_subjects[$course_code])) {
        $grouped_subjects[$course_code] = [];
    }
    if (!isset($grouped_subjects[$course_code][$year_level])) {
        $grouped_subjects[$course_code][$year_level] = [];
    }
    $grouped_subjects[$course_code][$year_level][$semester][] = $subject;
}

// Fetch Courses for dropdowns
$courses = [];
$coursesSql = "SELECT course_id, course_code, course_name FROM courses ORDER BY course_code";
$coursesResult = $conn->query($coursesSql);
if ($coursesResult && $coursesResult->num_rows > 0) {
    while($row = $coursesResult->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Fetch Instructors for dropdowns
$all_instructors = [];
$instructorsSql = "SELECT instructor_id, CONCAT(first_name, ' ', last_name) AS full_name FROM instructors ORDER BY last_name";
$instructorsResult = $conn->query($instructorsSql);
if ($instructorsResult && $instructorsResult->num_rows > 0) {
    while($row = $instructorsResult->fetch_assoc()) {
        $all_instructors[] = $row;
    }
}

// Fetch Rooms for dropdowns
$all_rooms = [];
$rooms_by_department = [];
$roomsSql = "SELECT r.room_id, r.room_name, d.department_id, d.department_name 
             FROM rooms r 
             LEFT JOIN departments d ON r.department_id = d.department_id 
             ORDER BY d.department_name, r.room_name";
$roomsResult = $conn->query($roomsSql);
if ($roomsResult && $roomsResult->num_rows > 0) {
    while($row = $roomsResult->fetch_assoc()) {
        $all_rooms[] = $row; // For flat list dropdowns
        // Group rooms by department name for organized display
        $dept_name = $row['department_name'] ?? 'Unassigned';
        $rooms_by_department[$dept_name][] = $row;
    }
}

// Fetch Departments for dropdowns
$departments = [];
$departmentsSql = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$departmentsResult = $conn->query($departmentsSql);
if ($departmentsResult && $departmentsResult->num_rows > 0) {
    while($row = $departmentsResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch Sections for dropdowns
$sections = [];
$sectionsSql = "SELECT s.section_id, s.section_name, s.year_level, s.semester, c.course_code, c.course_name FROM sections s LEFT JOIN courses c ON s.course_id = c.course_id ORDER BY c.course_code, s.year_level, s.semester, s.section_name";
$sectionsResult = $conn->query($sectionsSql);
if ($sectionsResult && $sectionsResult->num_rows > 0) {
    while($row = $sectionsResult->fetch_assoc()) {
        $sections[] = $row;
    }
}

// Group sections for categorized display
$grouped_sections = [];
foreach ($sections as $section) {
    $course_code = $section['course_code'] ?? 'Unassigned';
    $course_name = $section['course_name'] ?? 'Unassigned Course';
    $year_level = $section['year_level'] ?? 'N/A';
    $semester = $section['semester'] ?? 'N/A';

    if (!isset($grouped_sections[$course_code])) {
        $grouped_sections[$course_code] = ['course_name' => $course_name, 'year_levels' => []];
    }
    if (!isset($grouped_sections[$course_code]['year_levels'][$year_level])) {
        $grouped_sections[$course_code]['year_levels'][$year_level] = [];
    }
    $grouped_sections[$course_code]['year_levels'][$year_level][$semester][] = $section;
}

// Fetch Schedules
$schedules = [];
$scheduleSql = "SELECT 
                    sch.schedule_id,
                    sub.subject_code,
                    sub.subject_name,
                    ins.instructor_id,
                    CONCAT(ins.first_name, ' ', ins.last_name) AS instructor_name,
                    rm.room_id,
                    sch.day,
                    TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start_formatted,
                    TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end_formatted,
                    rm.room_name,
                    sec.section_name,
                    sec.year_level,
                    crs.course_code,
                    sch.school_year,
                    sch.semester
                FROM 
                    schedules AS sch
                LEFT JOIN sections AS sec ON sch.section_id = sec.section_id
                LEFT JOIN courses AS crs ON sec.course_id = crs.course_id
                LEFT JOIN subjects AS sub ON sch.subject_id = sub.subject_id
                LEFT JOIN instructors AS ins ON sch.instructor_id = ins.instructor_id
                LEFT JOIN rooms AS rm ON sch.room_id = rm.room_id
                ORDER BY sch.school_year DESC, sch.semester, sub.subject_code";
$scheduleResult = $conn->query($scheduleSql);
if ($scheduleResult && $scheduleResult->num_rows > 0) {
    while($row = $scheduleResult->fetch_assoc()) {
        $schedules[] = $row;
    }
}

// Fetch Announcements for Admin
$announcements_for_admin = [];
$announcementSql = "SELECT title, content, date_posted, target_role FROM announcements WHERE target_role IN ('all', 'admin', 'superadmin', 'student', 'instructor') ORDER BY date_posted DESC";
$announcementResult = $conn->query($announcementSql);
if ($announcementResult && $announcementResult->num_rows > 0) {
    while($row = $announcementResult->fetch_assoc()) {
        $announcements_for_admin[] = $row;
    }
}

// Group Schedules by Course, Year Level, Semester, and Section
$grouped_schedules = [];
$year_level_order = ['1st Year' => 1, '2nd Year' => 2, '3rd Year' => 3, '4th Year' => 4, '5th Year' => 5];
$semester_order = ['1st Semester' => 1, '2nd Semester' => 2, 'Summer' => 3];

foreach ($schedules as $schedule) {
    $course_code = $schedule['course_code'] ?? 'N/A';
    $year_level = $schedule['year_level'] ?? 'N/A';
    $semester = $schedule['semester'] ?? 'N/A';
    $section_name = $schedule['section_name'] ?? 'N/A';

    if (!isset($grouped_schedules[$course_code])) {
        $grouped_schedules[$course_code] = [];
    }
    if (!isset($grouped_schedules[$course_code][$year_level])) {
        $grouped_schedules[$course_code][$year_level] = [];
    }
    if (!isset($grouped_schedules[$course_code][$year_level][$semester])) {
        $grouped_schedules[$course_code][$year_level][$semester] = [];
    }
    if (!isset($grouped_schedules[$course_code][$year_level][$semester][$section_name])) {
        $grouped_schedules[$course_code][$year_level][$semester][$section_name] = [];
    }
    $grouped_schedules[$course_code][$year_level][$semester][$section_name][] = $schedule;
}

// Sort for consistent display
uksort($grouped_schedules, function($a, $b) { return strcmp($a, $b); }); // Sort courses by code
foreach ($grouped_schedules as $course_code => &$year_levels) {
    uksort($year_levels, function($a, $b) use ($year_level_order) { return ($year_level_order[$a] ?? 99) <=> ($year_level_order[$b] ?? 99); });
    foreach ($year_levels as $year_level => &$semesters) {
        uksort($semesters, function($a, $b) use ($semester_order) { return ($semester_order[$a] ?? 99) <=> ($semester_order[$b] ?? 99); });
        foreach ($semesters as $semester => &$sections_in_sem) {
            uksort($sections_in_sem, function($a, $b) { return strcmp($a, $b); }); // Sort sections by name
        }
    }
}
unset($year_levels, $semesters, $sections_in_sem); // Unset references

$page_title = 'Admin Dashboard';

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
    <!-- Select2 CSS for searchable dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

</head>
<style>
    /* Styles for grouped schedules */
    .schedule-group {
        margin-bottom: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        overflow: hidden;
    }
    .schedule-group .group-header {
        background-color: #f8f9fa;
        padding: 12px 15px;
        cursor: pointer;
        font-weight: bold;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e0e0e0;
        transition: background-color 0.2s ease;
    }
    .schedule-group .group-header:hover {
        background-color: #e9ecef;
    }
    .schedule-group .group-header .toggle-icon {
        transition: transform 0.2s ease;
    }
    .schedule-group .group-header.expanded .toggle-icon {
        transform: rotate(90deg);
    }
    .schedule-group .group-content {
        padding: 15px;
        background-color: #fff;
    }
    .schedule-group.course-group > .group-header { background-color: #eaf2f8; color: var(--primary); font-size: 1.2em; }
    .schedule-group.year-group > .group-header { background-color: #f2f7fb; color: #34495e; font-size: 1.1em; margin-left: 15px; }
    .schedule-group.semester-group > .group-header { background-color: #f9fcfd; color: #555; font-size: 1em; margin-left: 30px; }
    .schedule-group.section-group > .group-header { background-color: #ffffff; color: #777; font-size: 0.95em; margin-left: 45px; border-bottom: none; }
    .schedule-group.section-group > .group-content { padding-top: 0; }
    .schedule-group .data-table { margin-top: 0; }
</style>
<body class="dashboard">
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h1><i class="fas fa-calendar-alt"></i> SchedMaster</h1>
            </div>
            
            <!-- Regular Admin Sidebar -->
            <div class="menu-section">
                <h3>Main Menu</h3>
                <ul class="menu-items">
                    <?php if (hasPermission('view_dashboard')): ?>
                        <li><a href="#" onclick="showContent('dashboard-home', this.parentElement)"><i class="fas fa-th-large"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_enrollments')): ?>
                        <li><a href="#" onclick="showContent('pending-enrollments', this.parentElement)"><i class="fas fa-user-check"></i> Pending Enrollments</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_enrollments')): // Using same permission for now ?>
                        <li><a href="#" onclick="showContent('enrolled-students', this.parentElement);"><i class="fa-solid fa-user-graduate"></i> Manage Enrolled Student</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_instructors')): ?>
                        <li><a href="#" onclick="showContent('manage-instructors', this.parentElement)"><i class="fas fa-chalkboard-teacher"></i> Manage Instructors</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_subjects')): ?>
                        <li><a href="#" onclick="showContent('manage-subjects', this.parentElement)"><i class="fas fa-book"></i> Manage Subjects</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_schedules')): ?>
                        <li><a href="#" onclick="showContent('manage-schedules', this.parentElement)"><i class="fas fa-clock"></i> Manage Schedules</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_announcements')): ?>
                        <li><a href="#" onclick="showContent('announcements', this.parentElement)"><i class="fas fa-bullhorn"></i> Announcements</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="menu-section">
                <h3>Maintenance</h3>
                <ul class="menu-items">
                    <?php if (hasPermission('manage_courses')): ?>
                        <li><a href="#" onclick="showContent('manage-courses', this.parentElement)"><i class="fas fa-graduation-cap"></i> Manage Courses</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_departments')): ?>
                        <li><a href="#" onclick="showContent('manage-departments', this.parentElement)"><i class="fas fa-sitemap"></i> Manage Departments</a></li>
                    <?php endif; ?>
                    <?php if (hasPermission('manage_rooms_sections')): ?>
                        <li><a href="#" onclick="showContent('room-setup', this.parentElement)"><i class="fas fa-building"></i> Room & Section Setup</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="menu-section">
                <h3>Settings</h3>
                <ul class="menu-items">
                    <li><a href="#" onclick="showContent('reports', this.parentElement)"><i class="fas fa-chart-bar"></i> Reports</a></li>
                    <li><a href="../../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                </ul>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2 id="main-header-title">Dashboard</h2>
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <div style="text-align: right;">
                        <div><?= htmlspecialchars($user_display_name) ?></div>
                        <div style="font-size: 0.8rem; color: var(--gray);">Admin</div>
                    </div>
                </div>
            </div>
            
            <?php
                $success_messages = [
                    'instructoradded' => 'New instructor has been successfully created.',
                    'studentadded' => 'New student has been successfully created.',
                    'subjectadded' => 'New subject has been successfully added.',
                    'scheduleadded' => 'New schedule has been successfully created.',
                    'roomadded' => 'New room has been successfully added.',
                    'sectionadded' => 'New section has been successfully created.',
                    'courseadded' => 'New course has been successfully added.',
                    'dept_added' => 'New department has been successfully added.',
                ];
                $error_messages = [
                    'dberror' => 'A database error occurred. Please try again.',
                    'emptyfields' => 'Please fill in all required fields.',
                    'emptyfields_dept' => 'Department name cannot be empty.',
                    'conflict' => 'A scheduling conflict was detected. The item was not added.',
                    'duplicate_subject' => 'A subject with that code already exists. Please use a different code.',
                    'dept_exists' => 'A department with that name already exists.',
                ];

                if (isset($_GET['success']) && array_key_exists($_GET['success'], $success_messages)) {
                    echo '<div class="alert-message success">' . htmlspecialchars($success_messages[$_GET['success']]) . '<span class="close-alert" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
                }

                if (isset($_GET['error']) && array_key_exists($_GET['error'], $error_messages)) {
                    echo '<div class="alert-message error">' . htmlspecialchars($error_messages[$_GET['error']]) . '<span class="close-alert" onclick="this.parentElement.style.display=\'none\';">&times;</span></div>';
                }
            ?>

            <!-- Dashboard Home Section -->
            <?php if (hasPermission('view_dashboard')): ?>
                <div id="dashboard-home" class="content-section">
                    <?php include '_admin_content.php'; ?>
                </div>
            <?php endif; ?>

            <!-- Pending Enrollments Section -->
            <?php if (hasPermission('manage_enrollments')): ?>
            <div id="pending-enrollments" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-user-check"></i> Pending Enrollments</h2>
                </div>
                <p>Review and approve or reject student enrollment requests.</p>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Section</th>
                            <th>Course</th>
                            <th>S.Y. & Sem</th>
                            <th>Date Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pending_enrollments)): ?>
                            <?php foreach ($pending_enrollments as $enrollment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($enrollment['full_name'] ?? $enrollment['username']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['user_id']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['email']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['year_level']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['course_code']) ?></td>
                                    <td><?= htmlspecialchars($enrollment['school_year']) ?> / <?= htmlspecialchars($enrollment['semester']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($enrollment['date_submitted'])) ?></td>
                                    <td class="action-links">
                                        <a href="../actions/approve_enrollment.php?id=<?= $enrollment['enrollment_id'] ?>" style="color: var(--success);"><i class="fa-solid fa-check"></i> Approve</a>
                                        <a href="../actions/reject_enrollment.php?id=<?= $enrollment['enrollment_id'] ?>" style="color: #e74c3c;"><i class="fa-solid fa-times"></i> Reject</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align:center;">
                                    <i class="fas fa-check-circle" style="color: var(--success); margin-right: 5px;"></i>
                                    No pending enrollment requests at this time.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Enrolled Students Section -->
            <?php if (hasPermission('manage_enrollments')): ?>
            <div id="enrolled-students" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fa-solid fa-user-graduate"></i> Manage Enrolled Students</h2>
                </div>
                <p>View and manage all students with an approved enrollment status.</p>

                <!-- Filter Form -->
                <form action="" method="GET" id="enrolledFilterForm" class="filter-form" style="margin-top: 1.5rem;">
                    <input type="hidden" name="tab" value="enrolled-students">
                    <div class="filter-group">
                        <label for="filter_course">Course:</label>
                        <select name="filter_course" id="filter_course">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>" <?= ($filter_course_id == $course['course_id']) ? 'selected' : '' ?>><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter_year">Year Level:</label>
                        <select name="filter_year" id="filter_year">
                            <option value="">All Years</option>
                            <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year): ?>
                                <option value="<?= $year ?>" <?= ($filter_year_level == $year) ? 'selected' : '' ?>><?= $year ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group"><label for="filter_date">Appointment Date:</label><input type="date" name="filter_date" id="filter_date" value="<?= htmlspecialchars($filter_appointment_date) ?>"></div>
                    <button type="submit" class="button"><i class="fas fa-filter"></i> Filter</button>
                </form>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Student ID</th>
                            <th>Course</th>
                            <th>S.Y. & Sem</th>
                            <th>Date Enrolled</th>
                            <th>Appointment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrolled_students)): ?>
                            <?php foreach ($enrolled_students as $student): ?>
                                <tr>
                                    <td><?= htmlspecialchars($student['full_name'] ?? $student['username']) ?></td>
                                    <td><?= htmlspecialchars($student['user_id']) ?></td>
                                    <td><?= htmlspecialchars($student['course_code']) ?></td>
                                    <td><?= htmlspecialchars($student['school_year']) ?> / <?= htmlspecialchars($student['semester']) ?></td>
                                    <td><?= date('M d, Y', strtotime($student['date_enrolled'])) ?></td>
                                    <td>
                                        <?php if (!empty($student['appointment_date'])): ?>
                                            <strong><?= date('M j, Y', strtotime($student['appointment_date'])) ?></strong>
                                        <?php else: ?>
                                            <span style="color: #999;">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="action-links">
                                        <a href="#" style="color: var(--primary); margin-right: 10px;"><i class="fa-solid fa-eye"></i> View</a>
                                        <a href="print_enrollment.php?id=<?= $student['enrollment_id'] ?>" target="_blank" style="color: #555;"><i class="fa-solid fa-print"></i> Print</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align:center;">
                                    No students are currently enrolled.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Manage Instructors Section -->
            <?php if (hasPermission('manage_instructors')): ?>
            <div id="manage-instructors" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-chalkboard-teacher"></i> Manage Instructors</h2>
                    <?php if (hasPermission('manage_students_instructors')): // Assuming this is the create/edit permission ?>
                        <a href="#" class="button" onclick="openAddInstructorModal(); return false;"><i class="fa-solid fa-plus"></i> Add Instructor</a>
                    <?php endif; ?>
                </div>
                <p>Manage instructor profiles, assign subjects, and view their schedules.</p>

                <?php if (!empty($instructors_by_department)): ?>
                    <?php foreach ($instructors_by_department as $department_name => $instructors_in_dept): ?>
                        <h4 class="department-header" style="margin-top: 1.5rem; margin-bottom: 0.5rem; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px; cursor: pointer;">
                            <?= htmlspecialchars($department_name) ?> (<?= count($instructors_in_dept) ?>)
                            <i class="fas fa-chevron-down" style="float: right; margin-top: 4px;"></i>
                        </h4>
                        <div class="department-instructors" style="display: none;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($instructors_in_dept as $instructor): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($instructor['first_name'] . ' ' . $instructor['last_name']) ?></td>
                                            <td><?= htmlspecialchars($instructor['username']) ?></td>
                                            <td><?= htmlspecialchars($instructor['email']) ?></td>
                                            <td>
                                                <span class="status-<?= $instructor['status'] === 'active' ? 'active' : 'inactive' ?>">
                                                    <?= ucfirst(htmlspecialchars($instructor['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="action-links">
                                                <a href="#"><i class="fa-solid fa-pencil"></i> Edit</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; margin-top: 2rem;">No instructors have been added yet.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Manage Subjects Section -->
            <?php if (hasPermission('manage_subjects')): ?>
            <div id="manage-subjects" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-book"></i> Manage Subjects</h2>
                    <?php if (hasPermission('manage_subjects')): // Assuming full manage rights for this button ?>
                        <a href="#" class="button" onclick="openAddSubjectModal(); return false;"><i class="fa-solid fa-plus"></i> Add Subject</a>
                    <?php endif; ?>
                </div>
                <p>Add new subjects, edit existing ones, and manage course information.</p>
                <p class="info-note">Subjects are grouped by Course, Year Level, and Semester.</p>

                <?php if (!empty($grouped_subjects)): ?>
                    <?php foreach ($grouped_subjects as $course_code => $year_levels_in_course): ?>
                        <h3 class="course-group-header" style="margin-top: 2rem; margin-bottom: 1rem; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px; cursor: pointer;">
                            <?= htmlspecialchars($course_code) ?> (<?= count($year_levels_in_course, COUNT_RECURSIVE) - count($year_levels_in_course) ?> Subjects)
                            <i class="fas fa-chevron-down" style="float: right; margin-top: 4px;"></i>
                        </h3>
                        <div class="course-subjects-container" style="display: none;">
                            <?php
                            // Sort year levels
                            uksort($year_levels_in_course, function($a, $b) use ($year_level_order) {
                                return ($year_level_order[$a] ?? 99) <=> ($year_level_order[$b] ?? 99);
                            });
                            ?>
                            <?php foreach ($year_levels_in_course as $year_level => $semesters_in_year): ?>
                                <h4 class="department-header" style="margin-top: 1rem; margin-bottom: 0.5rem; color: var(--text-color-light); border-bottom: 1px dotted #eee; padding-bottom: 3px; cursor: pointer; margin-left: 15px;">
                                    <?= htmlspecialchars($year_level) ?> Year (<?= count($semesters_in_year, COUNT_RECURSIVE) - count($semesters_in_year) ?> Subjects)
                                    <i class="fas fa-chevron-down" style="float: right; margin-top: 4px;"></i>
                                </h4>
                                <div class="department-instructors" style="display: none; margin-left: 30px;">
                                    <?php
                                    // Define a custom order for semesters
                                    $semester_order = ['1st' => 1, '2nd' => 2, 'Summer' => 3];
                                    uksort($semesters_in_year, function($a, $b) use ($semester_order) {
                                        return ($semester_order[$a] ?? 99) <=> ($semester_order[$b] ?? 99);
                                    });
                                    ?>
                                    <?php foreach ($semesters_in_year as $semester => $subjects_in_semester): ?>
                                        <h5 class="semester-group-header" style="margin-top: 1rem; margin-bottom: 0.5rem; color: #6c757d; cursor: pointer; margin-left: 15px;">
                                            <?= htmlspecialchars($semester) ?> Semester (<?= count($subjects_in_semester) ?> Subjects)
                                            <i class="fas fa-chevron-down" style="float: right; margin-top: 4px;"></i>
                                        </h5>
                                        <div class="semester-subjects-container" style="display: none; margin-left: 30px;">
                                            <table class="data-table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Name</th>
                                                        <th>Units</th>
                                                        <th>Status</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($subjects_in_semester as $subject): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                                                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                                                            <td><?= htmlspecialchars($subject['units']) ?></td>
                                                            <td>
                                                                <span class="status-<?= $subject['status'] === 'active' ? 'active' : 'inactive' ?>">
                                                                    <?= ucfirst(htmlspecialchars($subject['status'])) ?>
                                                                </span>
                                                            </td>
                                                            <td class="action-links">
                                                                <a href="#" onclick='openEditSubjectModal(<?= json_encode($subject) ?>); return false;'><i class="fa-solid fa-pencil"></i> Edit</a>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; margin-top: 2rem;">No subjects found.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Manage Schedules Section -->
            <?php if (hasPermission('manage_schedules')): ?>
            <div id="manage-schedules" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-clock"></i> Manage Schedules</h2>
                    <?php if (hasPermission('manage_schedules')): // Assuming full manage rights for this button ?>
                        <a href="#" class="button" onclick="openAddScheduleModal(); return false;"><i class="fa-solid fa-plus"></i> Add Schedule</a>
                    <?php endif; ?>
                </div>
                <p>Create, view, and manage class schedules for different sections and semesters.</p>

                <?php if (!empty($grouped_schedules)): ?>
                    <?php foreach ($grouped_schedules as $course_code => $year_levels): ?>
                        <div class="schedule-group course-group">
                            <h3 class="group-header" data-toggle="course-<?= str_replace([' ', '/'], '-', $course_code) ?>">
                                <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($course_code) ?>
                            </h3>
                            <div id="course-<?= str_replace([' ', '/'], '-', $course_code) ?>" class="group-content" style="display: none;">
                                <?php foreach ($year_levels as $year_level => $semesters): ?>
                                    <div class="schedule-group year-group">
                                        <h4 class="group-header" data-toggle="year-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>">
                                            <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($year_level) ?>
                                        </h4>
                                        <div id="year-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace(' ', '-', $year_level) ?>" class="group-content" style="display: none;">
                                            <?php foreach ($semesters as $semester => $sections_in_sem): ?>
                                                <div class="schedule-group semester-group">
                                                    <h5 class="group-header" data-toggle="sem-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>">
                                                        <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($semester) ?>
                                                    </h5>
                                                    <div id="sem-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>" class="group-content" style="display: none;">
                                                        <?php foreach ($sections_in_sem as $section_name => $schedules_in_section): ?>
                                                            <div class="schedule-group section-group">
                                                                <h6 class="group-header" data-toggle="section-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>-<?= str_replace([' ', '/'], '-', $section_name) ?>">
                                                                    <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($section_name) ?>
                                                                </h6>
                                                                <div id="section-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>-<?= str_replace([' ', '/'], '-', $section_name) ?>" class="group-content" style="display: none;">
                                                                    <table class="data-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Subject</th>
                                                                                <th>Day & Time</th>
                                                                                <th>Room</th>
                                                                                <th>Instructor</th>
                                                                                <th>S.Y. & Sem</th>
                                                                                <th>Actions</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($schedules_in_section as $schedule): ?>
                                                                                <tr>
                                                                                    <td>
                                                                                        <strong><?= htmlspecialchars($schedule['subject_code']) ?></strong><br>
                                                                                        <small><?= htmlspecialchars($schedule['subject_name']) ?></small>
                                                                                    </td>
                                                                                    <td>
                                                                                        <?= htmlspecialchars($schedule['day']) ?><br>
                                                                                        <small><?= htmlspecialchars($schedule['time_start_formatted']) ?> - <?= htmlspecialchars($schedule['time_end_formatted']) ?></small>
                                                                                    </td>
                                                                                    <td><?= htmlspecialchars($schedule['room_name'] ?? 'N/A') ?></td>
                                                                                    <td><?= htmlspecialchars($schedule['instructor_name'] ?? 'N/A') ?></td>
                                                                                    <td><?= htmlspecialchars($schedule['school_year']) ?><br><small><?= htmlspecialchars($schedule['semester']) ?></small></td>
                                                                                    <td class="action-links">                                        
                                                                                        <a href="#" onclick='openEditScheduleModal(<?= json_encode($schedule) ?>); return false;'><i class="fa-solid fa-pencil"></i> Edit</a>
                                                                                    </td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; margin-top: 2rem;">No schedules found.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Add Schedule Modal -->
            <div id="addScheduleModal" class="modal" style="display:none;">
                <div class="modal-content" style="max-width: 850px;">
                    <div class="modal-header">
                        <h2><i class="fa-solid fa-calendar-plus"></i> Create New Schedule</h2>
                        <span class="close-btn" onclick="closeModal('addScheduleModal')">&times;</span>
                    </div>
                    <form id="addScheduleForm" action="../actions/add_schedule.php" method="POST">
                        <!-- Step 1: Filters -->
                        <div class="modal-form-grid" style="grid-template-columns: 1fr 1fr 1fr; margin-bottom: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 1.5rem;">
                            <div class="form-input-group">
                                <label for="schedule_filter_course">1. Select Course</label>
                                <select id="schedule_filter_course" name="filter_course_id" required>
                                    <option value="" disabled selected>Choose a course...</option>
                                    <?php foreach ($courses as $course): ?>
                                        <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-input-group">
                                <label for="schedule_filter_year">2. Filter by Year</label>
                                <select id="schedule_filter_year" name="filter_year_level"><option value="">All Years</option><option value="1">1st Year</option><option value="2">2nd Year</option><option value="3">3rd Year</option><option value="4">4th Year</option></select>
                            </div>
                            <div class="form-input-group">
                                <label for="schedule_filter_semester">3. Filter by Semester</label>
                                <select id="schedule_filter_semester" name="filter_semester"><option value="">All Semesters</option><option value="1st">1st Semester</option><option value="2nd">2nd Semester</option><option value="Summer">Summer</option></select>
                            </div>
                        </div>

                        <!-- Step 2: Schedule Details -->
                        <div id="schedule-details-container" class="modal-form-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); display: none;">
                            <div class="form-input-group">
                                <label for="add_sched_subject_id">Subject</label>
                                <select id="add_sched_subject_id" name="subject_id" required><option value="" disabled selected>Select course first</option></select>
                            </div>
                            <div class="form-input-group">
                                <label for="add_sched_instructor_id">Instructor</label>
                                <select id="add_sched_instructor_id" name="instructor_id" required>
                                    <option value="" disabled selected>Select course first</option>                                    
                                    <option value="" disabled selected>Select course first</option>
                                    <?php foreach ($all_instructors as $instructor): ?>
                                        <option value="<?= $instructor['instructor_id'] ?>"><?= htmlspecialchars($instructor['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-input-group">
                                <label for="add_sched_section_id">Section</label>
                                <select id="add_sched_section_id" name="section_id" required><option value="" disabled selected>Select course first</option></select>
                            </div>
                            <div class="form-input-group">
                                <label for="add_sched_room_id">Room</label>
                                <select id="add_sched_room_id" name="room_id" required><option value="" disabled selected>Select course first</option></select>
                            </div>
                            <div class="form-input-group">
                                <label for="add_sched_day">Day of Week</label>
                                <select id="add_sched_day" name="day" required><option value="Monday">Monday</option><option value="Tuesday">Tuesday</option><option value="Wednesday">Wednesday</option><option value="Thursday">Thursday</option><option value="Friday">Friday</option><option value="Saturday">Saturday</option></select>
                            </div>
                            <div class="form-input-group"><label for="add_sched_time_start">Time Start</label><input type="time" id="add_sched_time_start" name="time_start" required></div>
                            <div class="form-input-group"><label for="add_sched_time_end">Time End</label><input type="time" id="add_sched_time_end" name="time_end" required></div>
                            <div class="form-input-group"><label for="add_sched_school_year">School Year</label><input type="text" id="add_sched_school_year" name="school_year" placeholder="e.g., 2024-2025" required></div>
                            <div class="form-input-group"><label for="add_sched_semester">Semester</label><select id="add_sched_semester" name="semester" required><option value="1st">1st</option><option value="2nd">2nd</option><option value="Summer">Summer</option></select></div>
                        </div>
                        <div class="modal-buttons"><button type="button" class="button secondary-btn" onclick="closeModal('addScheduleModal')">Cancel</button><button type="submit" class="button"><i class="fa-solid fa-plus"></i> Create Schedule</button></div>
                    </form>
                </div>
            </div>

            <!-- Announcements Section -->
            <?php if (hasPermission('manage_announcements')): ?>
            <div id="announcements" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-bullhorn"></i> View Announcements</h2>
                    <?php if (hasPermission('manage_announcements')): // Assuming full manage rights for this button ?>
                        <a href="../superadmin/manage_announcements.php" class="button"><i class="fa-solid fa-pen-to-square"></i> Manage Announcements</a>
                    <?php endif; ?>
                 </div>
                <p>View global announcements and post new ones for students and instructors.</p>

                <ul class="activity-list" style="margin-top: 20px;">
                    <?php if (!empty($announcements_for_admin)): ?>
                        <?php foreach ($announcements_for_admin as $announcement): ?>
                            <li style="align-items: flex-start;">
                                <div class="activity-icon" style="margin-top: 4px;"><i class="fas fa-bullhorn"></i></div>
                                <div>
                                    <strong><?= htmlspecialchars($announcement['title']) ?></strong><br>
                                    <?= nl2br(htmlspecialchars($announcement['content'])) ?><br>
                                    <small style="color: #007bff; font-weight: bold;">Audience: <?= ucfirst(htmlspecialchars($announcement['target_role'])) ?></small><br>
                                    <small style="color: var(--gray);">Posted on: <?= date('F j, Y, g:i a', strtotime($announcement['date_posted'])) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?><li>No announcements found.</li><?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Room & Section Setup Section -->
            <?php if (hasPermission('manage_rooms_sections')): ?>
            <div id="room-setup" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-building"></i> Room & Section Setup</h2>
                </div>
                <p>Manage available rooms and create new class sections for courses.</p>

                <div class="modal-form-grid" style="align-items: flex-start;">
                    <!-- Rooms Management (Grouped by Department) -->
                    <div>
                        <div class="section-header" style="margin-bottom: 1rem;">
                            <h3>Rooms by Department</h3>
                            <?php if (hasPermission('manage_rooms_sections')): // Assuming full manage rights for this button ?>
                                <a href="#" class="button" onclick="openAddRoomModal(); return false;"><i class="fa-solid fa-plus"></i> Add Room</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($rooms_by_department)): ?>
                            <?php foreach ($rooms_by_department as $department_name => $rooms_in_dept): ?>
                                <h4 style="margin-top: 1.5rem; margin-bottom: 0.5rem; color: var(--primary); border-bottom: 1px solid #eee; padding-bottom: 5px;"><?= htmlspecialchars($department_name) ?></h4>
                                <table class="data-table">
                                    <thead><tr><th>Room Name</th><th>Actions</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($rooms_in_dept as $room): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($room['room_name']) ?></td>
                                            <td class="action-links"><a href="#"><i class="fa-solid fa-pencil"></i> Edit</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align:center; margin-top: 2rem;">No rooms have been added yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Sections Management -->
                    <div>
                        <div class="section-header" style="margin-bottom: 1rem;">
                            <h3>Class Sections</h3>
                            <?php if (hasPermission('manage_rooms_sections')): // Assuming full manage rights for this button ?>
                                <a href="#" class="button" onclick="openAddSectionModal(); return false;"><i class="fa-solid fa-plus"></i> Add Section</a>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($grouped_sections)): ?>
                            <?php foreach ($grouped_sections as $course_code => $course_data): ?>
                                <div class="schedule-group course-group">
                                    <h4 class="group-header" data-toggle="sections-course-<?= str_replace([' ', '/'], '-', $course_code) ?>">
                                        <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($course_data['course_name']) ?> (<?= htmlspecialchars($course_code) ?>)
                                    </h4>
                                    <div id="sections-course-<?= str_replace([' ', '/'], '-', $course_code) ?>" class="group-content" style="display: none;">
                                        <?php foreach ($course_data['year_levels'] as $year_level => $semesters): ?>
                                            <div class="schedule-group year-group">
                                                <h5 class="group-header" data-toggle="sections-year-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>">
                                                    <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($year_level) ?> Year
                                                </h5>
                                                <div id="sections-year-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>" class="group-content" style="display: none;">
                                                    <?php foreach ($semesters as $semester => $sections_in_sem): ?>
                                                        <div class="schedule-group semester-group">
                                                            <h6 class="group-header" data-toggle="sections-sem-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>">
                                                                <i class="fas fa-chevron-right toggle-icon"></i> <?= htmlspecialchars($semester) ?> Semester
                                                            </h6>
                                                            <div id="sections-sem-<?= str_replace([' ', '/'], '-', $course_code) ?>-<?= str_replace([' ', '/'], '-', $year_level) ?>-<?= str_replace([' ', '/'], '-', $semester) ?>" class="group-content" style="display: none;">
                                                                <table class="data-table">
                                                                    <thead><tr><th>Section Name</th><th>Actions</th></tr></thead>
                                                                    <tbody>
                                                                        <?php foreach ($sections_in_sem as $section): ?>
                                                                        <tr>
                                                                            <td><?= htmlspecialchars($section['section_name']) ?></td>
                                                                            <td class="action-links"><a href="#"><i class="fa-solid fa-pencil"></i> Edit</a></td>
                                                                        </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align:center; margin-top: 2rem;">No sections have been added yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reports Section -->
            <div id="reports" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-chart-bar"></i> Reports</h2>
                </div>
                <p>Generate and view various system reports, such as enrollment statistics and class lists.</p>
            </div>

            <!-- Manage Courses Section -->
            <?php if (hasPermission('manage_courses')): ?>
            <div id="manage-courses" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-graduation-cap"></i> Manage Courses</h2>
                    <?php if (hasPermission('manage_courses')): // Assuming full manage rights for this button ?>
                        <a href="#" class="button" onclick="openAddCourseModal(); return false;"><i class="fa-solid fa-plus"></i> Add Course</a>
                    <?php endif; ?>
                 </div>
                <p>Add, view, and manage all available courses or programs.</p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Course Code</th>
                            <th>Course Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= htmlspecialchars($course['course_code']) ?></td>
                                <td><?= htmlspecialchars($course['course_name']) ?></td>
                                <td class="action-links"><a href="#"><i class="fa-solid fa-pencil"></i> Edit</a></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" style="text-align:center;">No courses found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Manage Departments Section -->
            <?php if (hasPermission('manage_departments')): ?>
            <div id="manage-departments" class="content-section">
                <div class="section-header">
                    <h2 class="section-title-hidden"><i class="fas fa-sitemap"></i> Manage Departments</h2>
                </div>
                <p>Add, view, and manage academic departments.</p>

                <div class="modal-form-grid" style="align-items: flex-start;">
                    <!-- Add Department Form -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Add New Department</h3>
                        </div>
                        <div class="card-body">
                            <form action="../actions/add_department.php" method="POST">
                                <div class="form-input-group">
                                    <label for="department_name">Department Name</label>
                                    <input type="text" class="form-control" id="department_name" name="department_name" placeholder="e.g., Department of Arts" required>
                                </div>
                                <button type="submit" class="button" style="margin-top: 1rem;"><i class="fa-solid fa-plus"></i> Add Department</button>
                            </form>
                        </div>
                    </div>

                    <!-- List of Departments -->
                    <div>
                        <h3>Existing Departments</h3>
                        <table class="data-table">
                            <thead><tr><th>Department Name</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php if (!empty($departments)): ?>
                                    <?php foreach ($departments as $department): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($department['department_name']) ?></td>
                                        <td class="action-links"><a href="#"><i class="fa-solid fa-pencil"></i> Edit</a></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" style="text-align:center;">No departments found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    <div id="editSubjectModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 600px;">
            <span class="close-btn" onclick="closeModal('editSubjectModal')">&times;</span>
            <h2><i class="fa-solid fa-pencil"></i> Edit Subject</h2>
            <form id="editSubjectForm" action="../actions/edit_subject.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" id="edit_subject_id" name="subject_id">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <label for="edit_subject_code">Subject Code:</label>
                        <input type="text" id="edit_subject_code" name="subject_code" required>
                    </div>
                    <div>
                        <label for="edit_subject_name">Subject Name:</label>
                        <input type="text" id="edit_subject_name" name="subject_name" required>
                    </div>
                    <div>
                        <label for="edit_units">Units:</label>
                        <input type="number" id="edit_units" name="units" required min="1" max="6">
                    </div>
                    <div>
                        <label for="edit_course_id">Course:</label>
                        <select id="edit_course_id" name="course_id" required>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_semester">Semester:</label>
                        <select id="edit_semester" name="semester" required>
                            <option value="1st">1st</option>
                            <option value="2nd">2nd</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_status">Status:</label>
                        <select id="edit_status" name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="button" style="margin-top: 20px;"><i class="fa-solid fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div id="editScheduleModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 800px;">
            <span class="close-btn" onclick="closeModal('editScheduleModal')">&times;</span>
            <h2><i class="fa-solid fa-pencil"></i> Edit Schedule</h2>
            <form id="editScheduleForm" action="../actions/edit_schedule.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" id="edit_schedule_id" name="schedule_id">
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <label for="edit_sched_subject_id">Subject:</label>
                        <select id="edit_sched_subject_id" name="subject_id" required>
                            <?php foreach ($subjects as $subject): ?>
                            <?php foreach ($subjects_raw as $subject): ?>
                                <option value="<?= $subject['subject_id'] ?>"><?= htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']) ?></option>
                            <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_sched_instructor_id">Instructor:</label>
                        <select id="edit_sched_instructor_id" name="instructor_id" required>
                            <?php foreach ($all_instructors as $instructor): ?>
                                <option value="<?= $instructor['instructor_id'] ?>"><?= htmlspecialchars($instructor['full_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_sched_room_id">Room:</label>
                        <select id="edit_sched_room_id" name="room_id" required>
                            <?php foreach ($all_rooms as $room): ?>
                                <option value="<?= $room['room_id'] ?>"><?= htmlspecialchars($room['room_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_sched_section_id">Section:</label>
                        <select id="edit_sched_section_id" name="section_id" required>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['course_code'] . ' - ' . $section['section_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="edit_sched_day">Day:</label>
                        <select id="edit_sched_day" name="day" required>
                            <option value="Monday">Monday</option>
                            <option value="Tuesday">Tuesday</option>
                            <option value="Wednesday">Wednesday</option>
                            <option value="Thursday">Thursday</option>
                            <option value="Friday">Friday</option>
                            <option value="Saturday">Saturday</option>
                        </select>
                    </div>
                    <div>
                        <label for="edit_sched_time_start">Time Start:</label>
                        <input type="time" id="edit_sched_time_start" name="time_start" required>
                    </div>
                    <div>
                        <label for="edit_sched_time_end">Time End:</label>
                        <input type="time" id="edit_sched_time_end" name="time_end" required>
                    </div>
                    <div>
                        <label for="edit_sched_school_year">School Year:</label>
                        <input type="text" id="edit_sched_school_year" name="school_year" placeholder="e.g., 2024-2025" required>
                    </div>
                    <div>
                        <label for="edit_sched_semester">Semester:</label>
                        <select id="edit_sched_semester" name="semester" required>
                            <option value="1st">1st</option>
                            <option value="2nd">2nd</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="button" style="margin-top: 20px;"><i class="fa-solid fa-save"></i> Save Changes</button>
            </form>
        </div>
    </div>


    <!-- Add Instructor Modal -->
<div id="addInstructorModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width: 650px;">
        <div class="modal-header">
            <h2><i class="fa-solid fa-user-plus"></i> Add New Instructor</h2>
            <span class="close-btn" onclick="closeModal('addInstructorModal')">&times;</span>
        </div>
        <form id="addInstructorForm" action="add_instructor.php" method="POST">
            <div class="modal-form-grid">
                <div class="form-input-group">
                    <label for="add_instructor_firstname">First Name</label>
                    <input type="text" id="add_instructor_firstname" name="first_name" placeholder="Enter first name" required>
                </div>
                <div class="form-input-group">
                    <label for="add_instructor_lastname">Last Name</label>
                    <input type="text" id="add_instructor_lastname" name="last_name" placeholder="Enter last name" required>
                </div>
                <div class="form-input-group full-width">
                    <label for="add_instructor_department">Department</label>
                    <select id="add_instructor_department" name="department_id" required>
                        <option value="" disabled selected>Assign to a department</option><?php foreach ($departments as $department): ?><option value="<?= $department['department_id'] ?>"><?= htmlspecialchars($department['department_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>

                <div class="form-input-group full-width">
                    <label for="add_instructor_email">Email Address</label>
                    <input type="email" id="add_instructor_email" name="email" placeholder="e.g., instructor@example.com" required>
                </div>
                <div class="form-input-group full-width">
                    <label for="add_instructor_username">Username</label>
                    <input type="text" id="add_instructor_username" name="username" placeholder="Create a username" required>
                </div>

                <div class="form-input-group full-width">
                    <label for="add_instructor_password">Password</label>
                    <input type="password" id="add_instructor_password" name="password" placeholder="Create a strong password" required>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="button secondary-btn" onclick="closeModal('addInstructorModal')">Cancel</button>
                <button type="submit" class="button"><i class="fa-solid fa-user-plus"></i> Create Instructor</button>
            </div>
        </form>
    </div>
</div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-user-plus"></i> Add New Student</h2>
                <span class="close-btn" onclick="closeModal('addStudentModal')">&times;</span>
            </div>
            <form id="addStudentForm" action="../actions/add_student.php" method="POST">
                <div class="modal-form-grid">
                    <div class="form-input-group">
                        <label for="add_student_firstname">First Name</label>
                        <input type="text" id="add_student_firstname" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div class="form-input-group">
                        <label for="add_student_lastname">Last Name</label>
                        <input type="text" id="add_student_lastname" name="last_name" placeholder="Enter last name" required>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_student_course">Course</label>
                        <select id="add_student_course" name="course_id" required>
                            <option value="" disabled selected>Search for a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_student_section">Section</label>
                        <select id="add_student_section" name="section_id" required>
                            <option value="" disabled selected>Assign to a section</option>
                            <?php foreach ($sections as $section): ?><option value="<?= $section['section_id'] ?>"><?= htmlspecialchars($section['course_code'] . ' ' . $section['year_level'] . ' - ' . $section['section_name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_student_email">Email Address</label>
                        <input type="email" id="add_student_email" name="email" placeholder="e.g., student@example.com" required>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_student_username">Username</label>
                        <input type="text" id="add_student_username" name="username" placeholder="Create a username" required>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_student_password">Password</label>
                        <input type="password" id="add_student_password" name="password" placeholder="Create a strong password" required>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="button secondary-btn" onclick="closeModal('addStudentModal')">Cancel</button>
                    <button type="submit" class="button"><i class="fa-solid fa-user-plus"></i> Create Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div id="addSubjectModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-book-medical"></i> Add New Subject</h2>
                <span class="close-btn" onclick="closeModal('addSubjectModal')">&times;</span>
            </div>
            <form id="addSubjectForm" action="../actions/add_subject.php" method="POST">
                <div class="modal-form-grid">
                    <div class="form-input-group">
                        <label for="add_subject_code">Subject Code</label>
                        <input type="text" id="add_subject_code" name="subject_code" placeholder="e.g., IT101" required>
                    </div>
                    <div class="form-input-group">
                        <label for="add_subject_name">Subject Name</label>
                        <input type="text" id="add_subject_name" name="subject_name" placeholder="e.g., Introduction to IT" required>
                    </div>
                    <div class="form-input-group full-width">
                        <label for="add_subject_course">Course</label>
                        <select id="add_subject_course" name="course_id" required>
                            <option value="" disabled selected>Search for a course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-input-group">
                        <label for="add_subject_year_level">Year Level</label>
                        <select id="add_subject_year_level" name="year_level" required>
                            <option value="1st">1st Year</option>
                            <option value="2nd">2nd Year</option>
                            <option value="3rd">3rd Year</option>
                            <option value="4th">4th Year</option>
                            <option value="5th">5th Year</option>
                        </select>
                    </div>
                    <div class="form-input-group">
                        <label for="add_subject_units">Units</label>
                        <input type="number" id="add_subject_units" name="units" min="1" max="6" placeholder="e.g., 3" required>
                    </div>
                    <div class="form-input-group">
                        <label for="add_subject_semester">Semester</label>
                        <select id="add_subject_semester" name="semester" required>
                            <option value="1st">1st Semester</option>
                            <option value="2nd">2nd Semester</option>
                            <option value="Summer">Summer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="button secondary-btn" onclick="closeModal('addSubjectModal')">Cancel</button>
                    <button type="submit" class="button"><i class="fa-solid fa-plus"></i> Create Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Room Modal -->
    <div id="addRoomModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 500px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-door-open"></i> Add New Room</h2>
                <span class="close-btn" onclick="closeModal('addRoomModal')">&times;</span>
            </div>
            <form id="addRoomForm" action="../actions/add_room.php" method="POST" style="margin-top: 1rem;">
                <div class="form-input-group">
                    <label for="add_room_name">Room Name / Number</label>
                    <input type="text" id="add_room_name" name="room_name" placeholder="e.g., Room 101 or Computer Lab 2" required>
                </div>
                <div class="form-input-group" style="margin-bottom: 0;">
                    <label for="add_room_department">Department</label>
                    <select id="add_room_department" name="department_id" required>
                        <option value="" disabled selected>Assign to a department</option><?php foreach ($departments as $department): ?><option value="<?= $department['department_id'] ?>"><?= htmlspecialchars($department['department_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-buttons" style="margin-top: 1.5rem;">
                    <button type="button" class="button secondary-btn" onclick="closeModal('addRoomModal')">Cancel</button>
                    <button type="submit" class="button"><i class="fa-solid fa-plus"></i> Add Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Section Modal -->
    <div id="addSectionModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 550px;">
            <div class="modal-header">
                <h2><i class="fa-solid fa-users"></i> Add New Section</h2>
                <span class="close-btn" onclick="closeModal('addSectionModal')">&times;</span>
            </div>
            <form id="addSectionForm" action="../actions/add_section.php" method="POST">
                <div class="form-input-group">
                    <label for="add_section_name">Section Name</label>
                    <input type="text" id="add_section_name" name="section_name" placeholder="e.g., Block A, BSIT-4A" required>
                </div>
                <div class="form-input-group">
                    <label for="add_section_course">Course</label>
                    <select id="add_section_course" name="course_id" required><option value="" disabled selected>Assign to a course</option><?php foreach ($courses as $course): ?><option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="form-input-group">
                    <label for="add_section_year">Year Level</label>
                    <input type="text" id="add_section_year" name="year_level" placeholder="e.g., 1st, 2nd" required>
                </div>
                <div class="form-input-group" style="margin-bottom: 0;">
                    <label for="add_section_semester">Semester</label>
                    <select id="add_section_semester" name="semester" required>
                        <option value="1st Semester">1st Semester</option><option value="2nd Semester">2nd Semester</option><option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="button secondary-btn" onclick="closeModal('addSectionModal')">Cancel</button>
                    <button type="submit" class="button"><i class="fa-solid fa-plus"></i> Add Section</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div id="addCourseModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header">
                <h2><i class="fas fa-graduation-cap"></i> Add New Course</h2>
                <span class="close-btn" onclick="closeModal('addCourseModal')">&times;</span>
            </div>
            <form id="addCourseForm" action="../actions/add_course.php" method="POST">
                <div class="form-input-group">
                    <label for="add_course_code">Course Code</label>
                    <input type="text" id="add_course_code" name="course_code" placeholder="e.g., BSIT, BSED" required>
                </div>
                <div class="form-input-group">
                    <label for="add_course_name">Course Name</label>
                    <input type="text" id="add_course_name" name="course_name" placeholder="e.g., Bachelor of Science in Information Technology" required>
                </div>
                <div class="form-input-group" style="margin-bottom: 0;">
                    <label for="add_course_department">Department</label>
                    <select id="add_course_department" name="department_id" required><option value="" disabled selected>Assign to a department</option><?php foreach ($departments as $department): ?><option value="<?= $department['department_id'] ?>"><?= htmlspecialchars($department['department_name']) ?></option><?php endforeach; ?></select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="button secondary-btn" onclick="closeModal('addCourseModal')">Cancel</button>
                    <button type="submit" class="button"><i class="fa-solid fa-plus"></i> Add Course</button>
                </div>
            </form>
        </div>
    </div>

    <!-- jQuery and Select2 JS for searchable dropdowns -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom Admin Dashboard Script -->
    <script src="../../assets/js/admin_dashboard.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- START: Collapsible Group Logic ---
            document.querySelectorAll('.schedule-group .group-header').forEach(header => {
                header.addEventListener('click', function() {
                    const targetId = this.dataset.toggle;
                    const targetContent = document.getElementById(targetId);
                    if (targetContent) {
                        const isExpanded = targetContent.style.display === 'block';
                        targetContent.style.display = isExpanded ? 'none' : 'block';
                        this.classList.toggle('expanded', !isExpanded);
                    }
                });
            });
            // --- END: Collapsible Group Logic ---

            // --- START: Default View Logic ---
            // This ensures the correct section is shown on page load.
            const dashboardLink = document.querySelector('a[onclick*="dashboard-home"]');
            if (dashboardLink) {
                dashboardLink.click(); // Show dashboard if permission exists
            } else {
                const firstMenuItemLink = document.querySelector('.sidebar .menu-items a');
                if (firstMenuItemLink) firstMenuItemLink.click(); // Otherwise, show the first available item
            }
            // --- END: Default View Logic ---

            // --- START: Dependent Dropdown Logic for Add Schedule Modal ---
            const courseFilter = document.getElementById('schedule_filter_course');
            const yearFilter = document.getElementById('schedule_filter_year');
            const semesterFilter = document.getElementById('schedule_filter_semester');
            const detailsContainer = document.getElementById('schedule-details-container');

            const subjectSelect = document.getElementById('add_sched_subject_id');
            const sectionSelect = document.getElementById('add_sched_section_id');
            const roomSelect = document.getElementById('add_sched_room_id');
            const instructorSelect = document.getElementById('add_sched_instructor_id');

            function fetchOptions() {
                const courseId = courseFilter.value;
                const year = yearFilter.value;
                const semester = semesterFilter.value;

                if (!courseId) {
                    detailsContainer.style.display = 'none';
                    return;
                }
                
                detailsContainer.style.display = 'grid';
                const url = `get_schedule_options.php?course_id=${courseId}&year_level=${year}&semester=${semester}`;

                fetch(url)
                    .then(response => response.json())
                    .then(data => {
                        // Populate Subjects
                        populateSelect(subjectSelect, data.subjects, 'subject_id', item => `${item.subject_code} - ${item.subject_name}`, 'Select a subject...');
                        
                        // Populate Sections
                        populateSelect(sectionSelect, data.sections, 'section_id', 'section_name', 'Select a section...');

                        // Populate Rooms
                        populateSelect(roomSelect, data.rooms, 'room_id', 'room_name', 'Select a room...');

                        // Populate Instructors
                        populateSelect(instructorSelect, data.instructors, 'instructor_id', 'full_name', 'Select an instructor...');
                    })
                    .catch(error => console.error('Error fetching schedule options:', error));
            }

            function populateSelect(selectElement, items, valueKey, textKey, placeholder) {
                selectElement.innerHTML = `<option value="" disabled selected>${placeholder}</option>`;
                if (items.length > 0) {
                    items.forEach(item => {
                        const option = document.createElement('option');
                        option.value = item[valueKey];
                        option.textContent = (typeof textKey === 'function') ? textKey(item) : item[textKey];
                        selectElement.appendChild(option);
                    });
                } else {
                    selectElement.innerHTML = `<option value="" disabled selected>No options available</option>`;
                }
            }

            courseFilter.addEventListener('change', fetchOptions);
            yearFilter.addEventListener('change', fetchOptions);
            semesterFilter.addEventListener('change', fetchOptions);
            // --- END: Dependent Dropdown Logic ---
        });
    </script>
</body>
</html>
