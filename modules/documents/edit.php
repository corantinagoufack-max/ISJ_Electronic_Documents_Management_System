<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$docId    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

if ($docId <= 0) {
    header("Location: index.php?alert=error&msg=Invalid+document+ID.");
    exit();
}

// Viewers cannot edit
if ($userRole === 'Viewer') {
    header("Location: view.php?id=$docId&alert=error&msg=Viewers+do+not+have+permission+to+edit+documents.");
    exit();
}

$stmt = $conn->prepare("SELECT d.*, c.name as category_name FROM documents d LEFT JOIN categories c ON d.category_id=c.id WHERE d.id=?");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: index.php?alert=error&msg=Document+not+found.");
    exit();
}

$isOwner = ($doc['uploaded_by'] == $userId);
$isAdmin = ($userRole === 'Admin');

// Access: Admin edits all; Standard User edits only their own
if (!$isAdmin && !($isOwner && $userRole === 'Standard User')) {
    header("Location: view.php?id=$docId&alert=error&msg=You+do+not+have+permission+to+edit+this+document.");
    exit();
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $subject     = trim($_POST['subject'] ?? '');
    $author      = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $cat_id      = (int)($_POST['category_id'] ?? 0);
    $status      = $_POST['status'] ?? $doc['status'];

    $allowedStatuses = ['Draft', 'Published', 'Final'];
    if ($isAdmin) $allowedStatuses[] = 'Archived';
    if (!in_array($status, $allowedStatuses)) $status = $doc['status'];

    if (empty($title)) {
        header("Location: edit.php?id=$docId&alert=error&msg=Document+title+is+required.");
        exit();
    }
    if ($cat_id <= 0) {
        header("Location: edit.php?id=$docId&alert=error&msg=Please+select+a+valid+category.");
        exit();
    }

    $upd = $conn->prepare("UPDATE documents SET title=?, subject=?, author=?, description=?, category_id=?, status=? WHERE id=?");
    $upd->bind_param("ssssssi", $title, $subject, $author, $description, $cat_id, $status, $docId);

    if ($upd->execute()) {
        header("Location: view.php?id=$docId&alert=success&msg=" . urlencode("Document '$title' was updated successfully."));
        exit();
    } else {
        header("Location: edit.php?id=$docId&alert=error&msg=" . urlencode("Update failed: " . $conn->error));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/edit.css">
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
    <style>
        /* Override two-column layout to single column */
        .edit-layout {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            max-width: 780px;
        }

        /* Collapsible permissions toggle */
        .perm-toggle-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            background: #f0f9ff;
            border: 1.5px solid #bae6fd;
            border-radius: 9px;
            padding: 11px 16px;
            font-size: .88rem;
            font-weight: 600;
            color: #0369a1;
            cursor: pointer;
            transition: all .2s;
            text-align: left;
        }

        .perm-toggle-btn:hover {
            background: #e0f2fe;
            border-color: #7dd3fc;
        }

        .perm-toggle-btn .chevron {
            width: 16px;
            height: 16px;
            transition: transform .25s ease;
            flex-shrink: 0;
        }

        .perm-toggle-btn.open .chevron {
            transform: rotate(180deg);
        }

        .perm-panel {
            display: none;
            border: 1.5px solid #bae6fd;
            border-top: none;
            border-radius: 0 0 9px 9px;
            overflow: hidden;
            margin-top: -6px;
        }

        .perm-panel.open {
            display: block;
        }

        /* Quick links row */
        .quick-links-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding-top: .5rem;
        }

        .quick-link-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: .82rem;
            font-weight: 600;
            text-decoration: none;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            transition: all .2s;
        }

        .quick-link-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #0f172a;
        }

        .quick-link-btn.green {
            background: #f0fdf4;
            border-color: #bbf7d0;
            color: #16a34a;
        }

        .quick-link-btn.green:hover {
            background: #dcfce7;
        }
    </style>
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
                        <h3>The Academic Vault</h3><span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="index.php" class="nav-item active"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <a href="../../tags/index.php" class="nav-item"><?php echo icon('tags', 'nav-icon'); ?> Tags</a>
                <a href="../../modules/folder-browser/index.php" class="nav-item"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="../users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;font-weight:700;margin-bottom:.3rem;">
                        <a href="index.php" style="color:#94a3b8;text-decoration:none;">Documents</a> /
                        <a href="view.php?id=<?php echo $docId; ?>" style="color:#94a3b8;text-decoration:none;">View</a> / Edit
                    </div>
                    <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;">Edit Document</h1>
                </div>
                <a href="view.php?id=<?php echo $docId; ?>" style="padding:9px 16px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;text-decoration:none;">← Back</a>
            </div>

            <div class="edit-layout">
                <!-- Form -->
                <div>
                    <div class="card">
                        <h3>Document Metadata</h3>
                        <form method="POST" id="editForm" novalidate>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Document Title *</label>
                                    <input type="text" name="title" id="titleField" value="<?php echo htmlspecialchars($doc['title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Subject / Theme</label>
                                    <input type="text" name="subject" value="<?php echo htmlspecialchars($doc['subject'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Original Author</label>
                                    <input type="text" name="author" value="<?php echo htmlspecialchars($doc['author'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Category *</label>
                                    <select name="category_id" id="categoryField" required>
                                        <option value="">— Select category —</option>
                                        <?php foreach ($categories as $c): ?>
                                            <option value="<?php echo $c['id']; ?>" <?php echo ($doc['category_id'] == $c['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="Draft" <?php echo $doc['status'] === 'Draft'     ? 'selected' : ''; ?>>Draft</option>
                                    <option value="Published" <?php echo $doc['status'] === 'Published' ? 'selected' : ''; ?>>Published</option>
                                    <option value="Final" <?php echo $doc['status'] === 'Final'     ? 'selected' : ''; ?>>Final</option>
                                    <?php if ($isAdmin): ?>
                                        <option value="Archived" <?php echo $doc['status'] === 'Archived'  ? 'selected' : ''; ?>>Archived (Admin only)</option>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="4"><?php echo htmlspecialchars($doc['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>File (read-only)</label>
                                    <input type="text" value="<?php echo htmlspecialchars($doc['file_path'] ?? ''); ?>" class="readonly-field" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Current Version (read-only)</label>
                                    <input type="text" value="v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?>" class="readonly-field" readonly>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn-save"><?php echo icon('save', ''); ?> Save Changes</button>
                                <a href="view.php?id=<?php echo $docId; ?>" class="btn-cancel">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Edit Permissions — collapsible -->
                <div>
                    <button type="button" class="perm-toggle-btn" id="permToggleBtn" onclick="togglePermPanel()">
                        <span>&#128274; View Edit Permissions</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div class="perm-panel" id="permPanel">
                        <div class="info-card" style="border-radius:0;border:none;">
                            <strong>Your role: <?php echo htmlspecialchars($userRole); ?></strong>
                            <?php if ($isAdmin): ?>
                                As an Admin, you can edit all document metadata and change status to Archived.
                            <?php else: ?>
                                As a Standard User, you can edit the metadata of documents you uploaded. To add a new file version, use Version Control.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links — always visible, horizontal row -->
                <div class="card" style="margin-bottom:0;">
                    <h3>Quick Links</h3>
                    <div class="quick-links-row">
                        <a href="view.php?id=<?php echo $docId; ?>" class="quick-link-btn">&#128065; View Document</a>
                        <a href="versions.php?id=<?php echo $docId; ?>" class="quick-link-btn">&#128196; Manage Versions</a>
                        <a href="share.php?id=<?php echo $docId; ?>" class="quick-link-btn">&#128257; Share with Users</a>
                        <a href="../../download.php?id=<?php echo $docId; ?>" class="quick-link-btn green">&#11123; Download File</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/documents.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script>
        function togglePermPanel() {
            const btn = document.getElementById('permToggleBtn');
            const panel = document.getElementById('permPanel');
            const isOpen = panel.classList.contains('open');
            panel.classList.toggle('open', !isOpen);
            btn.classList.toggle('open', !isOpen);
            btn.querySelector('span').textContent = isOpen ?
                '🔒 View Edit Permissions' :
                '🔒 Hide Edit Permissions';
        }
    </script>
</body>

</html>