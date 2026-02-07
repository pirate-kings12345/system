<?php
require_once '../../includes/session_check.php';
require_once '../../config/db_connect.php';
require_once '../../includes/permissions_map.php'; // Include the permissions definitions

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../../auth/login.php");
    exit;
}

$page_title = 'Role Permissions';

// Fetch current permissions from the database
$current_permissions = [];
$perm_result = $conn->query("SELECT role, permission_key FROM role_permissions");
while ($row = $perm_result->fetch_assoc()) {
    $current_permissions[$row['role']][$row['permission_key']] = true;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - SchedMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        .permission-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: var(--box-shadow);
            overflow: hidden;
        }
        .permission-card-header {
            background-color: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid var(--light-gray);
            font-size: 1.1rem;
            font-weight: 600;
        }
        .permission-group {
            padding: 20px;
        }
        .permission-group h4 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 1rem;
            color: var(--primary);
        }
        .permission-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .permission-item:last-child {
            border-bottom: none;
        }
        .permission-item .description {
            font-size: 0.9rem;
            color: #333;
        }
    </style>
</head>
<body class="dashboard">
    <div class="container">
        <?php include '_sidebar.php'; ?>
        <div class="main-content">
            <div class="header">
                <h2><i class="fas fa-tasks"></i> <?= htmlspecialchars($page_title) ?> Management</h2>
            </div>

            <div class="card">
                <h3>Define Role Capabilities</h3>
                <p>Define what each user role can see and do within the system. Changes will apply to all users within that role.</p>
            </div>

            <form action="../actions/save_role_permissions.php" method="POST" style="margin-top: 2rem;">
                <div class="permissions-grid">
                    <?php foreach ($permissions_map as $role => $categories): ?>
                        <div class="permission-card">
                            <div class="permission-card-header"><?= ucfirst($role) ?> Permissions</div>
                            <div class="permission-group">
                                <?php foreach ($categories as $category_name => $permissions): ?>
                                    <h4><?= htmlspecialchars($category_name) ?></h4>
                                    <?php foreach ($permissions as $key => $description): ?>
                                        <div class="permission-item">
                                            <span class="description"><?= htmlspecialchars($description) ?></span>
                                            <label class="toggle-switch">
                                                <input type="checkbox" name="permissions[<?= $role ?>][]" value="<?= $key ?>"
                                                    <?= (isset($current_permissions[$role][$key])) ? 'checked' : '' ?>>
                                                <span class="slider"></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div style="text-align: right; margin-top: 2rem;">
                    <button type="submit" class="button"><i class="fas fa-save"></i> Save All Permissions</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>