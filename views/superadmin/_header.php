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
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="icon" type="image/png" href="../../assets/images/<?= htmlspecialchars($settings['site_logo'] ?? 'logo.png') ?>">
    <style>
        /* Additional styles for the content sections */
        .content-section { display: none; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .content-section.active { display: block; }
        .content-section h2 { margin-top: 0; border-bottom: 2px solid #ecf0f1; padding-bottom: 10px; margin-bottom: 20px; color: #2c3e50; }
        .button { background-color: #3498db; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 16px; }
        .button:hover { background-color: #2980b9; }
        .button.add-btn { background-color: #2ecc71; float: right; }
        .button.add-btn:hover { background-color: #27ae60; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #ecf0f1; color: #333; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tbody tr:hover { background-color: #f1f5f9; } /* Table row hover effect */
        .status-active { color: green; font-weight: bold; }
        .status-inactive { color: red; font-weight: bold; }
        .action-links a { margin-right: 10px; color: #3498db; text-decoration: none; }
        .action-links a:hover { text-decoration: underline; }
        .backup-section p { font-size: 1.1em; line-height: 1.6; }

        /* Styles for dashboard-grid and stats-card from _superadmin_content.php */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card.stats-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stats-card h3 {
            margin-top: 0;
            color: #555;
            font-size: 1.1em;
        }
        .stats-card .stats-value {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-card .stats-change {
            font-size: 0.9em;
            display: flex;
            align-items: center;
        }
        .stats-card .stats-change.positive { color: green; }
        .stats-card .stats-change.negative { color: red; }
        .stats-card .stats-change i { margin-right: 5px; }

        /* Styles from _sidebar.php that might be overridden or need to be consistent */
        .sidebar .menu-items a {
            color: white; text-decoration: none; display: flex; align-items: center; gap: 10px; width: 100%; height: 100%;
        }
        .sidebar .menu-items li.active a {
            background: #34495e; /* Active background for sidebar links */
        }

        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 25px;
            border: 1px solid #888;
            width: 90%;
            max-width: 500px;
            border-radius: 8px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        /* Improved form styling inside modals */
        .modal-content form label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .modal-content form input[type="text"],
        .modal-content form input[type="email"],
        .modal-content form input[type="password"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }
        .modal-content form input:focus {
            border-color: #3498db;
            outline: none;
        }

        .close-btn {
            color: #aaa;
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
        }

        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Custom Confirmation/Notification Modal */
        .confirm-modal-content { text-align: center; }
        .confirm-modal-content .modal-icon { font-size: 48px; margin-bottom: 20px; }
        .confirm-modal-content .modal-icon.success { color: #2ecc71; }
        .confirm-modal-content .modal-icon.danger { color: #e74c3c; }
        .confirm-modal-content h3 { margin-bottom: 10px; }
        .confirm-modal-content p { margin-bottom: 25px; color: #666; font-size: 1.1em; }
        .modal-buttons { display: flex; justify-content: center; gap: 15px; }
        .modal-buttons .button { min-width: 120px; }
        .modal-buttons .cancel-btn { background-color: #95a5a6; }
        .modal-buttons .cancel-btn:hover { background-color: #7f8c8d; }
        .modal-buttons .confirm-btn-danger { background-color: #e74c3c; }
        .modal-buttons .confirm-btn-danger:hover { background-color: #c0392b; }
        }

        /* Styles for Permissions Tab */
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .permission-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
        }
        .permission-card h3 {
            margin-top: 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .permission-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        /* Styles for Modules Tab Toggle Switches */
        .module-grid {
            display: grid;
            grid-template-columns: 1fr; /* Single column layout */
            gap: 15px;
        }
        .module-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
        }
        .module-card .info h4 { margin: 0 0 5px 0; color: #333; }
        .module-card .info p { margin: 0; font-size: 0.9em; color: #777; }
        .switch { position: relative; display: inline-block; width: 60px; height: 34px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 34px; }
        .slider:before { position: absolute; content: ""; height: 26px; width: 26px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        input:checked + .slider { background-color: #2ecc71; }
        input:checked + .slider:before { transform: translateX(26px); }

        /* Notification Styles */
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            color: white;
            font-weight: bold;
        }
        .notification.success { background-color: #2ecc71; }
        .notification.error { background-color: #e74c3c; }

    </style>
</head>
<body class="dashboard">
    <div class="container">
        <!-- Sidebar -->
        <?php include '_sidebar.php'; ?>
        
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