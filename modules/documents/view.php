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

// Fetch document
$stmt = $conn->prepare("
    SELECT d.*, c.name as category_name, u.fullname as uploader_name
    FROM documents d
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN users u ON d.uploaded_by = u.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: index.php?alert=error&msg=Document+not+found.");
    exit();
}

// Permission check
$isAdmin  = ($userRole === 'Admin');
$isOwner  = ($doc['uploaded_by'] == $userId);
$isViewer = ($userRole === 'Viewer');

if (!$isAdmin && !$isOwner) {
    $chk = $conn->prepare("SELECT id FROM document_shares WHERE document_id=? AND shared_with_user_id=?");
    $chk->bind_param("ii", $docId, $userId);
    $chk->execute();
    $isShared = $chk->get_result()->num_rows > 0;
    $isPublic = in_array($doc['status'], ['Published', 'Final']);
    if (!$isShared && !$isPublic) {
        header("Location: index.php?alert=error&msg=You+do+not+have+permission+to+view+this+document.");
        exit();
    }
}

$canEdit  = $isAdmin || ($isOwner && $userRole === 'Standard User');
$canShare = ($isAdmin || $isOwner) && !$isViewer;
$canDelete = ($isAdmin || $isOwner) && !$isViewer;

// Fetch version history
$vers = $conn->prepare("SELECT dv.*, u.fullname FROM document_versions dv LEFT JOIN users u ON dv.created_by=u.id WHERE dv.document_id=? ORDER BY dv.created_at DESC");
$vers->bind_param("i", $docId);
$vers->execute();
$versions = $vers->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch shares
$shares = $conn->prepare("SELECT ds.*, u.fullname, u.email FROM document_shares ds LEFT JOIN users u ON ds.shared_with_user_id=u.id WHERE ds.document_id=?");
$shares->bind_param("i", $docId);
$shares->execute();
$shared_with = $shares->get_result()->fetch_all(MYSQLI_ASSOC);

$typeColors = [
    'pdf'  => '#dc2626',
    'docx' => '#0284c7',
    'doc' => '#0284c7',
    'jpg'  => '#7c3aed',
    'jpeg' => '#7c3aed',
    'png' => '#7c3aed',
    'zip'  => '#d97706',
    'xlsx' => '#16a34a',
    'pptx' => '#ea580c',
    'txt' => '#475569',
];
$ext       = strtolower($doc['extension'] ?? 'default');
$iconColor = $typeColors[$ext] ?? '#64748b';
$isImage   = in_array($ext, ['jpg', 'jpeg', 'png']);
$isPdf     = ($ext === 'pdf');

