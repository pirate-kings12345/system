<?php
require_once '../../includes/session_check.php';
require_once '../../includes/functions.php';
require_once '../../config/db_connect.php';

// Ensure the user is a student
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'student' || !has_permission('student', 'edit_student_profile', $conn)) {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'You do not have permission to edit your profile.'];
    // Redirect back to the dashboard or the previous page
    header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'dashboard.php'));
    exit;
}

$user_id = $_SESSION['user_id'];
$user_display_name = $_SESSION['username'] ?? 'Student';

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Use prepared statements to prevent SQL injection
    $sql = "INSERT INTO student_profiles (user_id, full_name, date_of_birth, gender, nationality, civil_status, religion, address, contact_number, father_full_name, father_occupation, father_contact_number, mother_full_name, mother_occupation, mother_contact_number, elementary_school, elementary_graduated, high_school, high_school_graduated, senior_high_school, senior_high_graduated, college_school, college_graduated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            full_name=VALUES(full_name), date_of_birth=VALUES(date_of_birth), gender=VALUES(gender), nationality=VALUES(nationality), civil_status=VALUES(civil_status), religion=VALUES(religion), address=VALUES(address), contact_number=VALUES(contact_number), father_full_name=VALUES(father_full_name), father_occupation=VALUES(father_occupation), father_contact_number=VALUES(father_contact_number), mother_full_name=VALUES(mother_full_name), mother_occupation=VALUES(mother_occupation), mother_contact_number=VALUES(mother_contact_number), elementary_school=VALUES(elementary_school), elementary_graduated=VALUES(elementary_graduated), high_school=VALUES(high_school), high_school_graduated=VALUES(high_school_graduated), senior_high_school=VALUES(senior_high_school), senior_high_graduated=VALUES(senior_high_graduated), college_school=VALUES(college_school), college_graduated=VALUES(college_graduated)";

    $stmt = $conn->prepare($sql);
    // Bind parameters from $_POST array
    $stmt->bind_param("issssssssssssssssssssss", 
        $user_id, $_POST['full_name'], $_POST['date_of_birth'], $_POST['gender'], $_POST['nationality'], $_POST['civil_status'], $_POST['religion'], $_POST['address'], $_POST['contact_number'], 
        $_POST['father_full_name'], $_POST['father_occupation'], $_POST['father_contact_number'], 
        $_POST['mother_full_name'], $_POST['mother_occupation'], $_POST['mother_contact_number'], 
        $_POST['elementary_school'], $_POST['elementary_graduated'], $_POST['high_school'], $_POST['high_school_graduated'], 
        $_POST['senior_high_school'], $_POST['senior_high_graduated'], $_POST['college_school'], $_POST['college_graduated']
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Personal information updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating information: " . $stmt->error;
    }
    $stmt->close();
    header("Location: personal_info.php"); // Redirect to prevent form resubmission
    exit;
}

// --- Fetch Student's Personal Information ---
$student_data = [];
$sql = "SELECT sp.*, u.email FROM student_profiles sp RIGHT JOIN users u ON sp.user_id = u.user_id WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $student_data = $result->fetch_assoc();
}
$stmt->close();

