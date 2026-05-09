<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
if ($_SESSION['user_role'] === 'Viewer') { echo json_encode(['success'=>false,'message'=>'Viewers cannot delete folders']); exit(); }

$folderId = (int)($_POST['folder_id'] ?? 0);
if ($folderId <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid folder ID']); exit(); }

// Block if has sub-folders
$sub = $conn->prepare("SELECT COUNT(*) AS n FROM categories WHERE parent_id = ?");
$sub->bind_param("i", $folderId); $sub->execute();
if ($sub->get_result()->fetch_assoc()['n'] > 0) {
    echo json_encode(['success'=>false,'message'=>'Folder has sub-folders. Delete or move them first.']); exit();
}
// Block if has documents
$doc = $conn->prepare("SELECT COUNT(*) AS n FROM documents WHERE category_id = ?");
$doc->bind_param("i", $folderId); $doc->execute();
if ($doc->get_result()->fetch_assoc()['n'] > 0) {
    echo json_encode(['success'=>false,'message'=>'Folder contains documents. Move or delete them first.']); exit();
}

$stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
$stmt->bind_param("i", $folderId);
echo $stmt->execute()
    ? json_encode(['success'=>true])
    : json_encode(['success'=>false,'message'=>'Delete failed: '.$conn->error]);
