<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn) || !has_permission('student', 'view_grades', $conn)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'You do not have permission to view your grades.'];
    // Redirect back to the dashboard or the previous page
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit();
}

$page_title = 'My Grades';
$user_id = $_SESSION['user_id'];

// Get student details from user_id
$stmt = $conn->prepare("SELECT s.student_id, s.student_number, s.year_level, c.course_code FROM students s JOIN courses c ON s.course_id = c.course_id WHERE s.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['student_id'] ?? 0;
$stmt->close();

// Fetch grades for the logged-in student
$grouped_grades = [];
$sql = "SELECT 
            sub.subject_code,
            sub.subject_name,
            sub.units,
            sg.prelim,
            sg.midterm,
            sg.finals,
            sg.final_grade,
            sg.remarks,
            e.school_year,
            e.semester
        FROM student_grades sg
        JOIN enrollments e ON sg.enrollment_id = e.enrollment_id AND e.status = 'approved'
        JOIN subjects sub ON sg.subject_id = sub.subject_id
        WHERE e.student_id = ?
        ORDER BY e.school_year DESC, e.semester, sub.subject_code";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($grade = $result->fetch_assoc()) {
        $term_key = "S.Y. " . htmlspecialchars($grade['school_year']) . " / " . htmlspecialchars($grade['semester']);
        $grouped_grades[$term_key][] = $grade;
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
    <link rel="stylesheet" href="../../assets/css/my_grades.css">
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
                 <button class="button" onclick="window.print();"><i class="fas fa-print"></i> Print Grades</button>
            </div>

            <!-- ON-SCREEN VERSION -->
            <div id="on-screen-version">
                <div class="card">
                    <h3>Your Academic Grades</h3>
                    <p>This page shows a summary of your grades. Click the "Print Grades" button for a detailed scholastic report.</p>
                    <?php if (!empty($grouped_grades)): ?>
                        <?php foreach ($grouped_grades as $term => $grades): ?>
                            <div class="term-grades" style="margin-top: 2rem;">
                                <h4><?= $term ?></h4>
                                <div class="table-responsive">
                                    <table class="grade-table">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Prelim</th>
                                                <th>Midterm</th>
                                                <th>Finals</th>
                                                <th>Final Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($grades as $grade): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($grade['subject_code']) ?></strong><br><small><?= htmlspecialchars($grade['subject_name']) ?></small></td>
                                                    <td><?= ($grade['prelim'] !== null) ? htmlspecialchars(number_format((float)$grade['prelim'], 2)) : 'N/A' ?></td>
                                                    <td><?= ($grade['midterm'] !== null) ? htmlspecialchars(number_format((float)$grade['midterm'], 2)) : 'N/A' ?></td>
                                                    <td><?= ($grade['finals'] !== null) ? htmlspecialchars(number_format((float)$grade['finals'], 2)) : 'N/A' ?></td>
                                                    <td><strong><?= ($grade['final_grade'] !== null) ? number_format($grade['final_grade'], 2) : 'N/A' ?></strong></td>
                                                    <td><span class="remarks-<?= strtolower(htmlspecialchars($grade['remarks'] ?? 'incomplete')) ?>"><?= htmlspecialchars($grade['remarks'] ?? 'Incomplete') ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align:center; margin-top: 2rem;">No grades are available at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PRINT-ONLY VERSION -->
            <div id="print-version">
                <?php if (!empty($grouped_grades)): ?>
                    <?php foreach ($grouped_grades as $term => $grades): ?>
                        <div class="page-report">
                            <div class="report-logo"><img src="../../assets/images/ascotlogo.png" alt="Institution Logo"></div>
                            <div class="report-content">
                                <div class="report-header">
                                    <h3>Republic of the Philippines</h3>
                                    <h3>Aurora State College of Technology</h3>
                                    <h4>Baler, Aurora</h4>
                                    <h2>OFFICE OF THE REGISTRAR</h2>
                                    <h3>STUDENT'S SCHOLASTIC REPORT</h3>
                                    <h4><?= $term ?> / FINAL GRADE</h4>
                                </div>

                                <table class="info-table">
                                    <tr>
                                        <td style="width:14%;"><b>STUDENT NO</b></td>
                                        <td style="width:36%;">: <?= htmlspecialchars($student['student_number'] ?? 'N/A') ?></td>
                                        <td style="width:12%;"><b>DATE</b></td>
                                        <td style="width:38%;">: <?= date('F j, Y') ?></td>
                                    </tr>
                                    <tr>
                                        <td><b>STUDENT NAME</b></td>
                                        <td>: <?= htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']) ?></td>
                                        <td><b>COURSE/YR</b></td>
                                        <td>: <?= htmlspecialchars($student['course_code'] . '-' . get_year_level_numeric($student['year_level'])) ?></td>
                                    </tr>
                                </table>

                                <table class="grades" aria-label="Grades table">
                                    <thead>
                                        <tr>
                                            <th style="width:12%;">CODE</th>
                                            <th style="text-align:left;">DESCRIPTION</th>
                                            <th style="width:8%;">UNITS</th>
                                            <th style="width:8%;">GRADE</th>
                                            <th style="width:10%;">REMARKS</th>
                                        </tr>
                                    <thead>
                                    <tbody>
                                        <?php
                                        $total_units = 0;
                                        $total_grade_points = 0;
                                        $units_passed = 0;
                                        $units_failed = 0;
                                        foreach ($grades as $grade):
                                            $total_units += $grade['units'];
                                            if ($grade['final_grade'] !== null) {
                                                $total_grade_points += $grade['final_grade'] * $grade['units'];
                                                if (strtolower($grade['remarks']) == 'passed') {
                                                    $units_passed += $grade['units'];
                                                } else {
                                                    $units_failed += $grade['units'];
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($grade['subject_code']) ?></td>
                                            <td style="text-align:left;"><?= htmlspecialchars($grade['subject_name']) ?></td>
                                            <td><?= htmlspecialchars($grade['units']) ?></td>
                                            <td><?= ($grade['final_grade'] !== null) ? number_format($grade['final_grade'], 2) : 'N/A' ?></td>
                                            <td><?= htmlspecialchars($grade['remarks'] ?? 'Incomplete') ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="summary" role="complementary">
                                    <div class="left">
                                        <table style="width:100%; font-size:13px; border-collapse:collapse;">
                                            <tr>
                                                <td style="padding:6px;"><b>TOTAL NO. OF UNITS</b></td>
                                                <td style="padding:6px;"><?= $total_units ?></td>
                                                <td style="padding:6px;"><b>UNITS PASSED</b></td>
                                                <td style="padding:6px;"><?= $units_passed ?></td>
                                                <td style="padding:6px;"><b>UNITS FAILED</b></td>
                                                <td style="padding:6px;"><?= $units_failed ?></td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="right">
                                        <div><b>G.W.A</b></div>
                                        <div style="font-size:20px; font-weight:700; margin-top:6px;">
                                            <?= ($total_units > 0) ? number_format($total_grade_points / $total_units, 2) : 'N/A' ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="report-footer">
                                    <p><b>GRADING SYSTEM – COLLEGE:</b><br>
                                    1.00 = 96-100 | 1.25 = 93-95 | 1.50 = 90-92 | 1.75 = 88-91 | 2.00 = 86-90 | 2.25 = 83-85<br>
                                    2.50 = 80-82 | 2.75 = 77-79 | 3.00 = 75-76 | 4.00 = Conditional | 5.00 = Failed
                                    </p>
                                    <p><b>SUPPLEMENTAL REMARKS:</b><br>
                                    DRP = Dropped | INC = Incomplete | P = Passed | F = Failed | IP = In Progress | UD = Unofficially Dropped
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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