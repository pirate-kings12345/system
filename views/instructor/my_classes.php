<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if ($_SESSION['role'] !== 'instructor' || !has_permission('instructor', 'view_class_lists', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}

$page_title = 'My Classes';
$user_id = $_SESSION['user_id'];

// Get the logged-in instructor's ID
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();
$instructor_id = $instructor['instructor_id'] ?? 0;
$stmt->close();

// Fetch classes (schedules) for this instructor for the active term
$classes = [];
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

    $classSql = "SELECT 
                    sch.schedule_id,
                    sub.subject_code,
                    sub.subject_name,
                    sec.section_name,
                    crs.course_code,
                    sec.year_level,
                    (SELECT COUNT(*) FROM enrollment_subjects es JOIN enrollments e ON es.enrollment_id = e.enrollment_id WHERE e.section_id = sch.section_id AND es.subject_id = sch.subject_id AND e.status = 'approved') as student_count
                FROM schedules sch
                JOIN subjects sub ON sch.subject_id = sub.subject_id
                JOIN sections sec ON sch.section_id = sec.section_id
                JOIN courses crs ON sec.course_id = crs.course_id
                WHERE sch.instructor_id = ? AND sch.school_year = ? AND (sch.semester = ? OR sch.semester = ?)
                ORDER BY sub.subject_code, sec.section_name";
    $stmt = $conn->prepare($classSql);
    $stmt->bind_param("isss", $instructor_id, $current_sy, $semester_short, $semester_long);
    $stmt->execute();
    $classResult = $stmt->get_result();
    if ($classResult && $classResult->num_rows > 0) {
        while($row = $classResult->fetch_assoc()) {
            $classes[] = $row;
        }
    }
    $stmt->close();
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        .class-table tbody tr { cursor: pointer; }
        .class-table tbody tr:hover { background-color: #f1f5f9; }
        .class-table tbody tr.active-row { background-color: #dbeafe; font-weight: bold; }
        #student-list-container { margin-top: 2rem; }
        #student-list-container h4 { margin-bottom: 1rem; }
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
            <div class="card">
                <h3>Your Classes for S.Y. <?= htmlspecialchars($current_sy) ?>, <?= htmlspecialchars($current_sem) ?></h3>
                <p>Click on a class to view the list of enrolled students.</p>
                <table class="data-table class-table">
                    <thead>
                        <tr>
                            <th>Subject Code</th>
                            <th>Subject Name</th>
                            <th>Section</th>
                            <th>Students</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($classes)): ?>
                            <?php foreach ($classes as $class): ?>
                                <tr class="class-row" data-schedule-id="<?= $class['schedule_id'] ?>">
                                    <td><?= htmlspecialchars($class['subject_code']) ?></td>
                                    <td><?= htmlspecialchars($class['subject_name']) ?></td>
                                    <td><?= htmlspecialchars($class['course_code'] . ' ' . $class['year_level'] . ' - ' . $class['section_name']) ?></td>
                                    <td><?= $class['student_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">You have no classes assigned for the current semester.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div id="student-list-container" style="display:none;">
                    <h4>Student Roster</h4>
                    <div id="student-list-content"></div>
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
$(document).ready(function() {
    $('.class-row').on('click', function() {
        const scheduleId = $(this).data('schedule-id');
        const $studentContainer = $('#student-list-container');
        const $studentContent = $('#student-list-content');

        // Highlight active row
        $('.class-row').removeClass('active-row');
        $(this).addClass('active-row');

        $studentContent.html('<p>Loading students...</p>');
        $studentContainer.show();

        $.ajax({
            url: 'get_class_list.php',
            type: 'GET',
            data: { schedule_id: scheduleId },
            dataType: 'json',
            success: function(response) {
                $studentContent.empty();
                if (response.success && response.students.length > 0) {
                    const studentTable = $('<table class="data-table"><thead><tr><th>Student Number</th><th>Student Name</th></tr></thead><tbody></tbody></table>');
                    response.students.forEach(student => {
                        studentTable.find('tbody').append(`<tr><td>${student.student_number || 'N/A'}</td><td>${student.full_name}</td></tr>`);
                    });
                    $studentContent.append(studentTable);
                } else {
                    $studentContent.html('<p>No students are enrolled in this class.</p>');
                }
            },
            error: function() {
                $studentContent.html('<p>An error occurred while fetching the student list.</p>');
            }
        });
    });
});
</script>
</body>
</html>