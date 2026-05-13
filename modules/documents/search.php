<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Collect all search parameters
$q            = isset($_GET['q'])            ? trim($_GET['q'])            : '';
$content_only = isset($_GET['content_only']) ? trim($_GET['content_only']) : '';
$author       = isset($_GET['author'])       ? trim($_GET['author'])       : '';
$status       = isset($_GET['status'])       ? $_GET['status']             : '';
$ext          = isset($_GET['ext'])          ? strtolower(trim($_GET['ext'])) : '';
$cat_id       = isset($_GET['cat'])          ? (int)$_GET['cat']           : 0;
$date_from    = isset($_GET['date_from'])    ? $_GET['date_from']          : '';
$date_to      = isset($_GET['date_to'])      ? $_GET['date_to']            : '';
$uploader     = isset($_GET['uploader'])     ? trim($_GET['uploader'])     : '';
$size_min     = isset($_GET['size_min'])     ? (int)$_GET['size_min']      : 0;
$size_max     = isset($_GET['size_max'])     ? (int)$_GET['size_max']      : 0;

$docs     = [];
$searched = !empty($_GET);

if ($searched) {
    $where  = [];
    $params = [];
    $types  = '';

    // Non-admins see only their own docs and docs shared with them
    if ($userRole !== 'Admin') {
        $where[]  = "(d.uploaded_by = ? OR EXISTS (
                        SELECT 1 FROM document_shares ds
                        WHERE ds.document_id = d.id AND ds.shared_with_user_id = ?
                     ))";
        $params[] = $userId;
        $params[] = $userId;
        $types   .= 'ii';
    }


    // DEDICATED CONTENT-ONLY SEARCH (INSIDE DOCUMENTS)
    if ($content_only !== '') {
        $likeContent = '%' . $conn->real_escape_string($content_only) . '%';
        $where[] = "d.extracted_text LIKE ?";
        $params[] = $likeContent;
        $types .= 's';
    }

    // GENERAL KEYWORD SEARCH (Title, Description, Subject, Author, AND Content)
    if ($q !== '') {
        $like = '%' . $conn->real_escape_string($q) . '%';
        $where[] = "(d.title LIKE ? OR d.description LIKE ? OR d.subject LIKE ? OR d.author LIKE ? OR d.extracted_text LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sssss';
    }
    // ==================================================

    if ($author !== '') {
        $where[]  = "d.author LIKE ?";
        $params[] = '%' . $author . '%';
        $types   .= 's';
    }
    if ($uploader !== '') {
        $where[]  = "u.fullname LIKE ?";
        $params[] = '%' . $uploader . '%';
        $types   .= 's';
    }
    if ($status !== '') {
        $where[]  = "d.status = ?";
        $params[] = $status;
        $types   .= 's';
    }
    if ($ext !== '') {
        $where[]  = "d.extension = ?";
        $params[] = $ext;
        $types   .= 's';
    }
    if ($cat_id > 0) {
        $where[]  = "d.category_id = ?";
        $params[] = $cat_id;
        $types   .= 'i';
    }
    if ($date_from !== '') {
        $where[]  = "DATE(d.upload_date) >= ?";
        $params[] = $date_from;
        $types   .= 's';
    }
    if ($date_to !== '') {
        $where[]  = "DATE(d.upload_date) <= ?";
        $params[] = $date_to;
        $types   .= 's';
    }
    if ($size_min > 0) {
        $where[]  = "d.size >= ?";
        $params[] = $size_min * 1024;
        $types   .= 'i';
    }
    if ($size_max > 0) {
        $where[]  = "d.size <= ?";
        $params[] = $size_max * 1024;
        $types   .= 'i';
    }

    $sql = "SELECT d.*, c.name AS category_name, u.fullname AS uploader_name
            FROM documents d
            LEFT JOIN categories c ON d.category_id = c.id
            LEFT JOIN users u ON d.uploaded_by = u.id";
    if ($where) $sql .= " WHERE " . implode(" AND ", $where);
    $sql .= " ORDER BY d.upload_date DESC";

    $stmt = $conn->prepare($sql);
    if ($params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $docs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$typeColors = [
    'pdf'  => '#dc2626',
    'docx' => '#0284c7',
    'doc'  => '#0284c7',
    'jpg'  => '#7c3aed',
    'jpeg' => '#7c3aed',
    'png'  => '#7c3aed',
    'zip'  => '#d97706',
    'xlsx' => '#16a34a',
    'pptx' => '#ea580c',
    'txt'  => '#475569',
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
    <title>Advanced Search | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <style>
        .search-page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .search-breadcrumb {
            font-size: 0.7rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .search-page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .search-page-header p {
            color: #64748b;
            font-size: 0.8rem;
        }

        .btn-back {
            padding: 0.5rem 1rem;
            background: #f1f5f9;
            color: #475569;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .search-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .search-form-grid {
            display: grid;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-form-grid.cols-2 {
            grid-template-columns: 1fr 1fr;
        }

        .search-form-grid.cols-3 {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .search-form-grid.cols-4 {
            grid-template-columns: 1fr 1fr 1fr 1fr;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .search-field label {
            display: block;
            font-size: 0.7rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.25rem;
        }

        .search-field input,
        .search-field select {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.85rem;
            font-family: inherit;
        }

        .search-field input:focus,
        .search-field select:focus {
            outline: none;
            border-color: #3498db;
        }

        .field-hint {
            font-size: 0.65rem;
            color: #94a3b8;
            display: block;
            margin-top: 0.25rem;
        }

        .content-search-highlight {
            background: #dbe8f3;
            border-left: 4px solid #0b69f5;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .content-search-highlight label {
            color: #071870;
            font-weight: 700;
        }

        .search-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-search {
            padding: 0.6rem 1.5rem;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-clear {
            padding: 0.6rem 1.5rem;
            background: #f1f5f9;
            color: #475569;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .result-count {
            padding: 0.75rem 1rem;
            background: #f8fafc;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            color: #475569;
        }

        .results-table-wrap {
            background: white;
            border-radius: 16px;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .lean-table {
            width: 100%;
            border-collapse: collapse;
        }

        .lean-table th {
            text-align: left;
            padding: 0.8rem 1rem;
            background: #f8fafc;
            font-size: 0.7rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
        }

        .lean-table td {
            padding: 0.8rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.85rem;
        }

        .lean-table tr:hover {
            background: #f8fafc;
        }

        .file-icon-cell {
            width: 50px;
        }

        .version-badge {
            background: #f0f9ff;
            color: #0369a1;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-pill {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-pill.draft {
            background: #fef3c7;
            color: #92400e;
        }

        .status-pill.published {
            background: #dcfce7;
            color: #166534;
        }

        .status-pill.final {
            background: #dcfce7;
            color: #166534;
        }

        .cat-badge {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
        }

        .action-group {
            display: flex;
            gap: 6px;
            justify-content: flex-end;
        }

        .action-group a {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            background: #f1f5f9;
            text-decoration: none;
            color: #64748b;
        }

        .action-group a:hover {
            background: #e2e8f0;
        }

        .dl-btn {
            background: #f0fdf4 !important;
            color: #16a34a !important;
        }

        .match-highlight {
            background-color: #fef08a;
            padding: 0 2px;
            border-radius: 3px;
        }

        @media (max-width: 768px) {

            .search-form-grid.cols-2,
            .search-form-grid.cols-3,
            .search-form-grid.cols-4 {
                grid-template-columns: 1fr;
            }
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
                        <h3>The Academic Vault</h3>
                        <span>ISJ-DMS GLOBAL</span>
                    </div>
                </div>
            </div>
            <nav class="sidebar-nav">
                <a href="../../dashboard.php" class="nav-item"><?php echo icon('dashboard', 'nav-icon'); ?> Dashboard</a>
                <a href="index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
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

            <div class="search-page-header">
                <div>
                    <div class="search-breadcrumb">Advanced Search</div>
                    <h1>Search the Vault</h1>
                    <p>Filter documents by any combination of criteria below.</p>
                </div>
                <a href="index.php" class="btn-back">← Back to Documents</a>
            </div>

            <!-- Search Form -->
            <div class="search-card">
                <form method="GET" action="search.php" id="searchForm">

                    <!-- DEDICATED CONTENT-ONLY SEARCH FIELD (INSIDE THE FORM!) -->
                    <div class="search-form-grid">
                        <div class="search-field full-width content-search-highlight">
                            <label for="content_only">
                                <i class="fas fa-file-alt"></i>Search INSIDE Documents 
                            </label>
                            <input type="text" id="content_only" name="content_only"
                                value="<?php echo htmlspecialchars($content_only); ?>"
                                placeholder="Type any word or phrase that appears INSIDE PDF, Word, or image files..."
                                style="border-color: #6fc0f3; background: #fffbeb;">
                            <span class="field-hint"><i class="fas fa-search"></i> This searches ONLY within document content, not titles or descriptions</span>
                        </div>
                    </div>

                    <div class="search-form-grid">
                        <div class="search-field full-width">
                            <label for="q">General Keywords</label>
                            <input type="text" id="q" name="q"
                                value="<?php echo htmlspecialchars($q); ?>"
                                placeholder="Search title, description, subject, author, or INSIDE documents...">
                            <span class="field-hint">Searches across title, subject, description, author, AND document content</span>
                        </div>
                    </div>

                    <div class="search-form-grid cols-2">
                        <div class="search-field">
                            <label for="author">Document Author</label>
                            <input type="text" id="author" name="author"
                                value="<?php echo htmlspecialchars($author); ?>"
                                placeholder="Author's name">
                        </div>
                        <?php if ($userRole === 'Admin'): ?>
                            <div class="search-field">
                                <label for="uploader">Uploaded By</label>
                                <input type="text" id="uploader" name="uploader"
                                    value="<?php echo htmlspecialchars($uploader); ?>"
                                    placeholder="Uploader's full name">
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="search-form-grid cols-3">
                        <div class="search-field">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Any Status</option>
                                <option value="Draft" <?php echo $status === 'Draft'     ? 'selected' : ''; ?>>Draft</option>
                                <option value="Published" <?php echo $status === 'Published' ? 'selected' : ''; ?>>Published</option>
                                <option value="Final" <?php echo $status === 'Final'     ? 'selected' : ''; ?>>Final</option>
                                <option value="Archived" <?php echo $status === 'Archived'  ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="ext">File Type</label>
                            <select id="ext" name="ext">
                                <option value="">All File Types</option>
                                <option value="pdf" <?php echo $ext === 'pdf'  ? 'selected' : ''; ?>>PDF</option>
                                <option value="docx" <?php echo $ext === 'docx' ? 'selected' : ''; ?>>Word (DOCX)</option>
                                <option value="doc" <?php echo $ext === 'doc'  ? 'selected' : ''; ?>>Word (DOC)</option>
                                <option value="xlsx" <?php echo $ext === 'xlsx' ? 'selected' : ''; ?>>Excel (XLSX)</option>
                                <option value="pptx" <?php echo $ext === 'pptx' ? 'selected' : ''; ?>>PowerPoint (PPTX)</option>
                                <option value="jpg" <?php echo $ext === 'jpg'  ? 'selected' : ''; ?>>Image (JPG)</option>
                                <option value="png" <?php echo $ext === 'png'  ? 'selected' : ''; ?>>Image (PNG)</option>
                                <option value="zip" <?php echo $ext === 'zip'  ? 'selected' : ''; ?>>Archive (ZIP)</option>
                                <option value="txt" <?php echo $ext === 'txt'  ? 'selected' : ''; ?>>Text (TXT)</option>
                            </select>
                        </div>
                        <div class="search-field">
                            <label for="cat">Category</label>
                            <select id="cat" name="cat">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo $cat_id == $c['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="search-form-grid cols-4">
                        <div class="search-field">
                            <label for="date_from">Upload Date From</label>
                            <input type="date" id="date_from" name="date_from"
                                value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="search-field">
                            <label for="date_to">Upload Date To</label>
                            <input type="date" id="date_to" name="date_to"
                                value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="search-field">
                            <label for="size_min">Min File Size (KB)</label>
                            <input type="number" id="size_min" name="size_min" min="0"
                                value="<?php echo $size_min > 0 ? $size_min : ''; ?>"
                                placeholder="e.g. 100">
                        </div>
                        <div class="search-field">
                            <label for="size_max">Max File Size (KB)</label>
                            <input type="number" id="size_max" name="size_max" min="0"
                                value="<?php echo $size_max > 0 ? $size_max : ''; ?>"
                                placeholder="e.g. 5000">
                        </div>
                    </div>

                    <div class="search-actions">
                        <button type="submit" class="btn-search">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <a href="search.php" class="btn-clear">
                            <i class="fas fa-times"></i> Clear All Filters
                        </a>
                        <a href="index.php" class="btn-clear">
                            <i class="fas fa-arrow-left"></i> Back to Documents
                        </a>
                    </div>
                </form>
            </div>

            <!-- Results -->
            <?php if ($searched): ?>
                <div class="result-count">
                    <?php if (empty($docs)): ?>
                        <i class="fas fa-info-circle"></i> No documents match your search criteria.
                    <?php else: ?>
                        <i class="fas fa-check-circle" style="color: #16a34a;"></i>
                        <?php echo count($docs); ?> result<?php echo count($docs) !== 1 ? 's' : ''; ?> found
                        <?php if ($content_only !== ''): ?>
                            <span style="background: #fef3c7; padding: 2px 8px; border-radius: 12px; margin-left: 10px;">
                                🔍 Searching INSIDE document content for: "<?php echo htmlspecialchars($content_only); ?>"
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <?php if (!empty($docs)): ?>
                    <div class="results-table-wrap">
                        <table class="lean-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Title / Subject</th>
                                    <th class="col-hide-sm">Author</th>
                                    <th class="col-hide-sm">Category</th>
                                    <th class="col-hide-sm">Size</th>
                                    <th class="col-hide-sm">Version</th>
                                    <th class="col-hide-sm">Status</th>
                                    <th class="col-hide-sm">Date</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($docs as $doc):
                                    $docExt   = strtolower($doc['extension'] ?? 'default');
                                    $color    = $typeColors[$docExt] ?? '#64748b';
                                    $icon     = $typeIcons[$docExt] ?? 'fa-file';
                                    $isOwner  = ($doc['uploaded_by'] == $userId);
                                    $isAdmin  = ($userRole === 'Admin');
                                    $isViewer = ($userRole === 'Viewer');
                                    $canEdit  = $isAdmin || ($isOwner && $userRole === 'Standard User');
                                    $canShare = ($isAdmin || $isOwner) && !$isViewer;
                                    $canDelete = ($isAdmin || $isOwner) && !$isViewer;
                                    $sizeKB   = round(($doc['size'] ?? 0) / 1024, 1);

                                    // Highlight search term in title if content search is active
                                    $displayTitle = htmlspecialchars($doc['title']);
                                    if ($content_only !== '') {
                                        $displayTitle = preg_replace('/(' . preg_quote($content_only, '/') . ')/i', '<span class="match-highlight">$1</span>', $displayTitle);
                                    }
                                ?>
                                    <tr>
                                        <td class="file-icon-cell">
                                            <i class="fas <?php echo $icon; ?>" style="color: <?php echo $color; ?>; font-size: 24px;"></i>
                                        </td>
                                        <td>
                                            <strong><?php echo $displayTitle; ?></strong><br>
                                            <small style="color:#64748b;"><?php echo htmlspecialchars($doc['subject'] ?? ''); ?></small>
                                        </td>
                                        <td class="col-hide-sm" style="font-size:.83rem;color:#475569;"><?php echo htmlspecialchars($doc['author'] ?? '—'); ?></td>
                                        <td class="col-hide-sm">
                                            <span class="cat-badge"><?php echo htmlspecialchars($doc['category_name'] ?? '—'); ?></span>
                                        </td>
                                        <td class="col-hide-sm" style="font-size:.78rem;color:#64748b;"><?php echo $sizeKB; ?> KB</td>
                                        <td class="col-hide-sm"><span class="version-badge">v<?php echo htmlspecialchars($doc['current_version'] ?? '1'); ?></span></td>
                                        <td class="col-hide-sm">
                                            <span class="status-pill <?php echo strtolower($doc['status'] ?? 'draft'); ?>">
                                                <?php echo htmlspecialchars($doc['status'] ?? 'Draft'); ?>
                                            </span>
                                        </td>
                                        <td class="col-hide-sm" style="font-size:.78rem;color:#64748b;"><?php echo date('d/m/Y', strtotime($doc['upload_date'])); ?></td>
                                        <td>
                                            <div class="action-group" style="justify-content:flex-end;">
                                                <a href="view.php?id=<?php echo $doc['id']; ?>" title="View"><i class="fas fa-eye"></i></a>
                                                <a href="../../download.php?id=<?php echo $doc['id']; ?>" class="dl-btn" title="Download"><i class="fas fa-download"></i></a>
                                                <?php if ($canShare): ?>
                                                    <a href="share.php?id=<?php echo $doc['id']; ?>" title="Share"><i class="fas fa-share-alt"></i></a>
                                                <?php endif; ?>
                                                <?php if ($canEdit): ?>
                                                    <a href="edit.php?id=<?php echo $doc['id']; ?>" title="Edit"><i class="fas fa-edit"></i></a>
                                                <?php endif; ?>
                                                <?php if ($canDelete): ?>
                                                    <a href="javascript:void(0);"
                                                        onclick="confirmDelete(<?php echo $doc['id']; ?>, '<?php echo addslashes(htmlspecialchars($doc['title'])); ?>')"
                                                        title="Delete"><i class="fas fa-trash"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </main>
    </div>

    <script>
        function confirmDelete(id, title) {
            if (confirm('Are you sure you want to delete "' + title + '"? This action cannot be undone.')) {
                window.location.href = 'delete.php?id=' + id;
            }
        }
    </script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>