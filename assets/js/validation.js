// === LOGIN VALIDATION ===
document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        loginForm.addEventListener("submit", (e) => {
            const username = loginForm.username.value.trim();
            const password = loginForm.password.value.trim();
            const role = loginForm.role.value.trim();
            if (!username || !password || !role) {
                e.preventDefault();
                alert("Please fill out all fields and select a role.");
            }
        });
    }
});
