<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Generate CSRF token for delete forms
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Build query conditions
$search  = isset($_GET['q'])      ? trim($_GET['q'])       : '';
$status  = isset($_GET['status']) ? $_GET['status']        : '';
$cat_id  = isset($_GET['cat'])    ? (int)$_GET['cat']      : 0;

$where  = [];
$params = [];
$types  = '';

// Non-admins see their own docs + shared docs
if ($userRole !== 'Admin') {
    $where[]  = "(d.uploaded_by = ? OR EXISTS (
                    SELECT 1 FROM document_shares ds
                    WHERE ds.document_id = d.id AND ds.shared_with_user_id = ?
                 ))";
    $params[] = $userId;
    $params[] = $userId;
    $types   .= 'ii';
}

if ($search !== '') {
    $like     = '%' . $conn->real_escape_string($search) . '%';
    $where[]  = "(d.title LIKE ? OR d.subject LIKE ? OR d.author LIKE ? OR d.description LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ssss';
}
if ($status !== '') {
    $where[]  = "d.status = ?";
    $params[] = $status;
    $types   .= 's';
}
if ($cat_id > 0) {
    $where[]  = "d.category_id = ?";
    $params[] = $cat_id;
    $types   .= 'i';
}

$sql = "SELECT d.*, c.name as category_name, u.fullname as uploader_name
        FROM documents d
        LEFT JOIN categories c ON d.category_id = c.id
        LEFT JOIN users u ON d.uploaded_by = u.id";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY d.upload_date DESC";

