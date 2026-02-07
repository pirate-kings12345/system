<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'student') { header("Location: ../../index.php"); exit(); }

$user_id = $_SESSION['user_id'];

// --- Fetch Profile Completion & Personal Info ---
$profile_completion_percentage = 0;
$student_profile = null;
$profile_stmt = $conn->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
if ($profile_result->num_rows > 0) {
    $student_profile = $profile_result->fetch_assoc();
}
$profile_stmt->close();

if ($student_profile) {
    $fields_to_check = [
        'profile_picture_path', 'full_name', 'date_of_birth', 'gender', 'nationality', 'civil_status', 'religion', 'address', 'contact_number',
        'father_full_name', 'father_occupation', 'father_contact_number',
        'mother_full_name', 'mother_occupation', 'mother_contact_number',
        'elementary_school', 'elementary_graduated', 'high_school', 'high_school_graduated',
        'senior_high_school', 'senior_high_graduated', 'college_school', 'college_graduated'
    ];
    $total_fields = count($fields_to_check);
    $filled_fields = 0;
    foreach ($fields_to_check as $field) {
        if (!empty($student_profile[$field])) {
            $filled_fields++;
        }
    }
    $profile_completion_percentage = ($total_fields > 0) ? round(($filled_fields / $total_fields) * 100) : 0;
}

// --- Fetch Academic & User Info ---
$academic_info = null;
$sql = "SELECT 
            st.student_id, 
            st.student_number,
            c.course_name, 
            s.year_level,
            u.email
        FROM users u
        LEFT JOIN students st ON u.user_id = st.user_id
        LEFT JOIN courses c ON st.course_id = c.course_id
        LEFT JOIN sections s ON st.section_id = s.section_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $academic_info = $result->fetch_assoc();
}
$stmt->close();

$page_title = 'My Profile';
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
        .profile-grid { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
        .profile-sidebar .card { text-align: center; }
        .profile-pic-container img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .profile-pic-container { width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .profile-pic-container i { font-size: 60px; color: #adb5bd; }
        .profile-details dt { font-weight: 600; color: var(--gray); grid-column: 1; text-align: right; }
        .profile-details dd { grid-column: 2; margin-left: 0; }
        .profile-details { display: grid; grid-template-columns: 150px 1fr; gap: 15px 20px; align-items: center; }
        .completion-text { font-size: 0.9rem; color: var(--gray); margin-top: 10px; }
        @media (max-width: 992px) { 
            .profile-grid { grid-template-columns: 1fr; } 
        }
        @media (max-width: 768px) {
            .profile-details { grid-template-columns: 1fr; }
            .profile-details dt { text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #adb5bd; }
            .profile-sidebar .card { margin-bottom: 0; }
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
                    <h2><?= $page_title ?></h2>
                </div>
                 <?php include '../../includes/user_info_header.php'; ?>
            </div>
            <div class="profile-grid">
                <div class="profile-sidebar">
                    <div class="card" id="profile-card">
                        <form id="profilePicForm" enctype="multipart/form-data">
                            <div class="profile-pic-container">
                                <?php if (!empty($student_profile['profile_picture_path']) && file_exists('../../' . $student_profile['profile_picture_path'])): ?>
                                    <img id="profileImage" src="../../<?= htmlspecialchars($student_profile['profile_picture_path']) ?>?t=<?= time() ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <i id="profileIcon" class="fas fa-user"></i>
                                    <img id="profileImage" src="" alt="Profile Picture" style="display: none;">
                                <?php endif; ?>
                            </div>
                            <input type="file" id="profilePicInput" name="profile_picture" accept="image/jpeg, image/png, image/gif" style="display: none;">
                            <button type="button" id="uploadBtn" class="button secondary-btn" style="width: 100%;"><i class="fas fa-camera"></i> Upload Photo</button>
                        </form>
                        <hr style="margin: 20px 0; border-color: #f1f1f1;">
                        <h4>Profile Completion</h4>
                        <h2 style="font-size: 2.5rem; color: var(--primary); margin: 5px 0;"><?= $profile_completion_percentage ?>%</h2>
                        <p class="completion-text">Your Profile is <?= $profile_completion_percentage ?>% updated. Please update and complete your personal information.</p>
                        <a href="personal_info.php" class="button" style="width: 100%; margin-top: 15px;">
                            <i class="fas fa-edit"></i> Edit Personal Info
                        </a>
                    </div>
                </div>
                <div class="profile-main">
                    <div class="card">
                        <h3>Student Information</h3>
                        <dl class="profile-details" style="margin-top: 20px;">
                            <dt>Student ID No.:</dt>
                            <dd><?= htmlspecialchars($academic_info['student_number'] ?? 'Not Set') ?></dd>
                            <dt>Student Name:</dt>
                            <dd><?= htmlspecialchars($student_profile['full_name'] ?? 'Not Set') ?></dd>
                            <dt>Program/Degree:</dt>
                            <dd><?= htmlspecialchars($academic_info['course_name'] ?? 'Not Set') ?></dd>
                            <dt>Year Level:</dt>
                            <dd><?= htmlspecialchars($academic_info['year_level'] ?? 'Not Set') ?></dd>
                            <dt>Email Address:</dt>
                            <dd><?= htmlspecialchars($academic_info['email'] ?? 'Not Set') ?></dd>
                            <dt>Mobile No.:</dt>
                            <dd><?= htmlspecialchars($student_profile['contact_number'] ?? 'Not Set') ?></dd>
                            <dt>Address:</dt>
                            <dd><?= htmlspecialchars($student_profile['address'] ?? 'Not Set') ?></dd>
                        </dl>
                    </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        const uploadBtn = document.getElementById('uploadBtn');
        const profilePicInput = document.getElementById('profilePicInput');
        const profileImage = document.getElementById('profileImage');
        const profileIcon = document.getElementById('profileIcon');

        uploadBtn.addEventListener('click', function() {
            profilePicInput.click();
        });

        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', this.files[0]);

                // Show uploading state
                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                uploadBtn.disabled = true;

                fetch('upload_profile_pic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Reload the page to show the new image from the server and update completion %
                        window.location.reload();
                    } else {
                        alert('Upload failed: ' + data.message);
                    }
                })
                .catch(error => alert('An error occurred: ' + error))
                .finally(() => {
                    uploadBtn.innerHTML = '<i class="fas fa-camera"></i> Upload Photo';
                    uploadBtn.disabled = false;
                });
            }
        });
    });
    </script>
</body>
</html>
<?php $conn->close(); ?>