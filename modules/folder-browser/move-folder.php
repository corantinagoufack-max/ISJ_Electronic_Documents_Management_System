<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
if ($_SESSION['user_role'] === 'Viewer') { echo json_encode(['success'=>false,'message'=>'Viewers cannot move folders']); exit(); }

$folderId    = (int)($_POST['folder_id'] ?? 0);
$newParentId = !empty($_POST['new_parent_id']) ? (int)$_POST['new_parent_id'] : null;

if ($folderId <= 0) { echo json_encode(['success'=>false,'message'=>'Invalid folder ID']); exit(); }
if ($folderId === $newParentId) { echo json_encode(['success'=>false,'message'=>'Cannot move a folder into itself']); exit(); }

// Cycle check
$cur = $newParentId;
$limit = 15;
while ($cur && $limit-- > 0) {
    if ($cur === $folderId) { echo json_encode(['success'=>false,'message'=>'This move would create a circular reference']); exit(); }
    $p = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
    $p->bind_param("i", $cur); $p->execute();
    $row = $p->get_result()->fetch_assoc();
    $cur = $row['parent_id'] ?? null;
}

$stmt = $conn->prepare("UPDATE categories SET parent_id = ? WHERE id = ?");
$stmt->bind_param("ii", $newParentId, $folderId);
echo $stmt->execute()
    ? json_encode(['success'=>true])
    : json_encode(['success'=>false,'message'=>'Move failed: '.$conn->error]);
