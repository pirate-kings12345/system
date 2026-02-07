        </div> <!-- End main-content -->
    </div> <!-- End container -->

    <!-- Add Admin Modal -->
    <div id="addAdminModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h2><i class="fa-solid fa-user-plus"></i> Add New Admin</h2>
            <form id="addAdminForm" action="../actions/add_admin.php" method="POST" style="margin-top: 20px;">
                <label for="add_username">Username:</label>
                <input type="text" id="add_username" name="username" required>

                <label for="add_email">Email:</label>
                <input type="email" id="add_email" name="email" required>

                <label for="add_password">Password:</label>
                <input type="password" id="add_password" name="password" required>

                <button type="submit" class="button" style="background-color: #2ecc71;"><i class="fa-solid fa-check"></i> Create Admin Account</button>
            </form>
        </div>
    </div>

    <!-- Add Instructor Modal -->
    <div id="addInstructorModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addInstructorModal')">&times;</span>
            <h2><i class="fa-solid fa-chalkboard-teacher"></i> Add New Instructor</h2>
            <form id="addInstructorForm" action="../actions/add_instructor.php" method="POST" style="margin-top: 20px;">
                <label for="add_instructor_username">Username:</label>
                <input type="text" id="add_instructor_username" name="username" required>

                <label for="add_instructor_email">Email:</label>
                <input type="email" id="add_instructor_email" name="email" required>

                <label for="add_instructor_password">Password:</label>
                <input type="password" id="add_instructor_password" name="password" required>

                <button type="submit" class="button" style="background-color: #2ecc71;"><i class="fa-solid fa-check"></i> Create Instructor Account</button>
            </form>
        </div>
    </div>

    <!-- Edit Admin Modal -->
    <div id="editAdminModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('editAdminModal')">&times;</span>
            <h2><i class="fa-solid fa-pencil"></i> Edit Admin</h2>
            <form id="editAdminForm" action="../actions/edit_admin.php" method="POST" style="margin-top: 20px;">
                <input type="hidden" id="edit_user_id" name="user_id">

                <label for="edit_username">Username:</label>
                <input type="text" id="edit_username" name="username" required>

                <label for="edit_email">Email:</label>
                <input type="email" id="edit_email" name="email" required>

                <label for="edit_password">New Password (Optional):</label>
                <input type="password" id="edit_password" name="password" placeholder="Leave blank to keep current password">

                <button type="submit" class="button"><i class="fa-solid fa-save"></i> Update Admin Account</button>
            </form>
        </div>
    </div>

    <!-- Generic Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <div id="confirmationModalIcon" class="modal-icon"></div>
            <h3 id="confirmationModalTitle">Are you sure?</h3>
            <p id="confirmationModalText">This action cannot be undone.</p>
            <div class="modal-buttons">
                <button id="confirmationModalCancel" class="button cancel-btn">Cancel</button>
                <button id="confirmationModalConfirm" class="button">Confirm</button>
            </div>
        </div>
    </div>

<script src="../../assets/js/superadmin_dashboard.js"></script>
</body>
</html>