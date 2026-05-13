<?php
session_start();

require_once 'config/database.php';
require_once 'includes/UserManager.php';

// Security: Ensure only Admins can view this page
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: dashboard.php");
    exit();
}

$current_filter = isset($_GET['role']) ? $_GET['role'] : 'all';

// HANDLE ACTIONS 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $target_id = (int) $_POST['user_id'];

    if ($target_id === (int) $_SESSION['user_id']) {
        $action_error = "You cannot modify your own account from here.";
    } else {
        if ($_POST['action'] === 'update_role') {
            $new_role = $conn->real_escape_string($_POST['new_role']);
            $conn->query("UPDATE users SET role = '$new_role' WHERE id = $target_id");
            $action_success = "User role updated successfully.";
        } elseif ($_POST['action'] === 'enable') {
            $conn->query("UPDATE users SET is_verified = 1 WHERE id = $target_id");
            $action_success = "User has been enabled.";
        } elseif ($_POST['action'] === 'disable') {
            $conn->query("UPDATE users SET is_verified = 0 WHERE id = $target_id");
            $action_success = "User has been disabled.";
        }
    }
}

// Show success after creating user
if (isset($_GET['status']) && $_GET['status'] === 'created') {
    $action_success = "New user account created successfully.";
}

$userManager = new UserManager($conn);

