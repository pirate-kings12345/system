<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid Enrollment ID.');
}

$enrollment_id = (int)$_GET['id'];

// Fetch Enrollment Details
$enrollment_details = null;
$sql = "SELECT 
            e.enrollment_id, e.school_year, e.semester, e.date_submitted,
            u.user_id, u.username, u.email,
            sp.full_name,
            st.year_level,
            c.course_code, c.course_name,
            sec.section_name,
            st.section_id -- Fetch the student's section_id
        FROM enrollments e
        JOIN students st ON e.student_id = st.student_id
        JOIN users u ON st.user_id = u.user_id 
        LEFT JOIN student_profiles sp ON u.user_id = sp.user_id
        LEFT JOIN courses c ON st.course_id = c.course_id
        LEFT JOIN sections sec ON st.section_id = sec.section_id
        WHERE e.enrollment_id = ? AND e.status = 'approved'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $enrollment_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $enrollment_details = $result->fetch_assoc();
} else {
    die('Enrollment record not found or not approved.');
}
$stmt->close();
$student_section_id = $enrollment_details['section_id']; // Store the section_id

// Fetch Enrolled Subjects for this enrollment
$enrolled_subjects = [];
$subjectsSql = "SELECT 
                    s.subject_code, s.subject_name, s.units,
                    sch.day,
                    TIME_FORMAT(sch.time_start, '%h:%i %p') AS time_start,
                    TIME_FORMAT(sch.time_end, '%h:%i %p') AS time_end,
                    r.room_name,
                    CONCAT(i.first_name, ' ', i.last_name) AS instructor_name
                FROM enrollment_subjects es 
                JOIN subjects s ON es.subject_id = s.subject_id
                JOIN schedules sch ON s.subject_id = sch.subject_id AND sch.section_id = ? -- Use the fetched section_id
                JOIN instructors i ON sch.instructor_id = i.instructor_id
                JOIN rooms r ON sch.room_id = r.room_id
                WHERE es.enrollment_id = ?
                ORDER BY sch.day, sch.time_start";

$stmt = $conn->prepare($subjectsSql);
$stmt->bind_param("ii", $student_section_id, $enrollment_id);
$stmt->execute();
$subjectsResult = $stmt->get_result();
while ($row = $subjectsResult->fetch_assoc()) {
    $enrolled_subjects[] = $row;
}
$stmt->close();
$conn->close();

$total_units = array_sum(array_column($enrolled_subjects, 'units'));

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enrollment Form - <?= htmlspecialchars($enrollment_details['full_name'] ?? $enrollment_details['username']) ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; line-height: 1.4; color: #333; }
        .container { width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header h2 { margin: 5px 0; font-size: 18px; font-weight: normal; }
        .student-info { margin-bottom: 20px; }
        .student-info table { width: 100%; border-collapse: collapse; }
        .student-info th, .student-info td { padding: 5px; text-align: left; }
        .student-info th { width: 150px; font-weight: bold; }
        .subjects-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .subjects-table th, .subjects-table td { border: 1px solid #999; padding: 8px; text-align: left; }
        .subjects-table th { background-color: #f2f2f2; font-weight: bold; }
        .total-units { text-align: right; font-weight: bold; margin-top: 10px; font-size: 14px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #777; }
        @media print {
            body { margin: 0; }
            .container { border: none; width: 100%; margin: 0; padding: 0; }
            .print-button { display: none; }
        }
        .print-button {
            display: block;
            width: 150px;
            margin: 20px auto;
            padding: 10px;
            background-color: #007bff;
            color: white;
            text-align: center;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SchedMaster University</h1>
            <h2>Certificate of Registration</h2>
            <h3><?= htmlspecialchars($enrollment_details['school_year']) ?> - <?= htmlspecialchars($enrollment_details['semester']) ?> Semester</h3>
        </div>

        <div class="student-info">
            <table>
                <tr>
                    <th>Student Name:</th>
                    <td><?= htmlspecialchars($enrollment_details['full_name'] ?? $enrollment_details['username']) ?></td>
                    <th>Student ID:</th>
                    <td><?= htmlspecialchars($enrollment_details['user_id']) ?></td>
                </tr>
                <tr>
                    <th>Course & Year:</th>
                    <td><?= htmlspecialchars($enrollment_details['course_name']) ?> - <?= htmlspecialchars($enrollment_details['year_level']) ?></td>
                    <th>Section:</th>
                    <td><?= htmlspecialchars($enrollment_details['section_name'] ?? 'N/A') ?></td>
                </tr>
                 <tr>
                    <th>Date Enrolled:</th>
                    <td><?= date('F d, Y', strtotime($enrollment_details['date_submitted'])) ?></td>
                    <th>Email:</th>
                    <td><?= htmlspecialchars($enrollment_details['email']) ?></td>
                </tr>
            </table>
        </div>

        <h3>Enrolled Subjects</h3>
        <table class="subjects-table">
            <thead>
                <tr>
                    <th>Subject Code</th>
                    <th>Description</th>
                    <th>Units</th>
                    <th>Schedule (Day & Time)</th>
                    <th>Room</th>
                    <th>Instructor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($enrolled_subjects)): ?>
                    <?php foreach ($enrolled_subjects as $subject): ?>
                        <tr>
                            <td><?= htmlspecialchars($subject['subject_code']) ?></td>
                            <td><?= htmlspecialchars($subject['subject_name']) ?></td>
                            <td><?= htmlspecialchars($subject['units']) ?></td>
                            <td><?= htmlspecialchars($subject['day']) ?> / <?= htmlspecialchars($subject['time_start']) ?> - <?= htmlspecialchars($subject['time_end']) ?></td>
                            <td><?= htmlspecialchars($subject['room_name']) ?></td>
                            <td><?= htmlspecialchars($subject['instructor_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No subjects are currently enrolled for this student.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total-units">
            Total Units: <?= htmlspecialchars($total_units) ?>
        </div>

        <div class="footer">
            This is a system-generated document. Not valid as a receipt.
        </div>
    </div>

    <button class="print-button" onclick="window.print()">Print Form</button>

</body>
</html>