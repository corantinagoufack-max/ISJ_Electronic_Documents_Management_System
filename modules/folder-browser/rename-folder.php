<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { echo json_encode(['success'=>false,'message'=>'Unauthorized']); exit(); }
if ($_SESSION['user_role'] === 'Viewer') { echo json_encode(['success'=>false,'message'=>'Viewers cannot rename folders']); exit(); }

$folderId = (int)($_POST['folder_id'] ?? 0);
$newName  = trim($_POST['new_name'] ?? '');
if ($folderId <= 0 || empty($newName)) { echo json_encode(['success'=>false,'message'=>'Invalid data']); exit(); }

$stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
$stmt->bind_param("si", $newName, $folderId);
echo $stmt->execute()
    ? json_encode(['success'=>true,'name'=>$newName])
    : json_encode(['success'=>false,'message'=>'Rename failed: '.$conn->error]);
