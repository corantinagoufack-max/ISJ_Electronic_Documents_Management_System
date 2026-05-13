<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$userRole = $_SESSION['user_role'];
$isAdmin  = ($userRole === 'Admin');
$isViewer = ($userRole === 'Viewer');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <style>
        .settings-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 80vh;
            max-width: 600px;
            margin: 0 auto;
            text-align: center;
        }

        .icon-box {
            padding: 20px;
            background: #f1f5f9;
            border-radius: 16px;
            color: #3b82f6;
            margin-bottom: 24px;
        }

        .settings-placeholder h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 12px;
        }

        .settings-placeholder p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #eff6ff;
            color: #2563eb;
            padding: 8px 16px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #3b82f6;
            border-radius: 50%;
        }

        .btn-back {
            margin-top: 40px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-back:hover {
            color: #1e293b;
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-header">
                    <img src="../../assets/images/logo.png" alt="ISJ" class="brand-logo-img">
                    <div class="brand-text">
                        <h3>The Academic Vault</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="../documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="../documents/shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared With Me </a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="settings.php" class="nav-item active"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="settings-placeholder">
                <div class="icon-box">
                    <?php echo icon('settings', 'w-12 h-12'); ?>
                </div>

                <h1>Settings Module</h1>
                <p>We are currently configuring the profile and system preference panels. This feature will be available in the May 2026 update.</p>

                <div class="status-badge">
                    <span class="status-dot"></span>
                    Under Development
                </div>

                <a href="../../dashboard.php" class="btn-back">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path d="M19 12H5M12 19l-7-7 7-7"></path>
                    </svg>
                    Return to Dashboard
                </a>
            </div>
        </main>
    </div>
<script src="../../assets/js/main.js"></script>
</body>

</html>