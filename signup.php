<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/signup.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="signup-page">
    <div class="signup-container">
        <div class="signup-card">
            <header class="signup-header">
                <img src="assets/images/logo.png" alt="ISJ Logo" class="logo">
                <h1>ISJ-DMS</h1>
                <p>Create your Academic Profile</p>
            </header>

            <form action="includes/process-signup.php" method="POST" id="signupForm" class="signup-form">
                <div class="form-group">
                    <label>FULL NAME</label>
                    <div class="input-wrapper">
                        <i class="far fa-user"></i>
                        <input type="text" name="fullname" placeholder="Mr. Agoufack Alapani" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>EMAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <i class="far fa-envelope"></i>
                        <input type="email" name="email" placeholder="email@saintjeaningenieur.org" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>ACCESS ROLE</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-shield"></i>
                        <select name="role" required>
                            <option value="" disabled selected>Select institutional role</option>
                            <option value="Admin">Admin (School Administrator)</option>
                            <option value="Standard User">Standard User (Teacher)</option>
                            <option value="Viewer">Viewer (Student)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>PASSWORD</label>
                    <div class="input-wrapper">
                        <i class="fas fa-eye toggle-password"></i>
                        <input type="password" name="password" id="password" placeholder="••••••••••••" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>CONFIRM PASSWORD</label>
                    <div class="input-wrapper">
                        <i class="fas fa-eye toggle-password"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn-register">
                    Register <i class="fas fa-arrow-right"></i>
                </button>

                <p class="login-redirect">Already have an account? <a href="login.php">Login</a></p>
            </form>
        </div>

        <footer class="signup-footer">
            <p>&copy; 2026 ISJ Academic Curator System. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/signup.js"></script>
</body>

</html>