try {
    $filter_sql = "";
    if ($current_filter === 'Admin') {
        $filter_sql = " WHERE role = 'Admin'";
    } elseif ($current_filter === 'Teacher') {
        $filter_sql = " WHERE role = 'Standard User'";
    } elseif ($current_filter === 'Student') {
        $filter_sql = " WHERE role = 'Viewer'";
    }

    $query = "SELECT * FROM users" . $filter_sql . " ORDER BY fullname ASC";
    $result = $conn->query($query);
    $users = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $users = [];
    $error_msg = $e->getMessage();
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
    <title>User Management | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/2-tools/icons.css">
    <link rel="stylesheet" href="assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="assets/css/7-pages/usermanagement.css">

    <script src="assets/js/usermanagement.js" defer></script>
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg></button>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-header">
                    <img src="assets/images/logo.png" alt="ISJ Logo" class="brand-logo-img">
                    <div class="brand-text">
                        <h3>The Academic DMS</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="modules/documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="modules/documents/shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <a href="tags/index.php" class="nav-item"><?php echo icon('tags', 'nav-icon'); ?> Tags</a>
                <a href="modules/folder-browser/index.php" class="nav-item"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <a href="modules/users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
                <a href="usermanagement.php" class="nav-item active"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
            </nav>
            <div class="sidebar-footer">
                <a href="modules/documents/upload.php" class="btn-upload"><?php echo icon('upload', 'nav-icon'); ?> Upload Document</a>
                <a href="logout.php" class="logout-link"><?php echo icon('logout', 'nav-icon'); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <header class="content-header">
                <div class="header-nav-tabs"><span class="active">User Management</span></div>
                <div class="header-actions">
                    <div class="user-profile-header">
                        <div class="user-info">
                            <strong><?php echo htmlspecialchars(explode(' ', trim($_SESSION['user_name']))[0]); ?></strong>
                            <span>System Administrator</span>
                        </div>
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['user_name']); ?>&background=0f172a&color=fff" alt="Profile">
                    </div>
                </div>
            </header>

            <section class="dashboard-view">
                <div class="page-header-container">
                    <div class="header-left">
                        <div class="breadcrumb">Admin <span>/</span> User Management</div>
                        <h1 class="page-title">User Management</h1>
                        <p class="page-subtitle">Manage system users, roles, and access permissions.</p>
                    </div>
                    <div class="header-right">
                        <a href="modules/users/create.php" class="btn-create">
                            <?php echo icon('users', 'icon-sm'); ?> Create New User
                        </a>
                    </div>
                </div>

                <div class="filter-bar">
                    <div class="filter-tabs">
                        <a href="usermanagement.php?role=all" class="tab-item <?php echo $current_filter === 'all' ? 'active' : ''; ?>">All Users</a>
                        <a href="usermanagement.php?role=Admin" class="tab-item <?php echo $current_filter === 'Admin' ? 'active' : ''; ?>">Admins</a>
                        <a href="usermanagement.php?role=Teacher" class="tab-item <?php echo $current_filter === 'Teacher' ? 'active' : ''; ?>">Teachers</a>
                        <a href="usermanagement.php?role=Student" class="tab-item <?php echo $current_filter === 'Student' ? 'active' : ''; ?>">Students</a>
                    </div>
                    <div class="search-box">
                        <span class="search-icon">
                            <?php echo icon('filter', 'icon-xs search-filter-icon'); ?>
                        </span>
                        <input type="text" id="userSearch" placeholder="Search name or email...">
                    </div>
                </div>

                <?php if (isset($action_success)): ?>
                    <div class="alert alert-success"><?php echo icon('enable', 'icon-sm icon-success'); ?> <?php echo htmlspecialchars($action_success); ?></div>
                <?php endif; ?>

                <?php if (isset($action_error)): ?>
                    <div class="alert alert-error"><?php echo icon('disable', 'icon-sm icon-danger'); ?> <?php echo htmlspecialchars($action_error); ?></div>
                <?php endif; ?>

                <div class="table-container">
                    <table class="modern-table" id="userTable">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th class="col-hide-sm">Email</th>
                                <th class="col-hide-sm">Role</th>
                                <th class="col-hide-sm">Status</th>
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="empty-state">No users found for this role.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php $is_self = ((int)$user['id'] === (int)$_SESSION['user_id']); ?>
                                    <tr class="user-row <?php echo $is_self ? 'self-row' : ''; ?>">
                                        <td class="user-name">
                                            <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>
                                            <?php if ($is_self): ?><span class="self-label">You</span><?php endif; ?>
                                        </td>
                                        <td class="user-email col-hide-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="col-hide-sm">
                                            <span class="role-badge <?php echo $userManager->getRoleBadgeClass($user['role']); ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-sm">
                                            <span class="status-icon <?php echo $user['is_verified'] ? 'enabled' : 'disabled'; ?>">
                                                <?php echo icon($user['is_verified'] ? 'enable' : 'disable', 'icon-sm'); ?>
                                                <?php echo $user['is_verified'] ? 'Enabled' : 'Disabled'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!$is_self): ?>
                                                <div class="actions-cell">
                                                    <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn-action btn-edit">
                                                        <?php echo icon('edit', 'icon-sm'); ?> <span>Edit</span>
                                                    </a>
                                                    <form action="usermanagement.php?role=<?php echo $current_filter; ?>" method="POST" class="action-form">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <select name="new_role" class="role-select">
                                                            <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                            <option value="Standard User" <?php echo $user['role'] === 'Standard User' ? 'selected' : ''; ?>>Standard User</option>
                                                            <option value="Viewer" <?php echo $user['role'] === 'Viewer' ? 'selected' : ''; ?>>Viewer</option>
                                                        </select>
                                                        <button type="submit" class="btn-action btn-save"><?php echo icon('save', 'icon-sm'); ?> <span>Save</span></button>
                                                    </form>

                                                    <form action="usermanagement.php?role=<?php echo $current_filter; ?>" method="POST" class="action-form">
                                                        <input type="hidden" name="action" value="<?php echo $user['is_verified'] ? 'disable' : 'enable'; ?>">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit"
                                                            name="toggle_status"
                                                            class="btn-action <?php echo $user['is_verified'] ? 'btn-disable' : 'btn-enable'; ?>"
                                                            title="<?php echo $user['is_verified'] ? 'Disable User' : 'Enable User'; ?>">
                                                            <?php echo icon($user['is_verified'] ? 'disable' : 'enable', 'icon-sm'); ?>
                                                            <span><?php echo $user['is_verified'] ? 'Disable' : 'Enable'; ?></span>
                                                        </button>
                                                    </form>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>

</html>