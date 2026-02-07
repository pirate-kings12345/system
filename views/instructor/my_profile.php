<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';

if ($_SESSION['role'] !== 'instructor' || !has_permission('instructor', 'edit_instructor_profile', $conn)) {
    header("Location: ../../auth/login.php?error=unauthorized_or_permission_denied");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Fetch Instructor Info ---
$instructor_info = null;
$sql = "SELECT 
            i.instructor_id, 
            i.first_name,
            i.last_name,
            i.profile_picture_path,
            d.department_name,
            u.email
        FROM users u
        LEFT JOIN instructors i ON u.user_id = i.user_id
        LEFT JOIN departments d ON i.department_id = d.department_id
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $instructor_info = $result->fetch_assoc();
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
        .profile-pic-container { width: 150px; height: 150px; border-radius: 50%; margin: 0 auto 20px; background-color: #e9ecef; display: flex; align-items: center; justify-content: center; border: 4px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; }
        .profile-pic-container img { width: 100%; height: 100%; object-fit: cover; }
        .profile-pic-container i { font-size: 60px; color: #adb5bd; }
        .profile-details dt { font-weight: 600; color: var(--gray); grid-column: 1; text-align: right; }
        .profile-details dd { grid-column: 2; margin-left: 0; }
        .profile-details { display: grid; grid-template-columns: 150px 1fr; gap: 15px 20px; align-items: center; }
        @media (max-width: 992px) { .profile-grid { grid-template-columns: 1fr; } }
        @media (max-width: 768px) { .profile-details { grid-template-columns: 1fr; } .profile-details dt { text-align: left; font-size: 0.8rem; text-transform: uppercase; color: #adb5bd; } }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><?= $page_title ?></h2>
            </div>
            <div class="profile-grid">
                <div class="profile-sidebar">
                    <div class="card" id="profile-card">
                        <form id="profilePicForm" enctype="multipart/form-data">
                            <div class="profile-pic-container">
                                <?php if (!empty($instructor_info['profile_picture_path']) && file_exists('../../' . $instructor_info['profile_picture_path'])): ?>
                                    <img id="profileImage" src="../../<?= htmlspecialchars($instructor_info['profile_picture_path']) ?>?t=<?= time() ?>" alt="Profile Picture">
                                <?php else: ?>
                                    <i id="profileIcon" class="fas fa-user-tie"></i>
                                    <img id="profileImage" src="" alt="Profile Picture" style="display: none;">
                                <?php endif; ?>
                            </div>
                            <input type="file" id="profilePicInput" name="profile_picture" accept="image/jpeg, image/png, image/gif" style="display: none;">
                            <button type="button" id="uploadBtn" class="button secondary-btn" style="width: 100%;"><i class="fas fa-camera"></i> Upload Photo</button>
                        </form>
                    </div>
                </div>
                <div class="profile-main">
                    <div class="card">
                        <h3>Instructor Information</h3>
                        <dl class="profile-details" style="margin-top: 20px;">
                            <dt>Full Name:</dt>
                            <dd><?= htmlspecialchars(trim(($instructor_info['first_name'] ?? '') . ' ' . ($instructor_info['last_name'] ?? 'Not Set'))) ?></dd>
                            <dt>Email Address:</dt>
                            <dd><?= htmlspecialchars($instructor_info['email'] ?? 'Not Set') ?></dd>
                            <dt>Department:</dt>
                            <dd><?= htmlspecialchars($instructor_info['department_name'] ?? 'Not Assigned') ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const uploadBtn = document.getElementById('uploadBtn');
        const profilePicInput = document.getElementById('profilePicInput');

        uploadBtn.addEventListener('click', function() {
            profilePicInput.click();
        });

        profilePicInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('profile_picture', this.files[0]);

                uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                uploadBtn.disabled = true;

                fetch('upload_profile_pic.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
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