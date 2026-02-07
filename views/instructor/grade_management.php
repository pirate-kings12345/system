<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

if ($_SESSION['role'] !== 'instructor' || !has_permission('instructor', 'manage_grades', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}

// Fetch system settings to check if the module is active
$settings = [];
$settingsResult = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key = 'module_grades'");
if ($settingsResult) {
    while ($row = $settingsResult->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
$is_grade_module_active = isset($settings['module_grades']) && $settings['module_grades'] == '1';

// Fetch the specific announcement about grade submission being enabled
$grade_announcement = null;
$announcementSql = "SELECT content FROM announcements WHERE title = 'Grade Submission Enabled' AND target_role = 'instructor' ORDER BY date_posted DESC LIMIT 1";
$announcementResult = $conn->query($announcementSql);
if ($announcementResult && $announcementResult->num_rows > 0) {
    $grade_announcement = $announcementResult->fetch_assoc();
}

// Get the logged-in instructor's ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();
$instructor_id = $instructor['instructor_id'];
$stmt->close();

// Fetch classes (schedules) for this instructor
$classes = [];
$classSql = "SELECT 
                sch.schedule_id,
                sub.subject_code,
                sub.subject_name,
                sec.section_name,
                crs.course_code,
                sec.year_level
            FROM schedules sch
            JOIN subjects sub ON sch.subject_id = sub.subject_id
            JOIN sections sec ON sch.section_id = sec.section_id
            JOIN courses crs ON sec.course_id = crs.course_id
            WHERE sch.instructor_id = ?
            ORDER BY sub.subject_code, sec.section_name";
$stmt = $conn->prepare($classSql);
$stmt->bind_param("i", $instructor_id);
$stmt->execute();
$classResult = $stmt->get_result();
if ($classResult && $classResult->num_rows > 0) {
    while($row = $classResult->fetch_assoc()) {
        $classes[] = $row;
    }
}
$stmt->close();

$page_title = 'Grade Management';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        .grade-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .grade-table th, .grade-table td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        .grade-table th { background-color: #f8f9fa; }
        .grade-table input { width: 80px; padding: 5px; border-radius: 4px; border: 1px solid #ccc; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group select { width: 100%; padding: 10px; border-radius: 5px; border: 1px solid #ccc; }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><?= $page_title ?></h2>
            </div>

            <?php if (!$is_grade_module_active): ?>
                <div class="alert warning">
                    <i class="fas fa-exclamation-triangle"></i> The Grade Management module is currently disabled by the administrator. You cannot submit grades at this time.
                </div>
            <?php else: ?>
            <?php if ($grade_announcement): ?>
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> <?= htmlspecialchars($grade_announcement['content']) ?>
                </div>
            <?php endif; ?>
            <!-- Container for AJAX messages -->
            <div id="message-container" style="margin-bottom: 15px;"></div>

            <form id="grades-form">
            <div class="card">
                <div class="form-group">
                    <label for="class-select">Select a Class to Manage Grades:</label>
                    <select id="class-select" name="schedule_id">
                        <option value="">-- Select a Class --</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['schedule_id'] ?>">
                                <?= htmlspecialchars($class['subject_code'] . ' - ' . $class['subject_name'] . ' (' . $class['course_code'] . ' ' . $class['year_level'] . ' - ' . $class['section_name'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" id="subject-id-hidden" name="subject_id" value="">
                <table class="grade-table" id="grades-table">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Prelim</th>
                            <th>Midterm</th>
                            <th>Finals</th>
                            <th>GWA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Student rows will be populated by JavaScript -->
                        <tr><td colspan="5" style="text-align:center;">Please select a class to view students.</td></tr>
                    </tbody>
                </table>
                <button class="button" style="margin-top: 20px; display: none;" id="save-grades-btn">Save Grades</button>
            </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        let currentSubjectId = null;

        $('#class-select').on('change', function() {
            const scheduleId = $(this).val();
            const $tbody = $('#grades-table tbody');
            const $saveBtn = $('#save-grades-btn');
            $('#message-container').empty(); // Clear old messages

            if (scheduleId) {
                $.ajax({
                    url: 'get_students_for_grading.php',
                    type: 'GET',
                    data: { schedule_id: scheduleId },
                    dataType: 'json',
                    success: function(response) {
                        $tbody.empty(); // Clear previous student list
                        if (response.success && response.students.length > 0) {
                            currentSubjectId = response.students[0].subject_id;
                            $('#subject-id-hidden').val(currentSubjectId);
                            response.students.forEach(function(student) {
                                const finalGrade = student.final_grade ? parseFloat(student.final_grade).toFixed(2) : 'N/A';
                                const remarks = student.remarks || 'N/A';
                                const hasGrades = student.final_grade !== null && student.final_grade !== 'N/A'; // Check if GWA is present
                                const disabledAttribute = hasGrades ? 'disabled' : '';
                                const rowClass = hasGrades ? 'has-grades' : '';

                                const row = `<tr>
                                    <td>${student.full_name}</td>
                                    <td><input type="number" name="grades[${student.enrollment_id}][prelim]" value="${student.prelim !== null ? student.prelim : ''}" step="0.01" ${disabledAttribute}></td>
                                    <td><input type="number" name="grades[${student.enrollment_id}][midterm]" value="${student.midterm !== null ? student.midterm : ''}" step="0.01" ${disabledAttribute}></td>
                                    <td><input type="number" name="grades[${student.enrollment_id}][finals]" value="${student.finals !== null ? student.finals : ''}" step="0.01" ${disabledAttribute}></td>
                                    <td>${finalGrade} (${remarks})</td>
                                </tr>`;
                                $tbody.append(row);
                            });
                            $saveBtn.show();
                        } else {
                            const message = '<tr><td colspan="5" style="text-align:center; padding: 20px;">No students found for this class.</td></tr>';
                            $tbody.append(message);                            
                            $saveBtn.hide();
                        }
                    },
                    error: function() {
                        $tbody.empty().append('<tr><td colspan="5" style="text-align:center;">Error loading students. Please try again.</td></tr>');
                        $saveBtn.hide();
                    }
                });
            } else {
                $tbody.empty().append('<tr><td colspan="5" style="text-align:center;">Please select a class to view students.</td></tr>');
                $saveBtn.hide();
            }
        });

        $('#grades-form').on('submit', function(e) {
            e.preventDefault();
            const $saveBtn = $('#save-grades-btn');
            const $messageContainer = $('#message-container');

            if ($('#grades-table tbody tr.has-grades').length === $('#grades-table tbody tr').length) {
                $messageContainer.html('<div class="alert info">All grades for this class have already been submitted.</div>');
                return; // Stop submission if all grades are in
            }

            $saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: 'save_grades.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    const alertClass = response.success ? 'alert success' : 'alert danger';
                    $messageContainer.html(`<div class="${alertClass}">${response.message}</div>`);
                    if(response.success) {
                        $('#class-select').trigger('change'); // Refresh the view
                    }
                },
                error: function() {
                    $messageContainer.html('<div class="alert danger">An unexpected error occurred. Please try again.</div>');
                }
            }).always(function() {
                $saveBtn.prop('disabled', false).html('Save Grades');
            });
        });
    });
    </script>
</body>
</html>