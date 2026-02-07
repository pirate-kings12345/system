<?php
session_start();

// A list of valid roles to prevent invalid URL parameters
$valid_roles = ['student', 'instructor', 'admin', 'superadmin'];
$role = $_GET['role'] ?? '';

// If the role is not provided or not in our valid list, redirect to the homepage.
if (empty($role) || !in_array($role, $valid_roles)) {
    header('Location: index.php');
    exit();
}

// Create a user-friendly title from the role
$page_title = ucfirst($role) . ' Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SchedMaster</title>
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Styles to make the login page standalone */
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh;
            background-color: #f0f7ff;
        }
        .login-box { 
            display: block; 
            margin: 20px; 
            background-color: rgba(255, 255, 255, 0.95); /* Slightly transparent white */
            backdrop-filter: blur(5px); /* Frosted glass effect */
        }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #1a6bb3; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box" id="loginForm">
        <h2><?= htmlspecialchars($page_title) ?></h2>
        
        <?php if (isset($_SESSION['error']) && $_SESSION['error']): ?>
            <div class="error-message"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        
        <form action="auth/login_action.php" method="POST">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Username" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <!-- The role is now a hidden field, passed from the URL -->
            <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
            
            <button type="submit">Login</button>
            
            <div class="links">
                <a href="views/forgot_password.php">Forgot Password?</a>
            </div>
        </form>
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
        </div>
    </div>
</body>
</html>