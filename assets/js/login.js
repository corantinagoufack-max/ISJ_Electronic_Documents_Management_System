/**
 * ISJ-DMS Login Logic
 */
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('toggleBtn');
    const passwordInput = document.getElementById('password');
    const loginForm = document.getElementById('loginForm');

    // 1. Password Visibility Toggle
    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
        });
    }

    // 2. Role-Based Validation
    if (loginForm) {
        loginForm.onsubmit = function () {
            const role = document.getElementById('role').value;
            const pass = passwordInput.value;

            if (!role) {
                alert("Please select your access level.");
                return false;
            }

            // Security Policy Check
            if (role === "Admin" && pass.length < 12) {
                alert("Security Alert: Admin passwords must be 12+ characters.");
                return false;
            } else if (role === "Standard User" && pass.length < 8) {
                alert("Security Alert: Teacher passwords must be 8+ characters.");
                return false;
            } else if (role === "Viewer" && pass.length < 6) {
                alert("Security Alert: Student passwords must be 6+ characters.");
                return false;
            }
            return true;
        };
    }

    // 3. URL Error Notification Handler
    const params = new URLSearchParams(window.location.search);
    if (params.has('error')) {
        const err = params.get('error');
        if (err === 'role_mismatch') {
            alert("Role Error: Your account level does not match the selected access level.");
        } else if (err === 'invalid_credentials') {
            alert("Auth Error: The email or password provided is incorrect.");
        }
        // Clean URL without refresh
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});