<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']); exit();
}

$userId   = (int)$_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$folderId = (isset($_GET['id']) && $_GET['id'] !== '' && $_GET['id'] !== 'null')
            ? (int)$_GET['id'] : null;

// ── Sub-folders ──────────────────────────────────────────────────────────────
$sqlF  = "SELECT id, name FROM categories WHERE " .
         ($folderId ? "parent_id = ?" : "parent_id IS NULL") .
         " ORDER BY name ASC";
$stmtF = $conn->prepare($sqlF);
if ($folderId) $stmtF->bind_param("i", $folderId);
$stmtF->execute();
$folders = $stmtF->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Files in this folder (respecting role permissions) ───────────────────────
// When folderId is null (root), show documents that have NO category assigned
if ($userRole === 'Admin') {
    $sqlD = "SELECT d.id, d.title, d.extension, d.size, d.upload_date, d.status,
                    d.current_version, u.fullname AS uploader
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.category_id " . ($folderId ? "= ?" : "IS NULL") .
            " ORDER BY d.title ASC";
    $stmtD = $conn->prepare($sqlD);
    if ($folderId) $stmtD->bind_param("i", $folderId);
} else {
    $sqlD = "SELECT d.id, d.title, d.extension, d.size, d.upload_date, d.status,
                    d.current_version, u.fullname AS uploader
             FROM documents d
             LEFT JOIN users u ON d.uploaded_by = u.id
             WHERE d.category_id " . ($folderId ? "= ?" : "IS NULL") .
            " AND (d.uploaded_by = ?
                   OR d.status IN ('Published','Final')
                   OR EXISTS (
                       SELECT 1 FROM document_shares ds
                       WHERE ds.document_id = d.id AND ds.shared_with_user_id = ?
                   ))
             ORDER BY d.title ASC";
    $stmtD = $conn->prepare($sqlD);
    if ($folderId) {
        $stmtD->bind_param("iii", $folderId, $userId, $userId);
    } else {
        $stmtD->bind_param("ii", $userId, $userId);
    }
}
$stmtD->execute();
$files = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Full breadcrumb path ─────────────────────────────────────────────────────
$path = [];
$cur  = $folderId;
$max  = 12;
while ($cur && $max-- > 0) {
    $sp = $conn->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
    $sp->bind_param("i", $cur);
    $sp->execute();
    $row = $sp->get_result()->fetch_assoc();
    if (!$row) break;
    array_unshift($path, ['id' => (int)$row['id'], 'name' => $row['name']]);
    $cur = $row['parent_id'];
}

echo json_encode([
    'folders' => $folders,
    'files'   => $files,
    'path'    => $path,
    'current' => $folderId,
]);
