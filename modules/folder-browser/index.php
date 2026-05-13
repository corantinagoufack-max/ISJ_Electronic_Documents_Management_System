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
$isViewer = ($userRole === 'Viewer');
$isAdmin  = ($userRole === 'Admin');

// Preload SVG strings for JS (so dynamic icons are consistent)
$iconFolder = icon('folder', '');
$iconEdit   = icon('edit', '');
$iconDelete = icon('delete', '');
$iconMove   = icon('folder', '');  // reuse folder icon for move
$iconUpload = icon('upload', '');
$iconFileGeneric = icon('file-text', '');
$iconEye    = icon('eye', '');
$iconDownload = icon('download', '');
$iconHome   = icon('home', '');


$typeIcons = [
    'pdf' => 'fa-file-pdf',
    'doc' => 'fa-file-word',
    'docx' => 'fa-file-word',
    'xls' => 'fa-file-excel',
    'xlsx' => 'fa-file-excel',
    'ppt' => 'fa-file-powerpoint',
    'pptx' => 'fa-file-powerpoint',
    'jpg' => 'fa-file-image',
    'jpeg' => 'fa-file-image',
    'png' => 'fa-file-image',
    'gif' => 'fa-file-image',
    'zip' => 'fa-file-archive',
    'rar' => 'fa-file-archive',
    'txt' => 'fa-file-alt',
    'default' => 'fa-file'
];
$typeColors = [
    'pdf' => '#dc2626',
    'docx' => '#0284c7',
    'doc' => '#0284c7',
    'jpg' => '#7c3aed',
    'jpeg' => '#7c3aed',
    'png' => '#7c3aed',
    'zip' => '#d97706',
    'xlsx' => '#16a34a',
    'pptx' => '#ea580c',
    'txt' => '#475569',
];
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Folder Browser | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/folder-browser.css">
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
                <a href="../documents/index.php" class="nav-item"><?php echo icon('documents', 'nav-icon'); ?> My Documents</a>
                <a href="../documents/shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared with Me</a>
                <a href="index.php" class="nav-item active"><?php echo icon('folder', 'nav-icon'); ?> Folder Browser</a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?>
                    <a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a>
                <?php endif; ?>
                <a href="../users/settings.php" class="nav-item"><?php echo icon('settings', 'nav-icon'); ?> Settings</a>
            </nav>
            <div class="sidebar-footer">
                <?php if (!$isViewer): ?>
                    <a href="../documents/upload.php" class="btn-upload"><?php echo icon('upload', ''); ?> Upload Document</a>
                <?php endif; ?>
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">

            <!-- Top toolbar: breadcrumb + action buttons -->
            <div class="fb-toolbar">
                <nav class="fb-breadcrumb" id="fbBreadcrumb" aria-label="Folder path">
                    <button class="bc-item bc-root" onclick="navigate(null)">
                        <?php echo $iconHome; ?> Academic Vault
                    </button>
                </nav>
                <div class="fb-toolbar-right">
                    <?php if (!$isViewer): ?>
                        <button class="fb-btn fb-btn-secondary" id="btnNewFolder" onclick="openNewFolderModal()">
                            <?php echo $iconFolder; ?> New Folder
                        </button>
                        <button class="fb-btn fb-btn-primary" id="btnUpload" onclick="openUploadModal()">
                            <?php echo $iconUpload; ?> Upload Here
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Split layout: tree panel + content panel -->
            <div class="fb-body">

                <!-- Left: collapsible folder tree -->
                <aside class="fb-tree">
                    <div class="fb-tree-header">Folders</div>
                    <div id="fbTree" class="fb-tree-list">
                        <div class="fb-tree-loading">Loading…</div>
                    </div>
                </aside>

                <!-- Right: folder grid + file table -->
                <section class="fb-content" id="fbContent">
                    <div class="fb-content-loading" id="fbLoading">
                        <div class="fb-spinner"></div>
                        <span>Loading…</span>
                    </div>

                    <!-- Sub-folders -->
                    <div class="fb-section" id="fbFolderSection">
                        <div class="fb-section-header">
                            <span class="fb-section-title">Folders</span>
                            <span class="fb-section-count" id="fbFolderCount">0</span>
                        </div>
                        <div class="fb-folder-grid" id="fbFolderGrid"></div>
                    </div>

                    <!-- Files -->
                    <div class="fb-section" id="fbFileSection">
                        <div class="fb-section-header">
                            <span class="fb-section-title">Files</span>
                            <span class="fb-section-count" id="fbFileCount">0</span>
                        </div>
                        <div class="fb-table-wrap">
                            <table class="fb-table">
                                <thead>
                                    <tr>
                                        <th style="width:44px;"></th>
                                        <th>Name</th>
                                        <th class="col-hide-sm">Type</th>
                                        <th class="col-hide-sm">Size</th>
                                        <th class="col-hide-sm">Uploaded</th>
                                        <th class="col-hide-sm">Status</th>
                                        <th style="text-align:right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="fbFileBody"></tbody>
                            </table>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- MODAL: New Folder -->
    <div class="fb-overlay" id="modalNewFolder" role="dialog" aria-modal="true" style="display:none;">
        <div class="fb-modal">
            <div class="fb-modal-hd">
                <h2>New Folder</h2>
                <button class="fb-modal-close" onclick="closeModal('modalNewFolder')" aria-label="Close">&#x2715;</button>
            </div>
            <div class="fb-modal-bd">
                <label class="fb-label">Folder Name <span class="fb-req">*</span></label>
                <input id="inputNewFolder" class="fb-input" type="text" placeholder="e.g. Course Materials 2025" autocomplete="off" maxlength="150">
                <p class="fb-hint">Will be created inside: <strong id="mNFParentName">Root</strong></p>
            </div>
            <div class="fb-modal-ft">
                <button class="fb-btn fb-btn-primary" onclick="submitNewFolder()"><?php echo icon('save', ''); ?> Create</button>
                <button class="fb-btn fb-btn-ghost" onclick="closeModal('modalNewFolder')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Rename Folder -->
    <div class="fb-overlay" id="modalRename" style="display:none;">
        <div class="fb-modal">
            <div class="fb-modal-hd">
                <h2>Rename Folder</h2>
                <button class="fb-modal-close" onclick="closeModal('modalRename')">&#x2715;</button>
            </div>
            <div class="fb-modal-bd">
                <label class="fb-label">New Name <span class="fb-req">*</span></label>
                <input id="inputRename" class="fb-input" type="text" maxlength="150">
                <input type="hidden" id="inputRenameFolderId">
            </div>
            <div class="fb-modal-ft">
                <button class="fb-btn fb-btn-primary" onclick="submitRename()"><?php echo icon('save', ''); ?> Rename</button>
                <button class="fb-btn fb-btn-ghost" onclick="closeModal('modalRename')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- MODAL: Move Folder -->
    <div class="fb-overlay" id="modalUpload" style="display:none;">
        <div class="fb-modal fb-modal-wide">
            <div class="fb-modal-hd">
                <h2>Upload File into <strong id="mUploadFolderName">this folder</strong></h2>
                <button class="fb-modal-close" onclick="closeModal('modalUpload')">&#x2715;</button>
            </div>
            <div class="fb-modal-bd">
                <div class="fb-dropzone" id="fbDropzone" onclick="document.getElementById('fbFileInput').click()">
                    <div id="fbDropPlaceholder">
                        <div style="font-size:2.4rem;"><?php echo icon('upload', ''); ?></div>
                        <p style="font-weight:700;color:#1e293b;">Drop file here or click to browse</p>
                        <p style="font-size:.78rem;color:#94a3b8;">PDF · DOCX · XLSX · PPTX · JPG · PNG · ZIP · TXT — max 20 MB</p>
                    </div>
                    <div id="fbDropSelected" style="display:none;text-align:center;">
                        <div id="fbDropIcon"></div>
                        <p style="font-weight:700;color:#0f172a;" id="fbDropName"></p>
                        <p style="font-size:.75rem;color:#64748b;" id="fbDropMeta"></p>
                        <button type="button" onclick="clearDropFile(event)" style="margin-top:.5rem;padding:4px 12px;border:1px solid #e2e8f0;border-radius:6px;background:#fff;">Change file</button>
                    </div>
                </div>
                <input type="file" id="fbFileInput" style="display:none;" accept=".pdf,.docx,.doc,.xlsx,.pptx,.jpg,.jpeg,.png,.zip,.txt">

                <div class="fb-upload-fields">
                    <div class="fb-field-row">
                        <div class="fb-field">
                            <label class="fb-label">Title <span class="fb-req">*</span></label>
                            <input id="fbUploadTitle" class="fb-input" type="text" placeholder="Document title">
                        </div>
                        <div class="fb-field">
                            <label class="fb-label">Author</label>
                            <input id="fbUploadAuthor" class="fb-input" type="text" placeholder="Author name">
                        </div>
                    </div>
                    <div class="fb-field-row">
                        <div class="fb-field">
                            <label class="fb-label">Subject</label>
                            <input id="fbUploadSubject" class="fb-input" type="text" placeholder="e.g. Finance">
                        </div>
                        <div class="fb-field">
                            <label class="fb-label">Starting Version</label>
                            <input id="fbUploadVersion" class="fb-input" type="number" value="1" min="1">
                        </div>
                    </div>
                    <div class="fb-field-row">
                        <div class="fb-field">
                            <label class="fb-label">Status</label>
                            <select id="fbUploadStatus" class="fb-input">
                                <option value="Draft">Draft</option>
                                <option value="Published">Published</option>
                                <option value="Final">Final</option>
                            </select>
                        </div>
                    </div>
                    <div class="fb-field">
                        <label class="fb-label">Content Description</label>
                        <textarea id="fbUploadDescription" class="fb-input" rows="3" placeholder="Brief summary..."></textarea>
                    </div>
                </div>
            </div>
            <div class="fb-modal-ft">
                <button class="fb-btn fb-btn-primary" id="fbUploadBtn" onclick="submitUpload()">
                    <?php echo icon('upload', ''); ?> Upload
                </button>
                <button class="fb-btn fb-btn-ghost" onclick="closeModal('modalUpload')">Cancel</button>
            </div>
        </div>
    </div>


    <!-- MODAL: Move Folder -->
    <div class="fb-overlay" id="modalMove" style="display:none;">
        <div class="fb-modal">
            <div class="fb-modal-hd">
                <h2>Move Folder</h2>
                <button class="fb-modal-close" onclick="closeModal('modalMove')">&#x2715;</button>
            </div>
            <div class="fb-modal-bd">
                <p class="fb-hint" style="margin-bottom:.8rem;">Moving: <strong id="mMoveSourceName"></strong></p>
                <label class="fb-label">Destination Folder</label>
                <select id="selectMoveTarget" class="fb-input">
                    <option value="">— Root (top level) —</option>
                </select>
                <input type="hidden" id="inputMoveFolderId">
            </div>
            <div class="fb-modal-ft">
                <button class="fb-btn fb-btn-primary" onclick="submitMove()"><?php echo icon('save', ''); ?> Move Here</button>
                <button class="fb-btn fb-btn-ghost" onclick="closeModal('modalMove')">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Context menu -->
    <ul class="fb-ctx" id="fbCtx" style="display:none;">
        <li><button onclick="ctxOpen()"><?php echo $iconFolder; ?> Open</button></li>
        <?php if (!$isViewer): ?>
            <li><button onclick="ctxRename()"><?php echo $iconEdit; ?> Rename</button></li>
            <li><button onclick="ctxMove()"><?php echo $iconMove; ?> Move</button></li>
            <li class="fb-ctx-danger"><button onclick="ctxDelete()"><?php echo $iconDelete; ?> Delete</button></li>
        <?php endif; ?>
    </ul>

    <script>
        // Pass SVG strings to JavaScript
        const FB_ICONS = {
            folder: <?php echo json_encode($iconFolder); ?>,
            edit: <?php echo json_encode($iconEdit); ?>,
            delete: <?php echo json_encode($iconDelete); ?>,
            move: <?php echo json_encode($iconMove); ?>,
            eye: <?php echo json_encode($iconEye); ?>,
            download: <?php echo json_encode($iconDownload); ?>,
            fileGeneric: <?php echo json_encode($iconFileGeneric); ?>
        };
        const FB_ROLE = <?php echo json_encode($userRole); ?>;
        const FB_VIEWER = <?php echo $isViewer ? 'true' : 'false'; ?>;



        // This variable MUST be updated when you open a folder
        let CURRENT_FOLDER_ID = null; // updated by navigate()

        function submitUpload() {
            const fileInput = document.getElementById('fbFileInput');
            const titleInput = document.getElementById('fbUploadTitle');
            const authorInput = document.getElementById('fbUploadAuthor');
            const subjectInput = document.getElementById('fbUploadSubject');
            const statusInput = document.getElementById('fbUploadStatus');
            const versionInput = document.getElementById('fbUploadVersion');
            const descInput = document.getElementById('fbUploadDescription');
            const uploadBtn = document.getElementById('fbUploadBtn');

            // 1. Validation
            if (!fileInput.files[0]) {
                alert("Please select a file first.");
                return;
            }
            if (!titleInput.value.trim()) {
                alert("Please enter a title for the document.");
                titleInput.focus();
                return;
            }
            if (!CURRENT_FOLDER_ID) {
                alert("Error: No destination folder selected.");
                return;
            }

            // 2. Prepare Data
            const formData = new FormData();
            formData.append('document', fileInput.files[0]);
            formData.append('title', titleInput.value.trim());
            formData.append('author', authorInput.value.trim());
            formData.append('subject', subjectInput.value.trim());
            formData.append('status', statusInput.value);
            formData.append('current_version', versionInput.value);
            formData.append('description', descInput.value.trim());
            formData.append('category_id', CURRENT_FOLDER_ID); // Crucial for folder-based systems

            // 3. UI State
            uploadBtn.disabled = true;
            const originalText = uploadBtn.innerHTML;
            uploadBtn.innerHTML = "Uploading...";

            // 4. The Fetch Request
            // Ensure the path to process-upload.php is correct relative to this file
            fetch('../documents/process-upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    console.log("Server Response:", data);
                    if (data.success) {
                        alert("File uploaded successfully!");
                        location.reload(); // Refresh to see the new file
                    } else {
                        alert("Upload Failed: " + data.message);
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error("Fetch Error:", error);
                    alert("A technical error occurred. Check the console.");
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = originalText;
                });
        }

        // Helper to clear file selection
        function clearDropFile(e) {
            if (e) e.stopPropagation();
            document.getElementById('fbFileInput').value = '';
            document.getElementById('fbDropSelected').style.display = 'none';
            document.getElementById('fbDropPlaceholder').style.display = 'block';
        }

        // Event listener for the file input selection
        document.getElementById('fbFileInput').onchange = function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                document.getElementById('fbDropName').innerText = file.name;
                document.getElementById('fbDropMeta').innerText = (file.size / 1024 / 1024).toFixed(2) + " MB";
                document.getElementById('fbDropPlaceholder').style.display = 'none';
                document.getElementById('fbDropSelected').style.display = 'block';

                // Auto-fill title if empty
                const titleField = document.getElementById('fbUploadTitle');
                if (!titleField.value) {
                    titleField.value = file.name.split('.').slice(0, -1).join('.');
                }
            }
        };
    </script>
    <script src="../../assets/js/folder-browser.js"></script>
<script src="../../assets/js/main.js"></script>
</body>

</html>