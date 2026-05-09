<?php
// edit_user.php
require_once 'config/init.php';
require_once 'includes/UserManager.php';

// Security: Admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$userManager = new UserManager($conn);
$user = $userManager->getUserById($_GET['id'] ?? 0);

if (!$user) {
    die("User not found.");
}

function icon($name, $classes = '')
{
    $path = "assets/icons/{$name}.svg";
    return file_exists($path) ? "<span class=\"icon {$classes}\">" . file_get_contents($path) . "</span>" : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/2-tools/icons.css">
    <link rel="stylesheet" href="assets/css/7-pages/login.css">
</head>

<body class="login-body">
    <div class="login-container">

        <a href="usermanagement.php" class="btn-home-link">
            <?php echo icon('logout', 'icon-sm'); ?> <span>Back to List</span>
        </a>

        <div class="login-card">
            <header class="login-header">
                <h1>Edit User</h1>
                <p>Modifying: <strong><?php echo htmlspecialchars($user['fullname']); ?></strong></p>
            </header>

            <form action="modules/users/edit.php" method="POST" id="editForm">
                <input type="hidden" name="id" value="<?php echo $user['id']; ?>">

                <div class="form-group">
                    <label for="fullname">FULL NAME</label>
                    <div class="input-wrapper">
                        <?php echo icon('users', 'input-icon'); ?>
                        <input type="text" id="fullname" name="fullname"
                            value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">E-MAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <?php echo icon('mail', 'input-icon'); ?>
                        <input type="email" id="email" name="email"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>ACCESS LEVEL</label>
                    <div class="input-wrapper">
                        <?php echo icon('settings', 'input-icon'); ?>
                        <select name="role">
                            <option value="Admin" <?php if ($user['role'] == 'Admin') echo 'selected'; ?>>Admin</option>
                            <option value="Standard User" <?php if ($user['role'] == 'Standard User') echo 'selected'; ?>>Standard User</option>
                            <option value="Viewer" <?php if ($user['role'] == 'Viewer') echo 'selected'; ?>>Viewer</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Save Changes <?php echo icon('save', 'icon-sm'); ?>
                </button>
            </form>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>