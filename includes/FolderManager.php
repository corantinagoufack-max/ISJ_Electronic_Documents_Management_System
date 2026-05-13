<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

// If id is empty or "null", we are at the Root
$folderId = (isset($_GET['id']) && $_GET['id'] !== 'null' && $_GET['id'] !== '') ? (int)$_GET['id'] : null;

// 1. Fetch Sub-folders
$queryFolders = "SELECT id, name FROM categories WHERE " . ($folderId ? "parent_id = ?" : "parent_id IS NULL");
$stmtF = $conn->prepare($queryFolders);
if ($folderId) $stmtF->bind_param("i", $folderId);
$stmtF->execute();
$folders = $stmtF->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Fetch Files inside this folder
$queryFiles = "SELECT id, title, extension, size FROM documents WHERE folder_id " . ($folderId ? "= ?" : "IS NULL");
$stmtDoc = $conn->prepare($queryFiles);
if ($folderId) $stmtDoc->bind_param("i", $folderId);
$stmtDoc->execute();
$files = $stmtDoc->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['folders' => $folders, 'files' => $files]);
