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

// Fetch document details
$doc_stmt = $conn->prepare("SELECT d.*, c.name as category_name FROM documents d LEFT JOIN categories c ON d.category_id=c.id WHERE d.id=?");
$doc_stmt->bind_param("i", $docId);
$doc_stmt->execute();
$doc = $doc_stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: index.php");
    exit();
}

// Permissions Check
$isOwner = ($doc['uploaded_by'] == $userId);
$isAdmin = ($userRole === 'Admin');
if (!$isAdmin && !$isOwner) {
    header("Location: index.php?msg=No+permission+to+share");
    exit();
}

$error   = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $emails = array_map('trim', explode(',', $_POST['emails'] ?? ''));
        $permission = in_array($_POST['permission'], ['view', 'download', 'edit']) ? $_POST['permission'] : 'view';
        $added = 0;
        $skipped = [];

        foreach ($emails as $email) {
            if (empty($email)) continue;

            // 1. Find User
            $uq = $conn->prepare("SELECT id FROM users WHERE email=?");
            $uq->bind_param("s", $email);
            $uq->execute();
            $target = $uq->get_result()->fetch_assoc();

            if (!$target) {
                $skipped[] = "$email (not found)";
                continue;
            }
            if ($target['id'] == $userId) {
                $skipped[] = "$email (yourself)";
                continue;
            }

            // 2. Check for existing share
            $dup = $conn->prepare("SELECT id FROM document_shares WHERE document_id=? AND shared_with_user_id=?");
            $dup->bind_param("ii", $docId, $target['id']);
            $dup->execute();

            if ($dup->get_result()->num_rows > 0) {
                // Update existing permission
                $upd = $conn->prepare("UPDATE document_shares SET permission=? WHERE document_id=? AND shared_with_user_id=?");
                $upd->bind_param("sii", $permission, $docId, $target['id']);
                $upd->execute();
                $skipped[] = "$email (permission updated)";
                continue;
            }

            // 3. Insert new share (matching your schema with shared_by_id)
            $ins = $conn->prepare("INSERT INTO document_shares (document_id, shared_by_user_id, shared_with_user_id, shared_by_id, permission, shared_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $ins->bind_param("iiiis", $docId, $userId, $target['id'], $userId, $permission);
            if ($ins->execute()) {
                $added++;
            }
        }

        if ($added > 0) $success = "Shared with $added user(s). ";
        if ($skipped) $success .= "Note: " . implode(', ', $skipped);
    } elseif ($_POST['action'] === 'remove') {
        // FIX: Use DELETE instead of INSERT, and target the specific share ID
        $shareId = (int)$_POST['share_id'];
        $del = $conn->prepare("DELETE FROM document_shares WHERE id=? AND document_id=?");
        $del->bind_param("ii", $shareId, $docId);

        if ($del->execute()) {
            $success = "Access removed successfully.";
        } else {
            $error = "Error removing access.";
        }
    }
}

// Fetch current shares
$shares_query = $conn->prepare("
    SELECT ds.*, u.fullname, u.email, u.role
    FROM document_shares ds
    LEFT JOIN users u ON ds.shared_with_user_id = u.id
    WHERE ds.document_id = ?
    ORDER BY ds.shared_at DESC
");
$shares_query->bind_param("i", $docId);
$shares_query->execute();
$shares = $shares_query->get_result()->fetch_all(MYSQLI_ASSOC);

// Get IDs of users already shared with to exclude them from suggestions
$sharedIds = array_column($shares, 'shared_with_user_id');
$sharedIds[] = $userId;
$excludeList = implode(',', $sharedIds);
$allUsers = $conn->query("SELECT id, fullname, email, role FROM users WHERE id NOT IN ($excludeList) ORDER BY fullname LIMIT 20")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Document | ISJ-DMS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/share.css">
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
                <a href="shared.php" class="nav-item"><?php echo icon('shared', 'nav-icon'); ?> Shared With Me</a>
                <div class="nav-divider"></div>
                <?php if ($isAdmin): ?><a href="../../usermanagement.php" class="nav-item"><?php echo icon('users', 'nav-icon'); ?> User Management</a><?php endif; ?>
            </nav>
            <div class="sidebar-footer">
                <a href="../../logout.php" class="logout-link"><?php echo icon('logout', ''); ?> Logout</a>
            </div>
        </aside>

        <main class="main-content">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                <h1 style="font-size:1.4rem;font-weight:800;color:#0f172a;">Share Document</h1>
                <a href="view.php?id=<?php echo $docId; ?>" style="padding:9px 16px;background:#f1f5f9;color:#475569;border-radius:8px;text-decoration:none;font-size:.85rem;">← Back</a>
            </div>

            <div class="doc-ref">
                <div style="color:#3498db;"><?php echo icon('documents', ''); ?></div>
                <div>
                    <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                    <span style="font-size:.8rem; color:#64748b;"><?php echo htmlspecialchars($doc['category_name'] ?? 'General'); ?> · Version <?php echo $doc['current_version']; ?></span>
                </div>
            </div>

            <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>

            <div class="card">
                <h3>Add Collaborators</h3>
                <form method="POST" id="shareForm">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="emails" id="emailsHidden">
                    <div class="form-row">
                        <div class="form-group">
                            <label>User Email(s)</label>
                            <div class="user-tags" id="tagBox" onclick="document.getElementById('emailInput').focus()">
                                <input type="text" id="emailInput" placeholder="Type email and press Enter...">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Permission</label>
                            <select name="permission">
                                <option value="view">View Only</option>
                                <option value="download">Download</option>
                                <option value="edit">Edit Access</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-share">Share</button>
                    </div>
                </form>

                <?php if (!empty($allUsers)): ?>
                    <div style="margin-top:1rem;">
                        <label style="font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase;">Quick Add</label>
                        <div style="display:flex; flex-wrap:wrap; gap:5px; margin-top:5px;">
                            <?php foreach ($allUsers as $u): ?>
                                <button type="button" onclick="addTag('<?php echo $u['email']; ?>')" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:15px; padding:2px 10px; font-size:.75rem; cursor:pointer;"><?php echo htmlspecialchars($u['fullname']); ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Who has access</h3>
                <ul class="share-list">
                    <?php foreach ($shares as $s): ?>
                        <li class="share-row">
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($s['fullname']); ?></strong>
                                <span><?php echo htmlspecialchars($s['email']); ?></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:15px;">
                                <span class="perm-badge perm-<?php echo $s['permission']; ?>"><?php echo $s['permission']; ?></span>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="share_id" value="<?php echo $s['id']; ?>">
                                    <button type="submit" class="btn-remove" onclick="return confirm('Remove access?')">Remove</button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>
    </div>

    <script src="../../assets/js/share.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>

</html>