$page_title = 'Personal Information';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css"> <!-- Reusing admin styles for consistency -->
    <style>
        .info-grid {
            display: grid; grid-template-columns: 150px 1fr; gap: 15px 20px; margin-top: 1rem; align-items: center;
        }
        .info-grid dt {
            font-weight: 600; color: var(--gray); text-align: right;
        }
        .info-grid dd input {
            width: 100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;
        }
        .info-grid dd input[readonly] {
            background-color: #f3f4f6; border-color: transparent;
        }
        .info-grid dd {
            margin-left: 0;
        }
        .card h3 {
            border-bottom: 1px solid var(--light-gray);
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <!-- Sidebar -->
        <?php include '_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div class="header-left" style="flex-grow: 1;">
                    <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
                    <h2><?= htmlspecialchars($page_title) ?></h2>
                </div>
                <div class="header-actions">
                    <button id="editBtn" class="button"><i class="fas fa-edit"></i> Edit</button>
                    <button type="submit" form="personalInfoForm" id="saveBtn" class="button" style="display: none; background-color: var(--success);"><i class="fas fa-save"></i> Save</button>
                    <button type="button" id="cancelBtn" class="button secondary-btn" style="display: none;"><i class="fas fa-times"></i> Cancel</button>
                </div>
            </div>

            <form id="personalInfoForm" method="POST">
                <div class="card">
                    <h3>Personal Information</h3>
                    <dl class="info-grid">
                        <dt>Full Name:</dt> <dd><input type="text" name="full_name" value="<?= htmlspecialchars($student_data['full_name'] ?? '') ?>" readonly></dd>
                        <dt>Date of Birth:</dt> <dd><input type="date" name="date_of_birth" value="<?= htmlspecialchars($student_data['date_of_birth'] ?? '') ?>" readonly></dd>
                        <dt>Gender:</dt> <dd><input type="text" name="gender" value="<?= htmlspecialchars($student_data['gender'] ?? '') ?>" readonly></dd>
                        <dt>Nationality:</dt> <dd><input type="text" name="nationality" value="<?= htmlspecialchars($student_data['nationality'] ?? '') ?>" readonly></dd>
                        <dt>Civil Status:</dt> <dd><input type="text" name="civil_status" value="<?= htmlspecialchars($student_data['civil_status'] ?? '') ?>" readonly></dd>
                        <dt>Religion:</dt> <dd><input type="text" name="religion" value="<?= htmlspecialchars($student_data['religion'] ?? '') ?>" readonly></dd>
                        <dt>Address:</dt> <dd><input type="text" name="address" value="<?= htmlspecialchars($student_data['address'] ?? '') ?>" readonly></dd>
                        <dt>Contact Number:</dt> <dd><input type="text" name="contact_number" value="<?= htmlspecialchars($student_data['contact_number'] ?? '') ?>" readonly></dd>
                        <dt>Email Address:</dt> <dd><input type="email" name="email" value="<?= htmlspecialchars($student_data['email'] ?? '') ?>" readonly style="cursor: not-allowed;"></dd>
                    </dl>
                </div>

                <div class="card">
                    <h3>Parents Information</h3>
                    <dl class="info-grid">
                        <dt>Father's Name:</dt> <dd><input type="text" name="father_full_name" value="<?= htmlspecialchars($student_data['father_full_name'] ?? '') ?>" readonly></dd>
                        <dt>Occupation:</dt> <dd><input type="text" name="father_occupation" value="<?= htmlspecialchars($student_data['father_occupation'] ?? '') ?>" readonly></dd>
                        <dt>Contact Number:</dt> <dd><input type="text" name="father_contact_number" value="<?= htmlspecialchars($student_data['father_contact_number'] ?? '') ?>" readonly></dd>
                        <dt>Mother's Name:</dt> <dd><input type="text" name="mother_full_name" value="<?= htmlspecialchars($student_data['mother_full_name'] ?? '') ?>" readonly></dd>
                        <dt>Occupation:</dt> <dd><input type="text" name="mother_occupation" value="<?= htmlspecialchars($student_data['mother_occupation'] ?? '') ?>" readonly></dd>
                        <dt>Contact Number:</dt> <dd><input type="text" name="mother_contact_number" value="<?= htmlspecialchars($student_data['mother_contact_number'] ?? '') ?>" readonly></dd>
                    </dl>
                </div>

                <div class="card">
                    <h3>Educational Background</h3>
                    <dl class="info-grid">
                        <dt>Elementary:</dt> <dd><input type="text" name="elementary_school" placeholder="School Name" value="<?= htmlspecialchars($student_data['elementary_school'] ?? '') ?>" readonly></dd>
                        <dt>Year Graduated:</dt> <dd><input type="text" name="elementary_graduated" placeholder="YYYY" value="<?= htmlspecialchars($student_data['elementary_graduated'] ?? '') ?>" readonly></dd>
                        <dt>High School:</dt> <dd><input type="text" name="high_school" placeholder="School Name" value="<?= htmlspecialchars($student_data['high_school'] ?? '') ?>" readonly></dd>
                        <dt>Year Graduated:</dt> <dd><input type="text" name="high_school_graduated" placeholder="YYYY" value="<?= htmlspecialchars($student_data['high_school_graduated'] ?? '') ?>" readonly></dd>
                        <dt>Senior High:</dt> <dd><input type="text" name="senior_high_school" placeholder="School Name" value="<?= htmlspecialchars($student_data['senior_high_school'] ?? '') ?>" readonly></dd>
                        <dt>Year Graduated:</dt> <dd><input type="text" name="senior_high_graduated" placeholder="YYYY" value="<?= htmlspecialchars($student_data['senior_high_graduated'] ?? '') ?>" readonly></dd>
                        <dt>College:</dt> <dd><input type="text" name="college_school" placeholder="School Name" value="<?= htmlspecialchars($student_data['college_school'] ?? '') ?>" readonly></dd>
                        <dt>Year Graduated:</dt> <dd><input type="text" name="college_graduated" placeholder="YYYY or N/A" value="<?= htmlspecialchars($student_data['college_graduated'] ?? '') ?>" readonly></dd>
                    </dl>
                </div>
            </form>

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
        document.addEventListener('DOMContentLoaded', function() {
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const formInputs = document.querySelectorAll('#personalInfoForm input:not([name="email"])');

            function setFormEditable(isEditable) {
                formInputs.forEach(input => {
                    input.readOnly = !isEditable;
                });
                editBtn.style.display = isEditable ? 'none' : 'inline-flex';
                saveBtn.style.display = isEditable ? 'inline-flex' : 'none';
                cancelBtn.style.display = isEditable ? 'inline-flex' : 'none';
            }

            editBtn.addEventListener('click', function() {
                setFormEditable(true);
                // Focus on the first input field
                if(formInputs.length > 0) {
                    formInputs[0].focus();
                }
            });

            cancelBtn.addEventListener('click', function() {
                // Simply reload the page to discard changes from the database
                window.location.reload();
            });
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>