// Try both storage paths
$fileUrl = null;
$paths = [
    '../../storage/documents/users/' . $doc['file_path'],
    '../../storage/uploads/' . $doc['file_path'],
];
foreach ($paths as $p) {
    if (file_exists($p)) {
        $fileUrl = $p;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($doc['title']); ?> | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/view.css">
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
                <?php if (!$isViewer): ?>
                    <a href="upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <?php endif; ?>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <!-- Header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:1.5rem;">
                <div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;font-weight:700;margin-bottom:.4rem;">
                        <a href="index.php" style="color:#94a3b8;text-decoration:none;">Documents</a> / View
                    </div>
                    <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0;"><?php echo htmlspecialchars($doc['title']); ?></h1>
                    <div style="margin-top:.5rem;display:flex;gap:.6rem;align-items:center;">
                        <span class="status-pill <?php echo strtolower($doc['status'] ?? 'draft'); ?>"><?php echo htmlspecialchars($doc['status'] ?? 'Draft'); ?></span>
                        <span class="version-badge">v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></span>
                        <span style="font-size:.78rem;color:#94a3b8;"><?php echo strtoupper($ext); ?> file</span>
                    </div>
                </div>
                <a href="index.php" style="padding:9px 16px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;font-size:.85rem;text-decoration:none;">← Back</a>
            </div>

            <!-- Action bar -->
            <div class="action-bar">
                <a href="../../download.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-green"><?php echo icon('download', ''); ?> Download</a>
                <?php if ($canShare): ?>
                    <a href="share.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-blue"><?php echo icon('shared', ''); ?> Share</a>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                    <a href="edit.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-outline"><?php echo icon('edit', ''); ?> Edit Metadata</a>
                <?php endif; ?>
                <?php if ($isAdmin || $isOwner): ?>
                    <a href="versions.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-outline"><?php echo icon('eye', ''); ?> Versions</a>
                <?php endif; ?>
                <?php if ($canDelete): ?>
                    <button class="btn-action btn-danger"
                        onclick="confirmDelete(<?php echo $doc['id']; ?>, '<?php echo addslashes(htmlspecialchars($doc['title'])); ?>')">
                        🗑 Delete
                    </button>
                <?php endif; ?>
            </div>

            <div class="doc-layout">
                <!-- Left: preview + description -->
                <div>
                    <div class="card">
                        <h3>Document Preview</h3>
                        <div class="preview-box">
                            <?php if ($isImage && $fileUrl): ?>
                                <img src="<?php echo htmlspecialchars($fileUrl); ?>" alt="Document preview">
                            <?php elseif ($isPdf && $fileUrl): ?>
                                <iframe src="<?php echo htmlspecialchars($fileUrl); ?>"></iframe>
                            <?php else: ?>
                                <div class="doc-icon-lg">
                                    <span style="color:<?php echo $iconColor; ?>; font-size:4rem;">
                                        <?php echo icon('file-type-' . $ext, ''); ?>
                                    </span>
                                    <p style="color:#64748b;font-size:.9rem;">Preview not available for <?php echo strtoupper($ext); ?> files.</p>
                                    <a href="../../download.php?id=<?php echo $doc['id']; ?>" class="btn-action btn-green"><?php echo icon('download', ''); ?> Download to View</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($doc['description'])): ?>
                        <div class="card">
                            <h3>Description</h3>
                            <p class="desc-text"><?php echo htmlspecialchars($doc['description']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right: metadata, versions, shares -->
                <div>
                    <div class="card">
                        <h3>Document Details</h3>
                        <div class="meta-grid">
                            <div class="meta-item">
                                <label>Title</label>
                                <span><?php echo htmlspecialchars($doc['title']); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>Subject</label>
                                <span><?php echo htmlspecialchars($doc['subject'] ?? '—'); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>Author</label>
                                <span><?php echo htmlspecialchars($doc['author'] ?? '—'); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>Uploaded By</label>
                                <span><?php echo htmlspecialchars($doc['uploader_name'] ?? '—'); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>Category</label>
                                <span><?php echo htmlspecialchars($doc['category_name'] ?? '—'); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>File Type</label>
                                <span style="color:<?php echo $iconColor; ?>;font-weight:700;"><?php echo strtoupper($ext); ?></span>
                            </div>
                            <div class="meta-item">
                                <label>File Size</label>
                                <span><?php echo round(($doc['size'] ?? 0) / 1024, 1); ?> KB</span>
                            </div>
                            <div class="meta-item">
                                <label>Upload Date</label>
                                <span><?php echo date('d/m/Y H:i', strtotime($doc['upload_date'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Version history -->
                    <div class="card">
                        <h3>Version History</h3>
                        <div class="version-item" style="font-weight:600;">
                            <span class="version-badge" style="background:#dcfce7;color:#166534;border-color:#bbf7d0;">
                                v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?> — Current
                            </span>
                            <span style="font-size:.75rem;color:#94a3b8;"><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></span>
                        </div>
                        <?php foreach ($versions as $v): ?>
                            <div class="version-item">
                                <span class="version-badge">v<?php echo htmlspecialchars($v['version_number']); ?></span>
                                <span style="color:#64748b;font-size:.8rem;flex:1;margin:0 10px;">
                                    <?php echo htmlspecialchars(substr($v['comment'] ?? '—', 0, 40)); ?>
                                </span>
                                <span style="font-size:.72rem;color:#94a3b8;"><?php echo date('d/m/Y', strtotime($v['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($isAdmin || $isOwner): ?>
                            <a href="versions.php?id=<?php echo $doc['id']; ?>" style="font-size:.8rem;color:#3498db;text-decoration:none;margin-top:.5rem;display:inline-block;">Manage versions </a>
                        <?php endif; ?>
                    </div>

                    <!-- Shared with -->
                    <?php if ($canShare && !empty($shared_with)): ?>
                        <div class="card">
                            <h3>Shared With (<?php echo count($shared_with); ?>)</h3>
                            <?php foreach ($shared_with as $s): ?>
                                <div class="share-item">
                                    <div>
                                        <strong style="font-size:.85rem;"><?php echo htmlspecialchars($s['fullname']); ?></strong><br>
                                        <span style="font-size:.75rem;color:#94a3b8;"><?php echo htmlspecialchars($s['email']); ?></span>
                                    </div>
                                    <span style="font-size:.72rem;background:#f0f9ff;color:#0369a1;padding:2px 8px;border-radius:4px;border:1px solid #bae6fd;">
                                        <?php echo htmlspecialchars($s['permission'] ?? 'view'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                            <a href="share.php?id=<?php echo $doc['id']; ?>" style="font-size:.8rem;color:#3498db;text-decoration:none;margin-top:.5rem;display:inline-block;">Manage sharing →</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="../../assets/js/documents.js"></script>
<script src="../../assets/js/main.js"></script>
</body>

</html>