<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: passwordreset.php");
    exit();
}

$error = "";

if (isset($_POST['verify_code'])) {
    $code = $_POST['code'];
    $email = $_SESSION['reset_email'];

    // Check if code matches and is not expired
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expiry > NOW()");
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $_SESSION['allow_password_change'] = true;
        // Redirect to the separate page for password setting
        header("Location: resetpassword_final.php");
        exit();
    } else {
        $error = "Invalid or expired security code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code | ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/verify-password.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <header class="auth-header">
                <div class="icon-circle">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <h1>Enter Security Code</h1>
                <p>We sent a 6-digit verification code to:<br>
                    <span class="user-email"><?php echo htmlspecialchars($_SESSION['reset_email']); ?></span>
                </p>
            </header>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fas fa-circle-exclamation"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="verify-form">
                <div class="form-group">
                    <label>6-DIGIT CODE</label>
                    <input type="text" name="code" placeholder="000000" required maxlength="6" autocomplete="one-time-code" class="code-input">
                </div>

                <button type="submit" name="verify_code" class="btn-primary">
                    Verify Identity <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-footer">
                <p>Didn't get the code? <a href="passwordreset.php">Resend Email</a></p>
            </div>
        </div>
    </div>
</body>

</html>