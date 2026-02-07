<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student' || !has_permission('student', 'view_student_dashboard', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized");
    exit();
}

$page_title = 'Appointment';
$user_id = $_SESSION['user_id'];

// Fetch active school year and semester
$active_sy = get_active_sy($conn);
$active_semester = get_active_semester($conn);

$enrollment_info = null;

// 1. Get the student_id from the user_id
$stmt_student = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();

if ($student_data = $result_student->fetch_assoc()) {
    $student_id = $student_data['student_id'];

    // 2. Find the student's approved enrollment for the current term
    $enrollment_stmt = $conn->prepare(
        "SELECT enrollment_id, status, appointment_date
         FROM enrollments
         WHERE student_id = ? AND school_year = ? AND semester = ?
         ORDER BY date_submitted DESC LIMIT 1"
    );
    $enrollment_stmt->bind_param("iss", $student_id, $active_sy, $active_semester);
    $enrollment_stmt->execute();
    $enrollment_result = $enrollment_stmt->get_result();
    if ($enrollment_row = $enrollment_result->fetch_assoc()) {
        $enrollment_info = $enrollment_row;
    }
    $enrollment_stmt->close();
}
$stmt_student->close();

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
        .appointment-card {
            max-width: 600px;
            margin: 2rem auto;
            text-align: center;
            padding: 40px;
        }
        .appointment-card .icon {
            font-size: 48px;
            margin-bottom: 20px;
        }
        .appointment-card .status-approved { color: #2ecc71; }
        .appointment-card .status-pending { color: var(--warning); }
        .appointment-card .status-not-enrolled { color: var(--danger); }
        .appointment-card .date-display {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 1.2rem;
            font-weight: bold;
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
                    <h2><?= htmlspecialchars($page_title) ?></h2>
                </div>
                 <?php include '../../includes/user_info_header.php'; ?>
            </div>

            <div class="card appointment-card">
                <?php if ($enrollment_info && $enrollment_info['status'] === 'approved'): ?>
                    <i class="fas fa-calendar-check icon status-approved"></i>
                    <h2>Set Your Appointment</h2>
                    <p>Select a date to claim your enrollment form proof from the registrar's office.</p>

                    <?php if ($enrollment_info['appointment_date']): ?>
                        <h4>Your Appointment is Set!</h4>
                        <p>Please proceed to the registrar's office on the date below to claim your form.</p>
                        <div class="date-display">
                            <?= date('F j, Y', strtotime($enrollment_info['appointment_date'])) ?>
                        </div>
                        <p style="margin-top: 1rem; font-size: 0.9rem; color: var(--gray);">To reschedule, please contact the admin office directly.</p>
                    <?php else: ?>
                        <form action="../actions/set_appointment.php" method="POST" style="margin-top: 1.5rem;">
                            <input type="hidden" name="enrollment_id" value="<?= $enrollment_info['enrollment_id'] ?>">
                            <div class="form-input-group">
                                <label for="appointment_date" style="text-align: left;">Select a Date</label>
                                <input type="date" id="appointment_date" name="appointment_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            </div>
                            <button type="submit" class="button" style="margin-top: 1rem; width: 100%;">
                                <i class="fas fa-save"></i> Confirm Appointment
                            </button>
                        </form>
                    <?php endif; ?>

                <?php elseif ($enrollment_info && $enrollment_info['status'] === 'pending'): ?>
                    <i class="fas fa-hourglass-half icon status-pending"></i>
                    <h2>Enrollment Pending</h2>
                    <p>Your enrollment is still under review. Once approved, you can set an appointment on this page.</p>
                <?php else: ?>
                    <i class="fas fa-times-circle icon status-not-enrolled"></i>
                    <h2>Not Enrolled</h2>
                    <p>You must be officially enrolled for the current term (S.Y. <?= htmlspecialchars($active_sy) ?>, <?= htmlspecialchars($active_semester) ?>) to set an appointment.</p>
                    <a href="enrollment.php" class="button" style="margin-top: 1rem;">Go to Enrollment</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="../../assets/js/sidebar.js"></script>
</body>
</html>