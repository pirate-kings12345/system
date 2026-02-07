<!-- First Row -->
<div class="dashboard-grid">
    <div class="card stats-card">
        <div>
            <h3>Pending Enrollments</h3>
            <div class="stats-value"><?= count($pending_enrollments) ?></div>
            <div class="stats-change <?= count($pending_enrollments) > 0 ? 'positive' : '' ?>">
                <i class="fas fa-hourglass-half"></i>
                <?= count($pending_enrollments) ?> new request(s)
            </div>
        </div>
    </div>
    
    <div class="card stats-card">
        <div>
            <h3>Active Schedules</h3>
            <div class="stats-value">48</div>
            <div class="stats-change positive">
                <i class="fas fa-arrow-up"></i>
                2 added this week
            </div>
        </div>
    </div>
    
    <div class="card stats-card">
        <div>
            <h3>Rooms / Sections</h3>
            <div class="stats-value">25 / 30</div>
            <div style="font-size: 0.9rem; color: var(--gray);">Across all departments</div>
        </div>
    </div>
</div>

<!-- Data Grid -->
<div class="data-grid">
    <div class="card list-card">
        <h3>Recent System Activity</h3>
        <ul class="activity-list">
            <li>
                <div class="activity-icon"><i class="fas fa-user-plus"></i></div>
                <div>New student 'Clark Pena' registered for BSIT.</div>
            </li>
            <li>
                <div class="activity-icon"><i class="fas fa-book"></i></div>
                <div>Subject 'IT101' was updated.</div>
            </li>
            <li>
                <div class="activity-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div>Instructor 'Alexis Bondoc' updated their profile.</div>
            </li>
            <li>
                <div class="activity-icon"><i class="fas fa-file-alt"></i></div>
                <div>Generated enrollment report for 1st Semester.</div>
            </li>
        </ul>
    </div>
    
    <div class="card list-card">
        <h3>Quick Links</h3>
        <button style="width: 100%; margin-bottom: 10px;" class="button" onclick="document.querySelector('a[onclick*=\'pending-enrollments\']').click();"><i class="fas fa-check-circle"></i> Approve Enrollments</button>
        <button style="width: 100%; margin-bottom: 10px;" class="button" onclick="openAddScheduleModal();"><i class="fas fa-plus"></i> Create Schedule</button>
        <button style="width: 100%;" class="button" onclick="document.querySelector('a[onclick*=\'manage-instructors\']').click();"><i class="fas fa-users"></i> Manage Users</button>
    </div>
</div>