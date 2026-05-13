<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify | ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/7-pages/2fa.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="verify-page">

    <div class="auth-container">
        <div class="auth-inner">
            <header class="brand-header">
                <div class="brand-icon-box">
                    <i class="fas fa-shield-check"></i>
                </div>
                <h1>ISJ-DMS</h1>
                <p class="brand-sub">Authentication Before Login</p>
            </header>

            <div class="verify-card">
                <h2>Two-Factor Verification</h2>
                <p class="instruction">Enter the 6-digit code sent to your institutional email to proceed.</p>

                <form action="includes/verify-logic.php" method="POST" id="otpForm">
                    <div class="otp-inputs">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autofocus autocomplete="off">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autocomplete="off">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autocomplete="off">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autocomplete="off">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autocomplete="off">
                        <input type="text" name="otp[]" maxlength="1" placeholder="·" autocomplete="off">
                    </div>

                    <button type="submit" class="btn-verify-submit">
                        Verify & Complete <i class="fas fa-arrow-right"></i>
                    </button>
                </form>

                <div class="otp-meta">
                    <span class="expiry">
                        <i class="far fa-clock"></i> Expires in <span id="timer">03:00</span>
                    </span>
                    <a href="includes/resend-logic.php" id="resendBtn" class="resend disabled-link">RESEND CODE</a>
                </div>

                <div class="account-pill">
                    <img src="https://ui-avatars.com/api/?name=User&background=0f172a&color=fff" alt="User">
                    <span><?php echo $_SESSION['verify_email'] ?? 'user@saintjeaningenieur.org'; ?></span>
                </div>

                <a href="login.php" class="back-link">← Back to Login</a>
            </div>

            <div class="bottom-security">
                <span class="secure-dot"></span> ENCRYPTED SESSION ACTIVE
            </div>
        </div>
    </div>

    <script src="assets/js/2fa.js"></script>
</body>

</html>