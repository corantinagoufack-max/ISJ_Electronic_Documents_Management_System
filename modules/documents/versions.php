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

// Fetch the main document
$stmt = $conn->prepare("SELECT d.*, c.name as category_name FROM documents d LEFT JOIN categories c ON d.category_id=c.id WHERE d.id=?");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: index.php");
    exit();
}

$isOwner = ($doc['uploaded_by'] == $userId);
$isAdmin = ($userRole === 'Admin');
if (!$isAdmin && !$isOwner) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle new version upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['version_file'])) {
    $newVersion = isset($_POST['version_number']) ? trim($_POST['version_number']) : '';
    $comment    = isset($_POST['comment']) ? trim($_POST['comment']) : '';

    // Validate version number format
    if (!preg_match('/^\d+(\.\d+)?$/', $newVersion)) {
        $error = "Invalid version format. Use numbers like 1, 1.1, 2.0";
    } else {
        // Check if this exact version already exists
        $chk = $conn->prepare("SELECT id FROM document_versions WHERE document_id=? AND version_number=?");
        $chk->bind_param("is", $docId, $newVersion);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "Version $newVersion already exists for this document.";
        } else {
            // Check if this version matches the current_version
            if ($doc['current_version'] == $newVersion) {
                $error = "Version $newVersion is already the current version.";
            } else {
                // Process file upload
                $file = $_FILES['version_file'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'docx', 'doc', 'jpg', 'png'];

                if (!in_array($ext, $allowed)) {
                    $error = "File type not allowed. Use: PDF, DOCX, JPG, PNG.";
                } elseif ($file['error'] !== 0) {
                    $error = "File upload error. Please try again.";
                } else {
                    $safeName = 'ISJ_v' . $newVersion . '_' . uniqid() . '.' . $ext;
                    $dest = '../../storage/uploads/' . $safeName;

                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // MODIFIED: Corrected column names and parameter count to match your DB image
                        $ins = $conn->prepare("INSERT INTO document_versions (document_id, version_number, file_path, change_description, changed_by, comment, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");

                        // Binding 7 parameters: document_id(i), version_number(s), file_path(s), change_description(s), changed_by(i), comment(s), created_by(i)
                        $ins->bind_param("isssisi", $docId, $newVersion, $safeName, $comment, $userId, $comment, $userId);
                        $ins->execute();

                        // Update the main document's current version
                        $upd = $conn->prepare("UPDATE documents SET current_version=?, file_path=?, extension=? WHERE id=?");
                        $upd->bind_param("sssi", $newVersion, $safeName, $ext, $docId);
                        $upd->execute();

                        $success = "Version $newVersion uploaded successfully.";

                        // Refresh doc data for display
                        $stmt->execute();
                        $doc = $stmt->get_result()->fetch_assoc();
                    } else {
                        $error = "Failed to save file. Check storage/uploads folder permissions.";
                    }
                }
            }
        }
    }
}

// Fetch version history - Using 'changed_by' to match your schema
$vers = $conn->prepare("SELECT dv.*, u.fullname FROM document_versions dv LEFT JOIN users u ON dv.changed_by=u.id WHERE dv.document_id=? ORDER BY dv.created_at DESC");
$vers->bind_param("i", $docId);
$vers->execute();
$versions = $vers->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Document Versions | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/versions.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                        <h3>The Academic Vault</h3><span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="index.php" class="nav-item active"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;font-weight:700;margin-bottom:.3rem;">Version Control</div>
                    <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;"><?php echo htmlspecialchars($doc['title']); ?></h1>
                    <p style="color:#64748b;font-size:.85rem;">Current Version: <strong>v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></strong></p>
                </div>
                <a href="index.php" class="btn-back"> Back</a>
            </div>

            <?php if ($error): ?><div class="alert-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

            <div class="card">
                <h2>Upload New Version</h2>
                <form method="POST" enctype="multipart/form-data">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
                        <div class="form-group">
                            <label>New Version Number</label>
                            <input type="text" name="version_number" placeholder="e.g. 1.1, 2.0" required>
                            <div class="version-hint">
                                Current: <strong>v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></strong>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Replacement File</label>
                            <input type="file" name="version_file" accept=".pdf,.docx,.doc,.jpg,.png" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Change Notes</label>
                        <textarea name="comment" rows="2" placeholder="Describe what changed..."></textarea>
                    </div>
                    <button type="submit" class="btn-save">Save New Version</button>
                </form>
            </div>

            <div class="card">
                <h2>Version History</h2>
                <?php if (empty($versions)): ?>
                    <p style="color:#94a3b8;font-size:.85rem;">No history found. Current version: v<?php echo htmlspecialchars($doc['current_version']); ?>.</p>
                <?php else: ?>
                    <ul class="version-list">
                        <?php foreach ($versions as $v): ?>
                            <li class="version-item">
                                <span class="v-num <?php echo ($v['version_number'] == $doc['current_version']) ? 'v-current' : ''; ?>">
                                    v<?php echo htmlspecialchars($v['version_number']); ?>
                                </span>
                                <div class="v-comment">
                                    <?php echo htmlspecialchars($v['change_description'] ?: ($v['comment'] ?: 'Initial upload/No notes')); ?><br>
                                    <span class="v-meta">Modified by <?php echo htmlspecialchars($v['fullname'] ?? 'System'); ?></span>
                                </div>
                                <span class="v-meta"><?php echo date('d/m/Y H:i', strtotime($v['created_at'])); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../../assets/js/main.js"></script>
</body>

</html>