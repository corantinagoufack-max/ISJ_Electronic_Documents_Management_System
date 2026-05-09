<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
if ($_SESSION['user_role'] === 'Viewer') { echo json_encode(['success'=>false,'message'=>'Viewers cannot create folders']); exit(); }

$name     = trim($_POST['folder_name'] ?? '');
$parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

if (empty($name)) { echo json_encode(['success'=>false,'message'=>'Folder name is required']); exit(); }
if (mb_strlen($name) > 150) { echo json_encode(['success'=>false,'message'=>'Name too long (max 150 characters)']); exit(); }

// Duplicate check at same level
$sql = "SELECT id FROM categories WHERE name = ? AND " . ($parentId ? "parent_id = ?" : "parent_id IS NULL");
$chk = $conn->prepare($sql);
if ($parentId) { $chk->bind_param("si", $name, $parentId); } else { $chk->bind_param("s", $name); }
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    echo json_encode(['success'=>false,'message'=>"A folder named \"$name\" already exists here"]); exit();
}

$createdBy = (int)$_SESSION['user_id'];
$stmt = $conn->prepare("INSERT INTO categories (name, parent_id, created_by) VALUES (?, ?, ?)");
if (!$stmt) {
    // created_by column may not exist — try without it
    $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $parentId);
} else {
    $stmt->bind_param("sii", $name, $parentId, $createdBy);
}

echo $stmt->execute()
    ? json_encode(['success'=>true,'id'=>$conn->insert_id,'name'=>$name])
    : json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
