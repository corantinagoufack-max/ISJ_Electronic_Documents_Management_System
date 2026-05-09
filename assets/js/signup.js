document.addEventListener('DOMContentLoaded', () => {

    // 1. Password Toggle Logic (Traverses to closest form group)
    const toggleIcons = document.querySelectorAll('.toggle-password');

    toggleIcons.forEach(icon => {
        icon.addEventListener('click', function () {
            const formGroup = this.closest('.form-group');
            const input = formGroup.querySelector('input');

            if (input.type === 'password') {
                input.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // 2. Professional Error Handling (URL-based)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const errorType = urlParams.get('error');
        const messages = {
            'name_exists': "⚠️ This Full Name is already registered in ISJ-DMS.",
            'mismatch': "❌ Validation Error: Passwords do not match.",
            'system': "⚙️ System Error: Could not connect to database."
        };

        if (messages[errorType]) {
            setTimeout(() => {
                alert(messages[errorType]);
                window.history.replaceState({}, document.title, window.location.pathname);
            }, 100);
        }
    }

    // 3. Button Feedback
    const registerBtn = document.querySelector('.btn-register');
    if (registerBtn) {
        registerBtn.addEventListener('mousedown', () => registerBtn.style.transform = 'scale(0.98)');
        registerBtn.addEventListener('mouseup', () => registerBtn.style.transform = 'scale(1)');
    }
});