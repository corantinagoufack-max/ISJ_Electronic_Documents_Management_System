<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/UserManager.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    header("Location: ../../dashboard.php");
    exit();
}

function icon(string $name, string $classes = ''): string
{
    $path = __DIR__ . "/../../assets/icons/{$name}.svg";
    if (!file_exists($path)) return '';
    $svg = file_get_contents($path);
    $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
    if (!empty($classes)) $svg = str_replace('<svg', "<svg class=\"{$classes}\"", $svg);
    return "<span class=\"icon {$classes}\">{$svg}</span>";
}

$error   = isset($_GET['error'])  ? $_GET['error']  : '';
$success = isset($_GET['status']) && $_GET['status'] === 'created' ? 'User created successfully.' : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/usermanagement.css">
    <style>
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }

        .create-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 720px;
        }

        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .form-card h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: .3rem;
        }

        .form-card p {
            color: #64748b;
            font-size: .88rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .75rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 6px;
        }

        .form-group label .icon svg {
            width: 13px;
            height: 13px;
            color: #94a3b8;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: .9rem;
            font-family: inherit;
            color: #0f172a;
            background: #fff;
            outline: none;
            transition: border-color .2s, box-shadow .2s;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, .1);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 1.2rem 0;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 1.5rem;
        }

        .btn-create-user {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 24px;
            background: #0f172a;
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: .9rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-create-user:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(15, 23, 42, .2);
        }

        .btn-cancel-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 18px;
            background: #f8fafc;
            color: #475569;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            font-size: .88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .2s;
        }

        .btn-cancel-link:hover {
            background: #f1f5f9;
        }

        /* Role cards */
        .role-info-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
        }

        .role-info-card h3 {
            font-size: .85rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
        }

        .role-item {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 8px;
        }

        .role-item.admin {
            background: #fff1f2;
            border: 1px solid #fecdd3;
        }

        .role-item.teacher {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
        }

        .role-item.viewer {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
        }

        .role-item strong {
            display: block;
            font-size: .82rem;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .role-item.admin strong {
            color: #be123c;
        }

        .role-item.teacher strong {
            color: #166534;
        }

        .role-item.viewer strong {
            color: #0369a1;
        }

        .role-item p {
            font-size: .76rem;
            line-height: 1.5;
            color: #374151;
            margin: 0;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            padding: 12px 16px;
            border-radius: 9px;
            margin-bottom: 1rem;
            font-size: .88rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
            padding: 12px 16px;
            border-radius: 9px;
            margin-bottom: 1rem;
            font-size: .88rem;
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 42px;
        }

        .toggle-pw {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0;
            line-height: 0;
        }

        .toggle-pw:hover {
            color: #0f172a;
        }

        /* Sidebar */
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 8px;
            color: #94a3b8;
            text-decoration: none;
            font-size: .88rem;
            font-weight: 500;
            transition: all .2s;
            margin-bottom: 2px;
        }

        .nav-item:hover {
            background: rgba(255, 255, 255, .06);
            color: #fff;
        }

        .nav-item.active {
            background: rgba(52, 152, 219, .2);
            color: #38bdf8;
        }

        .nav-item svg,
        .nav-item .icon svg {
            width: 18px;
            height: 18px;
            opacity: .7;
            flex-shrink: 0;
        }

        .nav-item.active .icon svg {
            opacity: 1;
        }

        .nav-divider {
            height: 1px;
            background: rgba(255, 255, 255, .08);
            margin: 10px 0;
        }

        .btn-upload {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #3498db;
            color: #fff;
            padding: 11px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: .85rem;
            text-decoration: none;
            margin: 0 1.5rem 1rem;
        }

        .btn-upload:hover {
            background: #2980b9;
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            color: rgba(255, 255, 255, .5);
            text-decoration: none;
            font-size: .85rem;
        }

        .logout-link:hover {
            color: #ef4444;
        }

        /* Collapsible role capabilities */
        .role-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 11px 16px;
            font-size: .88rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: all .2s;
            text-align: left;
        }

        .role-toggle-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .role-toggle-btn .chevron {
            width: 16px;
            height: 16px;
            transition: transform .25s ease;
            flex-shrink: 0;
        }

        .role-toggle-btn.open .chevron {
            transform: rotate(180deg);
        }

        .role-capabilities-panel {
            display: none;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            overflow: hidden;
            margin-top: -4px;
        }

        .role-capabilities-panel.open {
            display: block;
        }

        .role-capabilities-inner {
            padding: 1.2rem;
            background: #fff;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .after-note {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 1rem 1.2rem;
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
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
                    <img src="../../assets/images/logo.png" alt="ISJ" class="brand-logo-img">
                    <div class="brand-text">
                        <h3>The Academic DMS</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="../../modules/documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="../../modules/documents/shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <a href="../../tags/index.php" class="nav-item"><?php echo icon('tags', 'nav-icon'); ?> Tags</a>
                <a href="../../modules/folder-browser/index.php" class="nav-item"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <a href="../../usermanagement.php" class="nav-item active"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <a href="settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../../modules/documents/upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="margin-bottom:1.5rem;">
                <div class="breadcrumb">Admin <span>/</span> <a href="../../usermanagement.php" style="color:#94a3b8;text-decoration:none;">User Management</a> <span>/</span> Create User</div>
                <h1 class="page-title" style="margin-top:.5rem;">Create New User</h1>
                <p style="color:#64748b;font-size:.9rem;margin-top:.3rem;">Add a new member to the Academic Vault system.</p>
            </div>

            <?php if ($error):   ?><div class="alert-error"><?php echo htmlspecialchars(urldecode($error)); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>

            <div class="create-layout">
                <div class="form-card">
                    <h2>Account Details</h2>
                    <p>Fill in all fields. The user will be able to login immediately.</p>

                    <form action="process-create.php" method="POST">
                        <div class="form-group">
                            <label><?php echo icon('users', ''); ?> Full Name</label>
                            <input type="text" name="fullname" placeholder="ALOMA MARC XAVIER" required autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label><?php echo icon('mail', ''); ?> Email Address</label>
                            <input type="email" name="email" placeholder="user@institutsaintjean.org" required autocomplete="off">
                        </div>

                        <div class="form-divider"></div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label><?php echo icon('settings', ''); ?> Assign Role</label>
                                <select name="role" required>
                                    <option value="" disabled selected>Select a role...</option>
                                    <option value="Viewer">Student Viewer</option>
                                    <option value="Standard User">Teacher Standard User</option>
                                    <option value="Admin">System Administrator</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label><?php echo icon('lock', ''); ?> Initial Password</label>
                                <div class="password-wrap">
                                    <input type="password" name="password" id="pwField" placeholder="Min. 8 characters" required minlength="8">
                                    <button type="button" class="toggle-pw" onclick="togglePw()" title="Show/hide password">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="form-divider"></div>

                        <div class="form-actions">
                            <button type="submit" class="btn-create-user"><?php echo icon('save', ''); ?> Confirm &amp; Create User</button>
                            <a href="../../usermanagement.php" class="btn-cancel-link">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Role capabilities collapsible -->
                <div>
                    <button type="button" class="role-toggle-btn" id="roleToggleBtn" onclick="toggleRolePanel()">
                        <span>&#128218; View Role Capabilities</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <div class="role-capabilities-panel" id="rolePanel">
                        <div class="role-capabilities-inner">
                            <div class="role-item admin">
                                <strong>Administrator</strong>
                                <p>Full access to all documents, user management, system settings, and can archive any document or change any user's role.</p>
                            </div>
                            <div class="role-item teacher">
                                <strong>Teacher Standard User</strong>
                                <p>Can upload, edit, and share their own documents. Can view all published/final documents. Cannot access User Management.</p>
                            </div>
                            <div class="role-item viewer">
                                <strong>Student Viewer</strong>
                                <p>Read-only access to published and documents shared with them. Can download files if permission is granted. Cannot upload or edit.</p>
                            </div>
                        </div>
                    </div>

                    <div class="after-note" style="margin-top:.75rem;">
                        <div style="font-size:.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:.5rem;">After Creating</div>
                        <p style="font-size:.82rem;color:#374151;line-height:1.6;margin:0;">The user is created as <strong>Active</strong> and can log in immediately. You can disable or change their role at any time from the User Management page.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../../assets/js/user.js"></script>
    <script>
        function togglePw() {
            const f = document.getElementById('pwField');
            f.type = f.type === 'password' ? 'text' : 'password';
        }

        function toggleRolePanel() {
            const btn = document.getElementById('roleToggleBtn');
            const panel = document.getElementById('rolePanel');
            const isOpen = panel.classList.contains('open');
            panel.classList.toggle('open', !isOpen);
            btn.classList.toggle('open', !isOpen);
            btn.querySelector('span').textContent = isOpen ?
                '📘 View Role Capabilities' :
                '📘 Hide Role Capabilities';
        }
    </script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>