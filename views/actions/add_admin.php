<?php
require_once '../../config/db_connect.php';
require_once '../../includes/session_check.php';

// ✅ Allow only superadmin
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'superadmin') {
    header('Location: ../../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);

    // Prevent duplicates
    $check = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $check->bind_param('ss', $username, $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        echo "<script>alert('Username or Email already exists!'); history.back();</script>";
        exit;
    }

    // Insert into users table
    $sql = "INSERT INTO users (username, password, email, role, status) 
            VALUES (?, ?, ?, 'admin', 'active')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $username, $password, $email);

    if ($stmt->execute()) {
        echo "<script>alert('✅ New admin created successfully!'); window.location='../../views/superadmin/dashboard.php';</script>";
    } else {
        echo "<script>alert('❌ Error creating admin: " . $stmt->error . "'); history.back();</script>";
    }
}
?>
