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

<script>
    // --- Custom Modal Logic ---
    const confirmationModal = document.getElementById('confirmationModal');
    const confirmationModalTitle = document.getElementById('confirmationModalTitle');
    const confirmationModalText = document.getElementById('confirmationModalText');
    const confirmationModalIcon = document.getElementById('confirmationModalIcon');
    const confirmBtn = document.getElementById('confirmationModalConfirm');
    const cancelBtn = document.getElementById('confirmationModalCancel');

    function showConfirmation(title, text, iconClass, confirmClass, onConfirm) {
        confirmationModalTitle.textContent = title;
        confirmationModalText.textContent = text;
        confirmationModalIcon.className = `modal-icon ${iconClass}`; // e.g., 'fa-solid fa-triangle-exclamation danger'
        confirmBtn.className = `button ${confirmClass}`; // e.g., 'confirm-btn-danger'

        confirmationModal.style.display = 'block';

        // Clone and replace the button to remove old event listeners
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.onclick = function() {
            onConfirm();
            confirmationModal.style.display = 'none';
        };

        cancelBtn.onclick = () => confirmationModal.style.display = 'none';
    }

    function updateAdminStatus(element, userId, newStatus) {
        const isDeactivating = newStatus === 'deactivate';
        const title = isDeactivating ? 'Deactivate Admin?' : 'Activate Admin?';
        const text = `Do you really want to ${newStatus} this admin account?`;
        const icon = isDeactivating ? 'fa-solid fa-triangle-exclamation danger' : 'fa-solid fa-circle-check success';
        const btnClass = isDeactivating ? 'confirm-btn-danger' : '';

        showConfirmation(title, text, icon, btnClass, () => {
            // This code runs only after the user clicks "Confirm"
            fetch('../actions/update_admin_status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, status: isDeactivating ? 'deactivated' : 'active' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = element.closest('tr');
                    const statusCell = row.querySelector('td:nth-child(3)');
                    const actionCell = element.parentElement;
                    const editLink = actionCell.querySelector('a.edit-btn');
                    
                    if (isDeactivating) {
                        statusCell.innerHTML = `<span class="status-inactive">Deactivated</span>`;
                        actionCell.innerHTML = editLink.outerHTML + ` <a href="#" onclick="updateAdminStatus(this, ${userId}, 'activate'); return false;" style="color: #27ae60;"><i class="fa-solid fa-toggle-off"></i> Activate</a>`;
                    } else { // Activating
                        statusCell.innerHTML = `<span class="status-active">Active</span>`;
                        actionCell.innerHTML = editLink.outerHTML + ` <a href="#" onclick="updateAdminStatus(this, ${userId}, 'deactivate'); return false;"><i class="fa-solid fa-toggle-on"></i> Deactivate</a>`;
                    }
                }
            })
            .catch(error => console.error('Fetch Error:', error));
        });
    }

    function showContent(sectionId, element) {
        // Hide all content sections
        const sections = document.querySelectorAll('.content-section');
        sections.forEach(section => section.classList.remove('active'));

        // Remove 'active' class from all nav links' parent li in the sidebar
        const navLinks = document.querySelectorAll('.sidebar .menu-items a');
        navLinks.forEach(link => link.closest('li').classList.remove('active'));

        // Show the selected content section
        const activeSection = document.getElementById(sectionId);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        // Add 'active' class to the clicked nav link's parent li
        if (element) {
            element.closest('li').classList.add('active');
            // Save the active tab to session storage to remember it
            sessionStorage.setItem('superadminActiveTab', sectionId);
        }
    }

    // This function ensures the correct content is shown on initial load and when using the back/forward browser buttons.
    function initializeDashboardView() {
        // Check if a tab was saved in the session, otherwise default to 'dashboard-home'
        const activeTabId = sessionStorage.getItem('superadminActiveTab') || 'dashboard-home';
        const activeLink = document.querySelector(`a[onclick*="'${activeTabId}'"]`);

        if (activeLink) {
            showContent(activeTabId, activeLink);
        } else {
            // Fallback to the very first link if the saved one isn't found
            const dashboardLink = document.querySelector('.sidebar ul.menu-items li:first-child a');
            showContent('dashboard-home', dashboardLink);
        }
    }

    // Run on initial page load
    document.addEventListener('DOMContentLoaded', initializeDashboardView);

    // Run when the page is shown from the browser's back/forward cache (bfcache)
    window.addEventListener('pageshow', function(event) {
        if (event.persisted) { // The 'persisted' property is true if the page is from bfcache
            initializeDashboardView();
        }
    });

    // --- Modal Logic for Add Admin ---
    document.addEventListener('DOMContentLoaded', function() {
        const addModal = document.getElementById("addAdminModal");
        const addBtn = document.getElementById("addAdminBtn");
        const addCloseBtn = addModal.querySelector(".close-btn");

        if(addBtn) {
            addBtn.onclick = function(e) {
                e.preventDefault(); // Prevent page jump
                addModal.style.display = "block";
            }
        }

        if(addCloseBtn) {
            addCloseBtn.onclick = () => closeModal('addAdminModal');
        }
    });

    function closeAddAdminModal() {
        closeModal('addAdminModal');
    }

    // --- Logic for Edit Admin Modal ---
    function openEditModal(adminData) {
        // Populate the form fields directly from the provided data
        document.getElementById('edit_user_id').value = adminData.user_id;
        document.getElementById('edit_username').value = adminData.username;
        document.getElementById('edit_email').value = adminData.email;
        document.getElementById('edit_password').value = ''; // Clear password field

        // Show the modal
        document.getElementById('editAdminModal').style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Close modal if clicking outside of it
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        for (const modal of modals) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
</script>
</body>
</html>