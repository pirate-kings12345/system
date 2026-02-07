<?php
$page_title = $page_title ?? 'Superadmin Dashboard'; // Use provided title or a default
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css"> <!-- Main Styles -->
    <link rel="stylesheet" href="../../assets/css/superadmin.css"> <!-- Page-specific Styles -->
    <link rel="icon" type="image/png" href="../../assets/images/<?= htmlspecialchars($settings['site_logo'] ?? 'logo.png') ?>">
</head>
<body class="dashboard">
    <div class="container">
        <!-- Sidebar -->
        <?php include __DIR__ . '/_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h2><?= htmlspecialchars($page_title) ?></h2>
                <div class="user-info">
                    <div class="user-avatar">SA</div>
                    <div style="text-align: right;">
                        <div>Super Admin</div>
                        <div style="font-size: 0.8rem; color: var(--gray);"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="notification success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="notification error"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>