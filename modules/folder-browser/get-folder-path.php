<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$folderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$path = [];

while ($folderId > 0) {
    $stmt = $conn->prepare("SELECT id, name, parent_id FROM categories WHERE id = ?");
    $stmt->bind_param("i", $folderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $folder = $result->fetch_assoc();
    if (!$folder) break;
    array_unshift($path, ['id' => $folder['id'], 'name' => $folder['name']]);
    $folderId = $folder['parent_id'];
    $stmt->close();
}
echo json_encode($path);
