<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Block viewers and unauthenticated users
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
if ($_SESSION['user_role'] === 'Viewer') {
    header("Location: ../../dashboard.php?alert=error&msg=Viewers+are+not+permitted+to+upload+documents.");
    exit();
}

// Get preselected folder ID from URL (used when coming from Folder Browser)
$preselectedFolderId = isset($_GET['folder_id']) ? (int)$_GET['folder_id'] : null;
if ($preselectedFolderId) {
    // Verify folder exists
    $check = $conn->prepare("SELECT id FROM categories WHERE id = ?");
    $check->bind_param("i", $preselectedFolderId);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows === 0) {
        $preselectedFolderId = null;
    }
}

// Recursive function to build nested category dropdown with indentation
function renderFolderOptions($conn, $parentId = null, $indent = '', $preselectedId = null)
{
    $sql = "SELECT id, name FROM categories WHERE parent_id " . ($parentId ? "= ?" : "IS NULL") . " ORDER BY name ASC";
    $stmt = $conn->prepare($sql);
    if ($parentId) {
        $stmt->bind_param("i", $parentId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $selected = ($preselectedId == $row['id']) ? 'selected' : '';
        echo '<option value="' . $row['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($row['name']) . '</option>';
        renderFolderOptions($conn, $row['id'], $indent . '&nbsp;&nbsp;&nbsp;', $preselectedId);
    }
    $stmt->close();
}

// Check for alert messages from process-upload.php
$alert = isset($_GET['alert']) ? $_GET['alert'] : '';
$msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Document | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <style>
        .upload-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .upload-form-card,
        .upload-preview-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .required {
            color: #e74c3c;
        }

        .form-control {
            width: 100%;
            padding: 0.6rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.875rem;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            resize: vertical;
        }

        .btn-submit {
            width: 100%;
            padding: 0.75rem;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 1rem;
        }

        .btn-submit:hover {
            background: #1e293b;
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fafc;
        }

        .drop-zone:hover {
            border-color: #3498db;
            background: #eff6ff;
        }

        .drop-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .drop-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .drop-sub {
            font-size: 0.8rem;
            color: #64748b;
        }

        .drop-link {
            color: #3498db;
            cursor: pointer;
            text-decoration: underline;
        }

        .drop-formats,
        .drop-size {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.5rem;
        }

        .allowed-types {
            margin-top: 1rem;
        }

        .allowed-types strong {
            font-size: 0.7rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #475569;
        }

        .type-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .type-pill {
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
        }

        .type-pill.pdf {
            background: #fee2e2;
            color: #dc2626;
        }

        .type-pill.word {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .type-pill.image {
            background: #ede9fe;
            color: #7c3aed;
        }

        .type-pill.archive {
            background: #fef3c7;
            color: #b45309;
        }

        .type-pill.excel {
            background: #dcfce7;
            color: #16a34a;
        }

        .type-pill.ppt {
            background: #ffedd5;
            color: #ea580c;
        }

        .type-pill.text {
            background: #f1f5f9;
            color: #475569;
        }

        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .file-info-box {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.8rem;
        }

        .btn-change-file {
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #e2e8f0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
        }

        .breadcrumb {
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .page-header {
            margin-bottom: 1.5rem;
        }

        .page-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
        }

        .page-subtitle {
            color: #64748b;
            font-size: 0.875rem;
        }

        @media (max-width: 768px) {
            .upload-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
</head>

<body class="dashboard-body">
    <div class="dashboard-wrapper">

        <!-- Sidebar -->
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
                <?php if ($_SESSION['user_role'] === 'Admin'): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="../users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <a href="upload.php" class="btn-upload active-upload">
                    <?php echo icon('upload', ''); ?> Upload Document
                </a>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb">
                        <a href="index.php">Documents</a> / Upload
                    </div>
                    <h1>Upload New Document</h1>
                    <p class="page-subtitle">Fill in the document details and attach your file below.</p>
                </div>
            </div>

            <?php if ($alert && $msg): ?>
                <div class="alert alert-<?php echo $alert; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <div class="upload-layout">

                <!-- Left: Form -->
                <div class="upload-form-card">
                    <form id="uploadForm" action="process-upload.php" method="POST" enctype="multipart/form-data" novalidate>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Document Title <span class="required">*</span></label>
                                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Annual Report 2025" required>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject / Theme</label>
                                <input type="text" id="subject" name="subject" class="form-control" placeholder="e.g. Administration">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="author">Original Author</label>
                                <input type="text" id="author" name="author" class="form-control" placeholder="Author's full name">
                            </div>
                            <div class="form-group">
                                <label for="category_id">Folder (Category) <span class="required">*</span></label>
                                <select id="category_id" name="category_id" class="form-control" required>
                                    <option value="">— Select a folder —</option>
                                    <?php renderFolderOptions($conn, null, '', $preselectedFolderId); ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="current_version">Starting Version</label>
                                <input type="number" id="current_version" name="current_version" class="form-control" value="1" min="1">
                            </div>
                            <div class="form-group">
                                <label for="status">Visibility Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="Draft">Draft</option>
                                    <option value="Published">Published</option>
                                    <option value="Final">Final (Archived)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Content Description</label>
                            <textarea id="description" name="description" class="form-control" rows="4" placeholder="Brief summary of the document content..."></textarea>
                        </div>

                        <!-- Hidden file input -->
                        <input type="file" name="document" id="fileInput"
                            accept=".pdf,.docx,.doc,.jpg,.jpeg,.png,.zip,.xlsx,.pptx,.txt"
                            style="display:none;" required>

                        <button type="submit" class="btn-submit" id="submitBtn" disabled>
                            <?php echo icon('save', 'btn-icon'); ?>
                            <span>Submit Document</span>
                        </button>
                    </form>
                </div>

                <!-- Right: Drop Zone & Preview -->
                <div class="upload-preview-card">
                    <div class="drop-zone" id="dropZone">
                        <div id="dropZonePlaceholder">
                            <div class="drop-icon">📁</div>
                            <p class="drop-title">Drag &amp; drop your file here</p>
                            <p class="drop-sub">or <span class="drop-link" id="browseBtn">browse to select</span></p>
                            <p class="drop-formats">Accepted: PDF, DOCX, DOC, JPG, PNG, ZIP, XLSX, PPTX, TXT</p>
                            <p class="drop-size">Maximum file size: 20 MB</p>
                        </div>
                        <div id="dropZonePreview" style="display:none;">
                            <img id="imagePreview" src="#" alt="Preview" style="max-width:100%;max-height:220px;border-radius:8px;display:none;">
                            <div id="fileIconPreview" style="font-size:3.5rem;"></div>
                            <div class="file-info-box" id="fileInfoBox"></div>
                            <button type="button" class="btn-change-file" id="changeFileBtn">Change File</button>
                        </div>
                    </div>
                    <div class="allowed-types">
                        <strong>Allowed File Types</strong>
                        <div class="type-pills">
                            <span class="type-pill pdf">PDF</span>
                            <span class="type-pill word">DOCX</span>
                            <span class="type-pill word">DOC</span>
                            <span class="type-pill image">JPG</span>
                            <span class="type-pill image">PNG</span>
                            <span class="type-pill archive">ZIP</span>
                            <span class="type-pill excel">XLSX</span>
                            <span class="type-pill ppt">PPTX</span>
                            <span class="type-pill text">TXT</span>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const browseBtn = document.getElementById('browseBtn');
        const dropZonePlaceholder = document.getElementById('dropZonePlaceholder');
        const dropZonePreview = document.getElementById('dropZonePreview');
        const imagePreview = document.getElementById('imagePreview');
        const fileIconPreview = document.getElementById('fileIconPreview');
        const fileInfoBox = document.getElementById('fileInfoBox');
        const changeFileBtn = document.getElementById('changeFileBtn');
        const submitBtn = document.getElementById('submitBtn');

        browseBtn.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#3498db';
            dropZone.style.background = '#eff6ff';
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.style.borderColor = '#cbd5e1';
            dropZone.style.background = '#f8fafc';
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#cbd5e1';
            dropZone.style.background = '#f8fafc';
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFilePreview(files[0]);
            }
        });

        fileInput.addEventListener('change', () => {
            if (fileInput.files.length > 0) {
                handleFilePreview(fileInput.files[0]);
            }
        });

        function handleFilePreview(file) {
            const fileName = file.name;
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileExt = fileName.split('.').pop().toLowerCase();

            dropZonePlaceholder.style.display = 'none';
            dropZonePreview.style.display = 'block';
            imagePreview.style.display = 'none';
            fileIconPreview.style.display = 'block';

            if (['jpg', 'jpeg', 'png'].includes(fileExt)) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                    fileIconPreview.style.display = 'none';
                };
                reader.readAsDataURL(file);
            } else {
                const iconMap = {
                    'pdf': '📄',
                    'doc': '📝',
                    'docx': '📝',
                    'xlsx': '📊',
                    'pptx': '📽️',
                    'zip': '📦',
                    'txt': '📃'
                };
                fileIconPreview.innerHTML = iconMap[fileExt] || '📎';
            }

            fileInfoBox.innerHTML = `
                <strong>${fileName}</strong><br>
                Size: ${fileSize} MB<br>
                Type: ${fileExt.toUpperCase()}
            `;

            submitBtn.disabled = false;
        }

        changeFileBtn.addEventListener('click', () => {
            fileInput.value = '';
            dropZonePlaceholder.style.display = 'block';
            dropZonePreview.style.display = 'none';
            submitBtn.disabled = true;
        });

        // Prevent double-submit: disable the button immediately on form submit
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            // Client-side validation: ensure a file is actually selected
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file before submitting.');
                return;
            }
            // Disable button and update label to prevent re-clicks during upload
            submitBtn.disabled = true;
            submitBtn.querySelector('span').textContent = 'Uploading…';
        });
    </script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>