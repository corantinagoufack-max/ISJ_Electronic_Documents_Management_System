<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location:login.php");
    exit();
}

// Role integrity check
$chk = $conn->prepare("SELECT role FROM users WHERE id = ?");
$chk->bind_param("i", $_SESSION['user_id']);
$chk->execute();
$row = $chk->get_result()->fetch_assoc();
if (!$row || $row['role'] !== $_SESSION['user_role']) {
    session_destroy();
    header("Location:login.php");
    exit();
}

// Recent docs
$recent_docs = $conn->query("
    SELECT d.*, c.name as category_name 
    FROM documents d 
    LEFT JOIN categories c ON d.category_id = c.id 
    ORDER BY d.upload_date DESC 
    LIMIT 7
");

// Stats
$total_users    = $conn->query("SELECT COUNT(*) as t FROM users")->fetch_assoc()['t'] ?? 0;
$active_users   = $conn->query("SELECT COUNT(*) as t FROM users WHERE is_verified=1")->fetch_assoc()['t'] ?? 0;
$total_docs     = $conn->query("SELECT COUNT(*) as t FROM documents")->fetch_assoc()['t'] ?? 0;

// Vault capacity
$used_bytes = $conn->query("SELECT COALESCE(SUM(size),0) as b FROM documents")->fetch_assoc()['b'] ?? 0;
$vault_gb   = 50;
$used_gb    = round($used_bytes / (1024 * 1024 * 1024), 3);
$used_mb    = round($used_bytes / (1024 * 1024), 1);
$pct        = $vault_gb > 0 ? min(100, ($used_gb / $vault_gb) * 100) : 0;

// Doc count by type (for mini breakdown)
$type_counts = [];
$tc = $conn->query("SELECT extension, COUNT(*) as n FROM documents GROUP BY extension");
if ($tc) while ($r = $tc->fetch_assoc()) $type_counts[$r['extension']] = $r['n'];

function icon($name, $class = "")
{
    // Map extension names to icon files
    $map = [
        'pdf' => 'file-pdf',
        'docx' => 'file-word',
        'doc' => 'file-word',
        'jpg' => 'file-image',
        'jpeg' => 'file-image',
        'png' => 'file-image',
        'zip' => 'file-archive',
        'rar' => 'file-archive',
        'default' => 'file-text'
    ];
    if (strpos($name, 'file-type-') === 0) {
        $ext = str_replace('file-type-', '', $name);
        $name = $map[$ext] ?? $map['default'];
    }
    $path = __DIR__ . "/assets/icons/{$name}.svg";
    if (!file_exists($path)) return "<span style='font-size:1.2em'>📄</span>";
    $svg = file_get_contents($path);
    $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
    if (!empty($class)) $svg = str_replace('<svg', "<svg class=\"{$class}\"", $svg);
    return $svg;
}

// Color map for file types used in CSS classes
$typeColors = [
    'pdf' => '#dc2626',
    'docx' => '#0284c7',
    'doc' => '#0284c7',
    'jpg' => '#7c3aed',
    'jpeg' => '#7c3aed',
    'png' => '#7c3aed',
    'zip' => '#d97706',
    'rar' => '#d97706',
    'default' => '#64748b'
];

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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="assets/css/6-components/alerts.css">
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="appSidebar">
            <div class="sidebar-brand">
                <div class="brand-header">
                    <img src="assets/images/logo.png" alt="ISJ" class="brand-logo-img">
                    <div class="brand-text">
                        <h3>The Academic Vault</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-item active"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="modules/documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="modules/documents/shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <a href="tags/index.php" class="nav-item"><?php echo icon('tags', 'nav-icon'); ?> Tags</a>
                <a href="modules/folder-browser/index.php" class="nav-item"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <a href="usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="modules/users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <a href="modules/documents/upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <a href="logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <section class="dashboard-view">
                <h1 style="font-size:1.6rem;font-weight:800;color:#0f172a;margin-bottom:1.5rem;">Institutional Dashboard</h1>

                <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                    <div class="alert alert-success" style="margin-bottom:1.5rem;">Document successfully secured in the vault.</div>
                <?php endif; ?>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-label">TOTAL DOCUMENTS</span>
                        <h2 class="stat-value"><?php echo number_format($total_docs); ?></h2>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">TOTAL USERS</span>
                        <h2 class="stat-value"><?php echo number_format($total_users); ?></h2>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">ACTIVE ACCOUNTS</span>
                        <h2 class="stat-value"><?php echo number_format($active_users); ?></h2>
                    </div>
                    <div class="stat-card quick-actions">
                        <h3>Quick Actions</h3>
                        <a href="modules/documents/upload.php" class="action-btn primary"><?php echo icon('upload', ''); ?> New Upload</a>
                    </div>
                </div>

                <div class="dashboard-content-area">
                    <div class="recent-docs-panel">
                        <div class="panel-header">
                            <h3>Recent Activity</h3>
                        </div>
                        <div class="activity-list">
                            <?php if ($recent_docs && $recent_docs->num_rows > 0): ?>
                                <table class="lean-table">
                                    <thead>
                                        <tr>
                                            <th>Type</th>
                                            <th>Title &amp; Subject</th>
                                            <th class="col-hide-sm">Author</th>
                                            <th class="col-hide-sm">Version</th>
                                            <th class="col-hide-sm">Status</th>
                                            <th class="col-hide-sm">Uploaded</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($doc = $recent_docs->fetch_assoc()):
                                            $ext  = strtolower($doc['extension'] ?? 'default');
                                            $color = $typeColors[$ext] ?? $typeColors['default'];
                                            $icon     = $typeIcons[$ext] ?? 'fa-file';
                                            $iconClass = 'file-' . (in_array($ext, ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'zip', 'rar']) ? $ext : 'default');
                                        ?>
                                            <tr>
                                                <td class="file-icon-cell">
                                                    <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>; font-size: 24px;"></i>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($doc['title']); ?></strong><br>
                                                    <small style="color:#64748b;"><?php echo htmlspecialchars($doc['subject'] ?? 'No Subject'); ?></small>
                                                </td>
                                                <td class="col-hide-sm"><?php echo htmlspecialchars($doc['author'] ?? 'Unknown'); ?></td>
                                                <td class="col-hide-sm"><span class="version-badge">v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></span></td>
                                                <td class="col-hide-sm"><span class="status-pill <?php echo strtolower($doc['status'] ?? 'draft'); ?>"><?php echo htmlspecialchars($doc['status'] ?? 'Draft'); ?></span></td>
                                                <td class="col-hide-sm" style="font-size:.8rem;color:#64748b;"><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></td>
                                                <td>
                                                    <div class="action-group">
                                                        <a href="modules/documents/view.php?id=<?php echo $doc['id']; ?>" title="View"><?php echo icon('eye', ''); ?></a>
                                                        <a href="download.php?id=<?php echo $doc['id']; ?>" title="Download"><?php echo icon('download', ''); ?></a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p class="empty-state" style="padding:2rem;text-align:center;color:#94a3b8;">No documents in the vault yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <aside class="side-panels">
                        <!-- Real-time vault capacity -->
                        <div class="capacity-card">
                            <h3>Vault Capacity</h3>
                            <div class="cap-header">
                                <span>Storage Used</span>
                                <span class="cap-pct"><?php echo round($pct, 1); ?>%</span>
                            </div>
                            <div class="cap-bar-wrap">
                                <div class="cap-bar <?php echo $pct >= 90 ? 'danger' : ($pct >= 70 ? 'warning' : ''); ?>" style="width:<?php echo max(1, $pct); ?>%;"></div>
                            </div>
                            <span class="cap-note"><?php echo $used_mb; ?> MB used of <?php echo $vault_gb; ?> GB</span>

                            <?php if (!empty($type_counts)): ?>
                                <div class="cap-types">
                                    <?php
                                    $dotColors = [
                                        'pdf' => '#dc2626',
                                        'docx' => '#0284c7',
                                        'doc' => '#0284c7',
                                        'jpg' => '#7c3aed',
                                        'png' => '#7c3aed',
                                        'zip' => '#d97706'
                                    ];
                                    foreach ($type_counts as $ext => $n):
                                        $dc = $dotColors[$ext] ?? '#64748b';
                                    ?>
                                        <div class="cap-type-row">
                                            <span class="cap-type-dot" style="background:<?php echo $dc; ?>;"></span>
                                            <span class="cap-type-name"><?php echo strtoupper($ext); ?> files</span>
                                            <span class="cap-type-count"><?php echo $n; ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </aside>
                </div>
            </section>
        </main>
    </div>
<script src="assets/js/main.js"></script>
</body>

</html>