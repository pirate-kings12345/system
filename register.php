<?php
session_start();

// Fetch module status for 'module_registration' from the database
require_once 'config/db_connect.php';
$is_registration_module_active = false;
$settingsResult = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'module_registration'");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $setting = $settingsResult->fetch_assoc();
    if ($setting['setting_value'] == '1') {
        $is_registration_module_active = true;
    }
}

// If registration is not active, redirect to the homepage.
if (!$is_registration_module_active) {
    header('Location: index.php');
    exit();
}

$register_error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
$register_success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Account - SchedMaster</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles to make the register page standalone */
        body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background-color: #f0f7ff; }
        .register-box { display: block; margin: 20px; } /* Ensure register box is visible */
    </style>
</head>
<body>
    <div class="register-box" id="registerForm">
        <h2>Register New Account</h2>
        <?php if ($register_error): ?>
            <div class="error-message"><?= htmlspecialchars($register_error) ?></div>
        <?php endif; ?>
        <?php if ($register_success): ?>
            <div class="success-message"><?= htmlspecialchars($register_success) ?></div>
        <?php endif; ?>
        
        <form action="auth/register.php" method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required value="<?= htmlspecialchars($_SESSION['old_username'] ?? '') ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($_SESSION['old_email'] ?? '') ?>">
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <input type="hidden" name="role" value="student">
            <button type="submit">Register</button>
            <div class="links">
                <a href="index.php">Already have an account? Login</a>
            </div>
        </form>
    </div>
</body>
</html>