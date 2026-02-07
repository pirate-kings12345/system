<?php
// This file is included in dashboard.php, so it has access to $conn

// Fetch all admin users except the superadmin
$admins = [];
$adminSql = "SELECT user_id, username, email, status FROM users WHERE role = 'admin' AND username != 'superadmin' ORDER BY username";
$adminResult = $conn->query($adminSql);
if ($adminResult && $adminResult->num_rows > 0) {
    while($row = $adminResult->fetch_assoc()) {
        $admins[] = $row;
    }
}
?>
<div id="manage-admins" class="content-section">
    <div class="section-header">
        <h2 class="section-title-hidden"><i class="fas fa-user-shield"></i> Manage Admins</h2>
        <a href="#" class="button" id="addAdminBtn"><i class="fa-solid fa-plus"></i> Add New Admin</a>
    </div>
    <p>Create, view, edit, and manage administrator accounts.</p>

    <table class="data-table">
        <thead>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($admins)): ?>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['username']) ?></td>
                        <td><?= htmlspecialchars($admin['email']) ?></td>
                        <td>
                            <?php 
                                $status_class = $admin['status'] === 'active' ? 'status-active' : 'status-inactive';
                                $status_text = $admin['status'] === 'deactivated' ? 'Deactivated' : ucfirst($admin['status']);
                            ?>
                            <span class="<?= $status_class ?>"><?= htmlspecialchars($status_text) ?></span>
                        </td>
                        <td class="action-links">
                            <a href="#" class="edit-btn" onclick='openEditModal(<?= json_encode($admin) ?>); return false;'><i class="fa-solid fa-pencil"></i> Edit</a>
                            <?php if ($admin['status'] === 'active'): ?>
                                <a href="#" onclick="updateAdminStatus(this, <?= $admin['user_id'] ?>, 'deactivate'); return false;"><i class="fa-solid fa-toggle-on"></i> Deactivate</a>
                            <?php else: ?>
                                <a href="#" onclick="updateAdminStatus(this, <?= $admin['user_id'] ?>, 'activate'); return false;" style="color: #27ae60;"><i class="fa-solid fa-toggle-off"></i> Activate</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;">No admin accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>