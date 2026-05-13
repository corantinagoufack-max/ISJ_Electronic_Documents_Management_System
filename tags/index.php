<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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
    <title>Tags | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/7-pages/dashboard.css">
    <style>
        .placeholder-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 75vh;
            max-width: 580px;
            margin: 0 auto;
            text-align: center;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            background: #f5f3ff;
            color: #8b5cf6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 24px;
        }

        .placeholder-content h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .placeholder-content p {
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 32px;
        }

        .tag-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #faf5ff;
            border: 1px solid #e9d5ff;
            color: #7c3aed;
            padding: 6px 16px;
            border-radius: 100px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background: #8b5cf6;
            border-radius: 50%;
        }

        .back-nav {
            margin-top: 48px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }

        .back-nav:hover {
            color: #7c3aed;
        }
    </style>
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <aside class="sidebar">
            <div class="sidebar-brand">
                <div class="brand-header">
                    <img src="../assets/images/logo.png" alt="ISJ" class="brand-logo-img">
                    <div class="brand-text">
                        <h3>The Academic Vault</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="../modules/documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="index.php" class="nav-item active"><?php echo icon('tags', 'nav-icon'); ?> Tags</a>
                <a href="../modules/folder-browser/index.php" class="nav-item"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?>
                    <a href="../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div class="placeholder-content">
                <div class="icon-circle">
                    <?php echo icon('tags', 'w-10 h-10'); ?>
                </div>

                <h1>Document Tagging</h1>
                <p>We are building a smart keyword system to help you categorize and search files instantly. This module will be enabled in the May 2026 release.</p>

                <div class="tag-status">
                    <span class="pulse-dot"></span>
                    Planned Feature
                </div>

                <a href="../dashboard.php" class="back-nav">
                    ← Back to Dashboard
                </a>
            </div>
        </main>
    </div>
</body>

</html>