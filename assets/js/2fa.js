document.addEventListener('DOMContentLoaded', () => {
    const inputs = document.querySelectorAll('.otp-inputs input');
    const timerDisplay = document.getElementById('timer');
    const resendBtn = document.getElementById('resendBtn');

    let countdownInterval;
    let timeLeft = 180; // 3 minutes in seconds

    // --- 1. TIMER CORE LOGIC ---
    const startTimer = () => {
        // Clear any existing timer to prevent overlapping loops
        clearInterval(countdownInterval);

        // Reset state
        timeLeft = 180;
        resendBtn.classList.add('disabled-link');
        resendBtn.style.pointerEvents = 'none';

        countdownInterval = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;

            if (timerDisplay) {
                timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            }

            if (timeLeft <= 0) {
                clearInterval(countdownInterval);
                if (timerDisplay) {
                    timerDisplay.parentElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> Code expired`;
                }

                // Enable Resend Button
                resendBtn.classList.remove('disabled-link');
                resendBtn.style.pointerEvents = 'auto';
            }
            timeLeft--;
        }, 1000);
    };

    // Trigger timer immediately on load (Syncs with the email sent by PHP)
    startTimer();

    // --- 2. OTP INPUT HANDLING ---
    inputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && input.value === '' && index > 0) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('keypress', (e) => {
            if (!/[0-9]/.test(e.key)) e.preventDefault();
        });
    });

    // --- 3. ERROR & URL HANDLING ---
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('error')) {
        const error = urlParams.get('error');
        if (error === 'invalid_otp') {
            alert("❌ Invalid code. Please check your email and try again.");
            inputs.forEach(i => {
                i.style.borderColor = '#ef4444';
                i.value = '';
            });
            inputs[0].focus();
        }
        // Remove error from URL without refreshing
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    // Optional: If you use AJAX for resending, call startTimer() inside the success callback.
    // If you use a standard link (PHP redirect), the page reloads and startTimer() runs automatically.
});