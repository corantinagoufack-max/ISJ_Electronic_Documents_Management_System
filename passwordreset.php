<?php
session_start();
require_once 'config/database.php'; // Using your mysqli $conn
?>
<?php if (isset($_GET['status'])): ?>
    <?php if ($_GET['status'] == 'sent'): ?>
        <div class="alert alert-success" style="color: #16a34a; background: #dcfce7; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem;">
            Success! Please check your inbox for the reset link.
        </div>
    <?php elseif ($_GET['status'] == 'notfound'): ?>
        <div class="alert alert-error" style="color: #b91c1c; background: #fee2e2; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem;">
            We couldn't find an account with that email.
        </div>
    <?php endif; ?>
<?php endif; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/passwordreset.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo-circle">
                    <i class="fas fa-lock-open"></i>
                </div>
                <h1>Reset Password</h1>
                <p>Enter your email address and we'll send you a code  to reset your password.</p>
            </div>

            <form action="includes/process-reset.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="name@isj-dms.edu" required>
                    </div>
                </div>

                <button type="submit" name="submit_reset" class="btn-auth-primary">
                    Send Reset code
                </button>
            </form>

            <div class="auth-footer">
                <a href="login.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>

</html>