$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$typeIcons = [
    'pdf'   => 'fa-file-pdf',
    'doc'   => 'fa-file-word',
    'docx'  => 'fa-file-word',
    'xls'   => 'fa-file-excel',
    'xlsx'  => 'fa-file-excel',
    'ppt'   => 'fa-file-powerpoint',
    'pptx'  => 'fa-file-powerpoint',
    'jpg'   => 'fa-file-image',
    'jpeg'  => 'fa-file-image',
    'png'   => 'fa-file-image',
    'gif'   => 'fa-file-image',
    'zip'   => 'fa-file-archive',
    'rar'   => 'fa-file-archive',
    'txt'   => 'fa-file-alt',
    'default' => 'fa-file'
];
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
    'txt'  => '#475569',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/Mydocuments.css">
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
                        <h3>The Academic Vault</h3>
                        <span>ISJ-DMS GLOBAL</span>
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
                <?php if ($userRole === 'Admin'): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="../users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <?php if ($userRole !== 'Viewer'): ?>
                    <a href="upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <?php endif; ?>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">

            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <div>
                    <div style="font-size:.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:.1em;font-weight:700;margin-bottom:.4rem;">My Documents</div>
                    <h1 style="font-size:1.6rem;font-weight:800;color:#0f172a;margin:0;">Document Vault</h1>
                    <p style="color:#64748b;font-size:.9rem;margin-top:.3rem;">
                        <?php echo count($docs); ?> document<?php echo count($docs) !== 1 ? 's' : ''; ?> found
                    </p>
                </div>
                <?php if ($userRole !== 'Viewer'): ?>
                    <a href="upload.php" class="btn-upload-sm"><?php echo icon('upload', ''); ?> Upload New</a>
                <?php endif; ?>
            </div>

            <!-- Search bar -->
            <form method="GET" action="index.php" class="page-toolbar">
                <div class="search-form">
                    <input type="text" name="q" placeholder="Search by title, author, subject..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit"><?php echo icon('filter', ''); ?> Search</button>
                </div>
                <a href="search.php" class="adv-search-link">Advanced Search </a>
            </form>

            <!-- Quick filters -->
            <form method="GET" action="index.php" class="filters">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($search); ?>">
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="Draft" <?php echo $status === 'Draft'     ? 'selected' : ''; ?>>Draft</option>
                    <option value="Published" <?php echo $status === 'Published' ? 'selected' : ''; ?>>Published</option>
                    <option value="Final" <?php echo $status === 'Final'     ? 'selected' : ''; ?>>Final</option>
                    <option value="Archived" <?php echo $status === 'Archived'  ? 'selected' : ''; ?>>Archived</option>
                </select>
                <select name="cat" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo $cat_id == (int)$c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($search || $status || $cat_id): ?>
                    <a href="index.php" style="font-size:.8rem;color:#ef4444;text-decoration:none;">Clear filters</a>
                <?php endif; ?>
            </form>

            <div style="background:#fff;border-radius:14px;box-shadow:0 1px 3px rgba(0,0,0,.06);overflow:hidden;">
                <?php if (empty($docs)): ?>
                    <div class="empty-msg">
                        <p>No documents found.
                            <?php if ($userRole !== 'Viewer'): ?>
                                <a href="upload.php" style="color:#3498db;">Upload your first document &rarr;</a>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <table class="lean-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Title</th>
                                <th class="col-hide-sm">Category</th>
                                <th class="col-hide-sm">Version</th>
                                <th class="col-hide-sm">Status</th>
                                <th class="col-hide-sm">Uploaded</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($docs as $doc):
                                $ext      = strtolower($doc['extension'] ?? 'default');
                                $color    = $typeColors[$ext] ?? '#64748b';
                                $icon     = $typeIcons[$ext] ?? 'fa-file';
                                $isOwner  = ($doc['uploaded_by'] == $userId);
                                $isAdmin  = ($userRole === 'Admin');
                                $isViewer = ($userRole === 'Viewer');
                                $canEdit  = $isAdmin || ($isOwner && in_array($userRole, ['Standard User']));
                                $canShare = $isAdmin || $isOwner;
                                $canDelete = $isAdmin || $isOwner;
                            ?>
                                <tr>
                                    <td class="file-icon-cell">
                                        <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>; font-size: 24px;"></i>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($doc['title']); ?></strong><br>
                                        <small style="color:#64748b;"><?php echo htmlspecialchars($doc['subject'] ?? ''); ?></small>
                                    </td>
                                    <td class="col-hide-sm">
                                        <span style="background:#f1f5f9;padding:3px 8px;border-radius:5px;font-size:.75rem;">
                                            <?php echo htmlspecialchars($doc['category_name'] ?? '—'); ?>
                                        </span>
                                    </td>
                                    <td class="col-hide-sm"><span class="version-badge">v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></span></td>
                                    <td class="col-hide-sm"><span class="status-pill <?php echo strtolower($doc['status'] ?? 'draft'); ?>"><?php echo htmlspecialchars($doc['status'] ?? 'Draft'); ?></span></td>
                                    <td class="col-hide-sm" style="font-size:.78rem;color:#64748b;"><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></td>
                                    <td>
                                        <div class="action-group" style="justify-content:flex-end;">
                                            <a href="view.php?id=<?php echo $doc['id']; ?>" title="View Document"><?php echo icon('eye', ''); ?></a>
                                            <a href="../../download.php?id=<?php echo $doc['id']; ?>" class="dl-btn" title="Download"><?php echo icon('download', ''); ?></a>
                                            <?php if ($canShare && !$isViewer): ?>
                                                <a href="share.php?id=<?php echo $doc['id']; ?>" title="Share"><?php echo icon('shared', ''); ?></a>
                                            <?php endif; ?>
                                            <?php if ($canEdit): ?>
                                                <a href="edit.php?id=<?php echo $doc['id']; ?>" title="Edit Metadata"><?php echo icon('edit', ''); ?></a>
                                            <?php endif; ?>
                                            <?php if ($canDelete && !$isViewer): ?>
                                                <form method="POST" action="delete.php" style="display:inline;" onsubmit="return confirmDelete('<?php echo addslashes(htmlspecialchars($doc['title'])); ?>')">
                                                    <input type="hidden" name="doc_id" value="<?php echo $doc['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                    <button type="submit" title="Delete Document" style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;border-radius:6px;background:#fee2e2;border:1px solid #fca5a5;color:#dc2626;cursor:pointer;font-size:1rem;">&#x1F5D1;</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../../assets/js/documents.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>