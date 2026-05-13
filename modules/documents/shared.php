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

// Fetch docs shared with this user
$q    = isset($_GET['q'])    ? trim($_GET['q'])    : '';
$perm = isset($_GET['perm']) ? $_GET['perm']       : '';

$sql = "SELECT d.*, c.name as category_name, u.fullname as owner_name, ds.permission, ds.shared_at
        FROM document_shares ds
        JOIN documents d ON ds.document_id = d.id
        LEFT JOIN categories c ON d.category_id = c.id
        LEFT JOIN users u ON d.uploaded_by = u.id
        WHERE ds.shared_with_user_id = ?";
$params = [$userId];
$types  = 'i';

if ($q !== '') {
    $like = '%' . $conn->real_escape_string($q) . '%';
    $sql .= " AND (d.title LIKE ? OR d.subject LIKE ? OR u.fullname LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}
if ($perm !== '') {
    $sql .= " AND ds.permission = ?";
    $params[] = $perm;
    $types .= 's';
}
$sql .= " ORDER BY ds.shared_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$typeColors = [
    'pdf'  => '#dc2626',
    'docx' => '#0284c7',
    'doc'  => '#0284c7',
    'jpg'  => '#7c3aed',
    'jpeg' => '#7c3aed',
    'png'  => '#7c3aed',
    'zip'  => '#d97706'
];
$typeIcons = [
    'pdf'     => 'fa-file-pdf',
    'doc'     => 'fa-file-word',
    'docx'    => 'fa-file-word',
    'xls'     => 'fa-file-excel',
    'xlsx'    => 'fa-file-excel',
    'ppt'     => 'fa-file-powerpoint',
    'pptx'    => 'fa-file-powerpoint',
    'jpg'     => 'fa-file-image',
    'jpeg'    => 'fa-file-image',
    'png'     => 'fa-file-image',
    'gif'     => 'fa-file-image',
    'zip'     => 'fa-file-archive',
    'rar'     => 'fa-file-archive',
    'txt'     => 'fa-file-alt',
    'default' => 'fa-file'
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared with Me | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/shared.css">
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">

        <!-- Mobile sidebar toggle -->
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- Sidebar -->
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
                <a href="../../dashboard.php" class="nav-item">
                    <?php echo icon('dashboard', 'nav-icon'); ?> Dashboard
                </a>
                <a href="index.php" class="nav-item">
                    <?php echo icon('documents', 'nav-icon'); ?> My Documents
                </a>
                <a href="shared.php" class="nav-item active">
                    <?php echo icon('shared', 'nav-icon'); ?> Shared with Me
                </a>
                <a href="../../tags/index.php" class="nav-item">
                    <?php echo icon('tags', 'nav-icon'); ?> Tags
                </a>
                <a href="../../modules/folder-browser/index.php" class="nav-item">
                    <?php echo icon('folder', 'nav-icon'); ?> Folder Browser
                </a>
                <div class="nav-divider"></div>
                <?php if ($userRole === 'Admin'): ?>
                    <a href="../../usermanagement.php" class="nav-item">
                        <?php echo icon('users', 'nav-icon'); ?> User Management
                    </a>
                <?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="upload.php" class="btn-upload">
                    <?php echo icon('upload', ''); ?> Upload Document
                </a>
                <a href="../../logout.php" class="logout-link">
                    <?php echo icon('logout', ''); ?> Logout
                </a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">

            <!-- Page header -->
            <div class="shared-page-header">
                <h1>Shared with Me</h1>
                <p>
                    <?php echo count($docs); ?>
                    document<?php echo count($docs) !== 1 ? 's' : ''; ?> shared with you
                </p>
            </div>

            <!-- Search + filter toolbar -->
            <form method="GET" action="shared.php">
                <div class="toolbar">
                    <div class="search-row">
                        <input type="text" name="q"
                            placeholder="Search shared documents..."
                            value="<?php echo htmlspecialchars($q); ?>">
                        <button type="submit">Search</button>
                    </div>
                    <div class="filter-row">
                        <select name="perm" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Permissions</option>
                            <option value="view" <?php echo $perm === 'view'     ? 'selected' : ''; ?>>View Only</option>
                            <option value="download" <?php echo $perm === 'download' ? 'selected' : ''; ?>>Download</option>
                            <option value="edit" <?php echo $perm === 'edit'     ? 'selected' : ''; ?>>Edit</option>
                        </select>
                        <?php if ($q || $perm): ?>
                            <a href="shared.php" style="font-size:.8rem;color:#ef4444;text-decoration:none;white-space:nowrap;">
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Documents table -->
            <div class="shared-table-card">
                <?php if (empty($docs)): ?>
                    <div class="shared-empty">
                        <p>No documents have been shared with you yet.</p>
                    </div>
                <?php else: ?>
                    <table class="lean-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Document</th>
                                <th class="col-hide-sm">Shared By</th>
                                <th class="col-hide-sm">Permission</th>
                                <th class="col-hide-sm">Shared On</th>
                                <th style="text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($docs as $doc):
                                $ext   = strtolower($doc['extension'] ?? 'default');
                                $color = $typeColors[$ext] ?? '#64748b';
                                $icon  = $typeIcons[$ext]  ?? 'fa-file';
                                $perm2 = $doc['permission'] ?? 'view';
                            ?>
                                <tr>
                                    <!-- Type icon -->
                                    <td class="file-icon-cell">
                                        <i class="fas <?php echo $icon; ?>"
                                            style="color:<?php echo $color; ?>;font-size:22px;"></i>
                                    </td>

                                    <!-- Title + subject -->
                                    <td>
                                        <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                        <?php if (!empty($doc['subject'])): ?>
                                            <small><?php echo htmlspecialchars($doc['subject']); ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Shared By (hidden on mobile) -->
                                    <td class="col-hide-sm" style="font-size:.85rem;">
                                        <?php echo htmlspecialchars($doc['owner_name'] ?? '—'); ?>
                                    </td>

                                    <!-- Permission (hidden on mobile) -->
                                    <td class="col-hide-sm">
                                        <span class="perm-badge perm-<?php echo $perm2; ?>">
                                            <?php echo ucfirst($perm2); ?>
                                        </span>
                                    </td>

                                    <!-- Shared On (hidden on mobile) -->
                                    <td class="col-hide-sm" style="font-size:.78rem;color:#64748b;">
                                        <?php echo date('d/m/Y', strtotime($doc['shared_at'] ?? 'now')); ?>
                                    </td>

                                    <!-- Actions -->
                                    <td>
                                        <div class="action-group">
                                            <a href="view.php?id=<?php echo $doc['id']; ?>" title="View">
                                                <?php echo icon('eye', ''); ?>
                                            </a>
                                            <?php if (in_array($perm2, ['download', 'edit'])): ?>
                                                <a href="../../download.php?id=<?php echo $doc['id']; ?>"
                                                    class="dl-btn" title="Download">
                                                    <?php echo icon('download', ''); ?>
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($perm2 === 'edit'): ?>
                                                <a href="edit.php?id=<?php echo $doc['id']; ?>" title="Edit">
                                                    <?php echo icon('edit', ''); ?>
                                                </a>
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

    <script src="../../assets/js/main.js"></script>
</body>

</html>