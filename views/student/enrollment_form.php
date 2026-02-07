<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student') { header("Location: ../../index.php"); exit(); }

$user_id = $_SESSION['user_id'];
$page_title = 'Enrollment Form';

// --- Define Current School Year & Semester ---
// This should be managed by a superadmin in a settings page in a real application.
$current_sy = get_active_sy($conn);
$current_sem = get_active_semester($conn);

// --- Fetch Student's Course and Year Level ---
$student_info = null;
$sql_student = "SELECT student_id, course_id, year_level 
                FROM students
                WHERE user_id = ?";
$stmt_student = $conn->prepare($sql_student);
$stmt_student->bind_param("i", $user_id);
$stmt_student->execute();
$result_student = $stmt_student->get_result();
if ($result_student->num_rows > 0) {
    $student_info = $result_student->fetch_assoc();
}
$stmt_student->close();

// --- Fetch all available courses for new students ---
$all_courses = [];
if (!$student_info) { // Only fetch if the student is new
    $sql_courses = "SELECT course_id, course_name FROM courses ORDER BY course_name";
    $result_courses = $conn->query($sql_courses);
    while ($row = $result_courses->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// --- Check for Existing Enrollment Request ---
$existing_enrollment = null;
$student_id_to_check = $student_info['student_id'] ?? 0; // Use 0 if student doesn't exist yet
if ($student_id_to_check > 0) {
    $sql_check = "SELECT status, date_submitted FROM enrollments WHERE student_id = ? AND school_year = ? AND semester = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("iss", $student_id_to_check, $current_sy, $current_sem);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows > 0) {
        $existing_enrollment = $result_check->fetch_assoc();
    }
    $stmt_check->close();
}

// If student is already approved, redirect them to the main enrollment page
// which will then display the "You are Already Enrolled!" message.
if ($existing_enrollment && $existing_enrollment['status'] === 'approved') {
    header("Location: enrollment.php");
    exit();
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
    <style>
        .subject-list { list-style-type: none; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; margin-top: 10px; }
        .subject-list li { display: flex; justify-content: space-between; align-items: center; padding: 12px 15px; border-bottom: 1px solid #e9ecef; transition: background-color 0.2s ease-in-out; cursor: pointer; }
        .subject-list li:hover { background-color: #e9ecef; }
        .subject-list li.header { font-weight: bold; background-color: #e9ecef; }
        .subject-list li.no-subjects { justify-content: center; color: #6c757d; }
        .subject-list li label { flex-grow: 1; cursor: pointer; display: flex; align-items: center; gap: 15px; }
        .subject-list li:last-child { border-bottom: none; }

        /* Custom Checkbox Styling */
        .subject-list li input[type="checkbox"] {
            appearance: none;
            -webkit-appearance: none;
            width: 20px;
            height: 20px;
            border: 2px solid #adb5bd;
            border-radius: 4px;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            flex-shrink: 0; /* Prevent checkbox from shrinking */
        }
        .subject-list li input[type="checkbox"]:checked {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        .subject-list li input[type="checkbox"]:checked::before {
            content: '\f00c'; /* Font Awesome check icon */
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            color: white;
            font-size: 12px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><?= $page_title ?></h2>
                <?php include '../../includes/user_info_header.php'; ?>
            </div>
            <div class="card" style="max-width: 700px; margin: 2rem auto;">
                <h3>Enrollment for S.Y. <?= htmlspecialchars($current_sy) ?>, <?= htmlspecialchars($current_sem) ?> Semester</h3>
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert-message success">Your enrollment request has been submitted successfully! Please wait for admin approval.</div>
                <?php elseif (isset($_GET['error'])): ?>
                    <div class="alert-message error">There was an error submitting your request. Please try again.</div>
                <?php endif; ?>

                <?php if ($existing_enrollment): ?>
                    <?php if ($existing_enrollment['status'] !== 'approved'): // Only show this if not approved, as approved will redirect ?>
                            <h4>You have an existing enrollment request.</h4>
                            <p>Your request submitted on <?= date('F j, Y', strtotime($existing_enrollment['date_submitted'])) ?> is currently <strong><?= ucfirst($existing_enrollment['status']) ?></strong>.</p>
                            <p>You cannot submit a new request at this time.</p>
                        </div>
                    <?php endif; ?>
                <?php elseif ($student_info || !empty($all_courses)): ?>
                    <!-- Form for ALL students (New and Existing) -->
                    <form action="../actions/submit_enrollment.php" method="POST" id="enrollmentForm">
                        <?php if ($student_info): ?>
                            <input type="hidden" name="student_id" value="<?= $student_info['student_id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="user_id" value="<?= $user_id ?>">
                        <?php endif; ?>
                        <input type="hidden" name="school_year" value="<?= $current_sy ?>">
                        
                        <div class="modal-form-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1rem;">
                            <!-- Course Selection -->
                            <div class="form-input-group">
                                <label for="course_id">Course</label>
                                <?php if ($student_info): ?>
                                    <input type="text" value="<?= htmlspecialchars(get_course_name($conn, $student_info['course_id'])) ?>" readonly>
                                    <input type="hidden" name="course_id" id="course_id" value="<?= $student_info['course_id'] ?>">
                                <?php else: ?>
                                    <select name="course_id" id="course_id" required>
                                        <option value="" disabled selected>-- Choose your course --</option>
                                        <?php foreach ($all_courses as $course): ?>
                                            <option value="<?= $course['course_id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <!-- Year Level Selection -->
                            <div class="form-input-group">
                                <label for="year_level">Year Level</label>
                                <?php if ($student_info): ?>
                                    <select name="year_level" id="year_level" required>
                                        <?php foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $year): ?>
                                            <option value="<?= $year ?>" <?= ($student_info['year_level'] == $year) ? 'selected' : '' ?>><?= $year ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" value="1st Year" readonly>
                                    <input type="hidden" name="year_level" id="year_level" value="1st Year">
                                <?php endif; ?>
                            </div>

                            <!-- Semester Selection -->
                            <div class="form-input-group">
                                <label for="semester">Semester</label>
                                <?php if ($student_info): ?>
                                    <select name="semester" id="semester" required>
                                        <option value="1st" <?= ($current_sem == '1st') ? 'selected' : '' ?>>1st Semester</option>
                                        <option value="2nd" <?= ($current_sem == '2nd') ? 'selected' : '' ?>>2nd Semester</option>
                                    </select>
                                <?php else: ?>
                                    <input type="text" value="1st Semester" readonly>
                                    <input type="hidden" name="semester" id="semester" value="1st">
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Section Selection (appears after course/year/sem are chosen) -->
                        <div class="form-input-group" style="margin-top: 1.5rem;">
                            <label for="section_id">Section</label>
                            <select name="section_id" id="section_id" required disabled><option value="">-- Select a section --</option></select>
                        </div>

                        <div class="form-input-group" style="margin-top: 1.5rem;">
                            <label>Available Subjects to Enroll</label>
                            <p style="font-size: 0.9rem; color: #6c757d; margin-top: -5px;">Please select the subjects you wish to enroll in for this semester.</p>
                            <ul id="subjectList" class="subject-list">
                                <li class="no-subjects">Please select a course and semester to see available subjects.</li>
                            </ul>
                        </div>
                        <div style="text-align: right; margin-top: 2rem;">
                            <button type="submit" id="submitBtn" class="button" disabled><i class="fas fa-paper-plane"></i> Submit Enrollment Request</button>
                        </div>
                    </form>
                <?php else: ?>
                    <p>No courses are available for enrollment at this time, or your student data is incomplete. Please contact the administration.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const courseSelect = document.getElementById('course_id');
    const semesterSelect = document.getElementById('semester');
    const yearLevelSelect = document.getElementById('year_level');
    const sectionSelect = document.getElementById('section_id');
    const subjectList = document.getElementById('subjectList');
    const submitBtn = document.getElementById('submitBtn');

    function fetchSubjects() {
        const courseId = courseSelect.value;
        const yearLevel = yearLevelSelect.value;
        const semester = semesterSelect.value;
        const sectionId = sectionSelect.value;

        // Clear subjects and disable submit button
        subjectList.innerHTML = '<li class="no-subjects">Loading subjects...</li>';
        submitBtn.disabled = true;

        if (!sectionId) {
            subjectList.innerHTML = '<li class="no-subjects">Please select a section to see its subjects.</li>';
            return;
        }

        // Fetch subjects for the selected section
        fetch(`../actions/get_subjects.php?section_id=${sectionId}`)
            .then(response => response.json())
            .then(data => {
                subjectList.innerHTML = ''; // Clear list
                if (data.status === 'success' && data.subjects.length > 0) {
                    const header = document.createElement('li');
                    header.className = 'header';
                    header.innerHTML = `<span>Subject</span><span>Units</span>`;
                    subjectList.appendChild(header);

                    data.subjects.forEach(subject => {
                        const li = document.createElement('li');
                        // Subjects are pre-selected and hidden, as they are part of the section
                        // Changed to be visible and selectable by the student.
                        li.innerHTML = `
                            <label for="subject-${subject.subject_id}">
                                <input type="checkbox" name="subjects[]" value="${subject.subject_id}" id="subject-${subject.subject_id}">
                                ${subject.subject_code} - ${subject.subject_name}
                            </label>
                            <strong>${subject.units}</strong>
                        `;
                        // Make the whole li clickable
                        li.addEventListener('click', (e) => { if (e.target.tagName !== 'INPUT') { li.querySelector('input').click(); } });
                        subjectList.appendChild(li);
                    });
                    submitBtn.disabled = false; // Enable submit button once subjects are loaded
                } else {
                    subjectList.innerHTML = '<li class="no-subjects">No subjects are scheduled for this section yet.</li>';
                }
            })
            .catch(error => {
                console.error('Error fetching subjects:', error);
                subjectList.innerHTML = '<li class="no-subjects">Error loading subjects.</li>';
            });
    }

    function fetchSections() {
        const courseId = courseSelect.value;
        const yearLevel = yearLevelSelect.value;

        // Reset section and subject lists
        sectionSelect.innerHTML = '<option value="">-- Loading sections... --</option>';
        sectionSelect.disabled = true;
        subjectList.innerHTML = '<li class="no-subjects">Please select a section to see its subjects.</li>';
        submitBtn.disabled = true;

        if (!courseId || !yearLevel) {
            sectionSelect.innerHTML = '<option value="">-- Select course and year level first --</option>';
            return;
        }

        fetch(`../actions/get_sections.php?course_id=${courseId}&year_level=${yearLevel}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">-- Select a section --</option>';
                if (data.status === 'success' && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section.section_id;
                        option.textContent = section.section_name;
                        sectionSelect.appendChild(option);
                    });
                    sectionSelect.disabled = false;
                } else {
                    sectionSelect.innerHTML = '<option value="">-- No sections available --</option>';
                }
            })
            .catch(error => {
                console.error('Error fetching sections:', error);
                sectionSelect.innerHTML = '<option value="">-- Error loading sections --</option>';
            });
    }

    // Event Listeners
    courseSelect?.addEventListener('change', fetchSections);
    semesterSelect?.addEventListener('change', fetchSections);
    sectionSelect?.addEventListener('change', fetchSubjects);
    if(yearLevelSelect && yearLevelSelect.tagName === 'SELECT') { // Add listener only if it's a dropdown
        yearLevelSelect.addEventListener('change', fetchSections);
    }

    // Initial fetch if course is pre-selected for existing students
    if (courseSelect && courseSelect.value) {
        fetchSections();
    }
});
</script>
</html>
<?php $conn->close(); ?>