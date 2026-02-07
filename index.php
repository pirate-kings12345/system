<?php
session_start();
require_once 'config/db_connect.php';

// Fetch module status for 'module_registration'
$is_registration_module_active = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_registration'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    if ($setting['setting_value'] == '1') {
        $is_registration_module_active = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SchedMaster | ASCOT</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body <?php if (isset($_SESSION['error']) && $_SESSION['error']): ?>data-login-error="true"<?php endif; ?>
      <?php if (isset($_SESSION['register_error']) && $_SESSION['register_error']): ?>data-register-error="true"<?php endif; ?>
      <?php if (isset($_SESSION['register_success']) && $_SESSION['register_success']): ?>data-register-success="true"<?php endif; ?>>
    <!-- Header Section -->
    <header>
        <video autoplay muted loop playsinline class="bg-video">
            <source src="assets/images/ascotdroneshot.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <div class="container header-content">
            <h1 class="logo">SchedMaster</h1>
            <p class="tagline">Smart Scheduling System for Aurora State College of Technology</p>
            <a class="portal-btn" onclick="toggleLoginForm()">Access Your Portal</a>
        </div>
    </header>

    <!-- Login & Portal Selection Section -->
    <section id="login-section">
        <!-- Portal Selection Grid -->
        <div id="portal-selection" class="portal-selection container">
            <h2 class="section-title animate-on-scroll slide-in-up">Choose Your Portal</h2>
            <div class="services-grid animate-on-scroll slide-in-up">
                <div class="service-card portal-student" onclick="window.location.href='login.php?role=student'">
                    <div class="service-icon">🎓</div>
                    <h3 class="service-title">Student Portal</h3>
                    <p>Access your schedules, grades, and enrollment information.</p>
                </div>
                <div class="service-card portal-instructor" onclick="window.location.href='login.php?role=instructor'">
                    <div class="service-icon">🧑‍🏫</div>
                    <h3 class="service-title">Instructor Portal</h3>
                    <p>Manage your class loads, schedules, and student records.</p>
                </div>
                <div class="service-card portal-admin" onclick="window.location.href='login.php?role=admin'">
                    <div class="service-icon">⚙️</div>
                    <h3 class="service-title">Admin Portal</h3>
                    <p>Oversee system settings and manage academic operations.</p>
                </div>
                <div class="service-card portal-superadmin" onclick="window.location.href='login.php?role=superadmin'">
                    <div class="service-icon">👑</div>
                    <h3 class="service-title">Super Admin Portal</h3>
                    <p>Full system administration and user management.</p>
                </div>
                <?php if ($is_registration_module_active): ?>
                <div class="service-card portal-register" onclick="window.location.href='register.php'">
                    <div class="service-icon">📝</div>
                    <h3 class="service-title">Register Account</h3>
                    <p>Create a new student account to access the portal.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services">
        <div class="container">
            <h2 class="section-title animate-on-scroll slide-in-up">Our Digital Services</h2>
            <div class="services-grid animate-on-scroll slide-in-up">
                <div class="service-card">
                    <div class="service-icon">👤</div>
                    <h3 class="service-title">Student Portal</h3>
                    <p>Manage your personal and academic information in one secure place.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📅</div>
                    <h3 class="service-title">Class Scheduling</h3>
                    <p>View your class schedules, room assignments, and instructor details seamlessly.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📋</div>
                    <h3 class="service-title">Enrollment System</h3>
                    <p>Enroll in subjects, manage your academic load, and track your progress.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📊</div>
                    <h3 class="service-title">Grade Viewing</h3>
                    <p>Access your academic grades and performance records anytime, anywhere.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text animate-on-scroll slide-in-left">
                    <h2 class="about-title">About ASCOT</h2>
                    <p class="about-description">Aurora State College of Technology is a public higher education institution committed to advancing technological excellence, sustainable development, and inclusive growth in Aurora and beyond. ASCOT has always been at the forefront of progress in Aurora since its establishment on December 30, 1993, through Republic Act No. 7664.</p>
                </div>
                <div class="photo-grid animate-on-scroll slide-in-right">
                    <img src="assets/images/about1.jpg" alt="ASCOT Campus Image 1">
                    <img src="assets/images/about2.jpg" alt="ASCOT Campus Image 2">
                    <img src="assets/images/about3.jpg" alt="ASCOT Campus Image 3">
                    <img src="assets/images/about4.jpg" alt="ASCOT Campus Image 4">
                    <img src="assets/images/about5.jpg" alt="ASCOT Campus Image 5">
                    <img src="assets/images/about6.jpg" alt="ASCOT Campus Image 6">
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section animate-on-scroll slide-in-up">
                    <h3>VISION</h3>
                    <p>ASCOT 2030: ASCOT as a globally recognized comprehensive inclusive higher education institution anchoring on the local culture of Aurora in particular and the Philippines in general.</p>
                </div>
                <div class="footer-section animate-on-scroll slide-in-up">
                    <h3>MISSION</h3>
                    <p>ASCOT shall capacitate human resources of Aurora and beyond to be globally empowered and future-proofed; generate, disseminate and apply knowledge and technologies for sustainable development.</p>
                </div>
            </div>
            <div class="copyright">
                © 2025 Aurora State College of Technology | SchedMaster
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>
