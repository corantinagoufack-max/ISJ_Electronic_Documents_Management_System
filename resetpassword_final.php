<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['allow_password_change'])) {
    header("Location: passwordreset.php");
    exit();
}

$php_error = ""; // For server-side fallbacks

if (isset($_POST['change_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $php_error = "Passwords do not match.";
    } else {
        $new_pass = password_hash($password, PASSWORD_BCRYPT);
        $email = $_SESSION['reset_email'];

        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE email = ?");
        $stmt->bind_param("ss", $new_pass, $email);

        if ($stmt->execute()) {
            session_destroy();
            echo "<script>alert('Password updated successfully!'); window.location.href='login.php';</script>";
            exit();
        } else {
            $php_error = "Database error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Set New Password | ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/passwordreset.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <h1>Create New Password</h1>

            <form id="resetForm" method="POST" onsubmit="return validatePasswords()" class="auth-form">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Min. 8 characters" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                </div>

                <button type="submit" name="change_password" class="btn-auth-primary">
                    Update Password
                </button>
            </form>
        </div>
    </div>

    <script>
        // Check for server-side errors passed to PHP
        <?php if (!empty($php_error)): ?>
            alert("<?php echo $php_error; ?>");
        <?php endif; ?>

        function validatePasswords() {
            const pass = document.getElementById('password').value;
            const confirmPass = document.getElementById('confirm_password').value;

            if (pass.length < 8) {
                alert("Error: Password must be at least 8 characters long.");
                return false; // Stop form submission
            }

            if (pass !== confirmPass) {
                alert("Error: Passwords do not match. Please re-enter.");
                return false; // Stop form submission
            }

            return true; // Allow form submission
        }
    </script>
</body>

</html>