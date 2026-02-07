<?php
// This page is intended to be included in index.php, not accessed directly.
// If accessed directly, redirect to index.php
if (basename($_SERVER['PHP_SELF']) == 'register.php') {
    header('Location: ../index.php');
    exit();
}

$register_error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
$register_success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
?>

<div class="register-box" id="registerForm" style="display: none;">
    <h2>Register</h2>
    <?php if ($register_error): ?>
        <div class="error-message"><?= htmlspecialchars($register_error) ?></div>
    <?php endif; ?>
    <?php if ($register_success): ?>
        <div class="success-message"><?= htmlspecialchars($register_success) ?></div>
    <?php endif; ?>
    
    <form action="auth/register.php" method="POST">
        <div class="input-group">
            <i class="fas fa-user"></i>
            <input type="text" name="username" placeholder="Username" required value="<?= $_SESSION['old_username'] ?? '' ?>">
        </div>
        <div class="input-group">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" placeholder="Email" required value="<?= $_SESSION['old_email'] ?? '' ?>">
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
            <a href="#" onclick="toggleLoginForm(); return false;">Already have an account? Login</a>
        </div>
    </form>
</div>

<?php
// Clear old input values from session after displaying
unset($_SESSION['old_username']);
unset($_SESSION['old_email']);
?>
