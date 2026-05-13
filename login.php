<?php
require_once 'config/init.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

function icon(string $name, string $classes = ''): string
{
    $path = __DIR__ . "/assets/icons/{$name}.svg";
    if (!file_exists($path)) return '';
    $svg = file_get_contents($path);
    $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
    return "<span class=\"icon {$classes}\">{$svg}</span>";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/2-tools/icons.css">
    <link rel="stylesheet" href="assets/css/7-pages/login.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="login-body">
    <div class="login-container">
        <a href="index.php" class="btn-home-link">
            <?php echo icon('home', 'icon-sm'); ?> <span>Home</span>
        </a>

        <div class="login-card">
            <header class="login-header">
                <img src="assets/images/logo.png" alt="ISJ Logo" class="logo">
                <h1>ISJ-DMS</h1>
                <p>Academic Curator</p>
            </header>

            <?php if (isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="alert-success">
                    <?php echo icon('enable', 'icon-sm'); ?> <span>Password updated!</span>
                </div>
            <?php endif; ?>

            <form action="includes/auth.php" method="POST" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email">EMAIL</label>
                    <div class="input-wrapper">
                        <?php echo icon('mail', 'input-icon'); ?>
                        <input type="email" id="email" name="email" placeholder="username@gmail.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label for="password">PASSWORD</label>
                        <a href="passwordreset.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    <div class="input-wrapper">
                        <?php echo icon('lock', 'icon-xs'); ?>
                        <input type="password" id="password" name="password" placeholder="••••••••" required>
                        <span id="toggleBtn" class="toggle-password-btn">
                            <?php echo icon('eye', 'icon-xs'); ?>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">ACCESS LEVEL</label>
                    <div class="input-wrapper">
                        <?php echo icon('users', 'input-icon'); ?>
                        <select id="role" name="role" required>
                            <option value="" disabled selected>Select your role</option>
                            <option value="Admin">Admin (School Administrator)</option>
                            <option value="Standard User">Standard User (Teacher)</option>
                            <option value="Viewer">Viewer (Student)</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Login <?php echo icon('logout', 'icon-sm'); ?>
                </button>
            </form>
            <p class="signup-prompt">Don't have an account? <a href="signup.php">Sign Up</a></p>
        </div>

        <footer class="login-footer">
            <p>&copy; 2026 ISJ Academic Curator System. All rights reserved.</p>
        </footer>
    </div>

    <script src="assets/js/login.js"></script>
</body>

</html>