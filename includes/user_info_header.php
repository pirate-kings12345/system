<?php
// This file is included in dashboard headers to display user info.
// It assumes $conn is NOT available, and fetches its own data.

if (isset($_SESSION['user_id'])) {
    $user_id_for_header = $_SESSION['user_id'];
    $role_for_header = $_SESSION['role'];
    $name_for_header = $_SESSION['username']; // Default

    // If the full name was fetched and stored in the session (e.g., on the main dashboard page),
    // use it. This is more efficient than running a query on every page load.
    if (isset($_SESSION['full_name'])) {
        $name_for_header = $_SESSION['full_name'];
    }
}
?>
<div class="user-info">
    <div class="user-avatar"><?= strtoupper(substr($name_for_header, 0, 1)) ?></div>
    <div style="text-align: right;">
        <div><?= htmlspecialchars($name_for_header) ?></div>
        <div style="font-size: 0.8rem; color: var(--gray);"><?= ucfirst($role_for_header) ?></div>
    </div>
</div>