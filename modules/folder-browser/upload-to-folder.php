<?php
session_start();
require_once '../../config/database.php';
header('Content-Type: application/json');

// 1. Security Checks
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
if ($_SESSION['user_role'] === 'Viewer') {
    echo json_encode(['success' => false, 'message' => 'Viewers cannot upload files']);
    exit();
}

// 2. File Presence Check
if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode(['success' => false, 'message' => 'No file provided']);
    exit();
}

$file = $_FILES['document'];
$errMap = [
    1 => 'File exceeds server limit',
    2 => 'File exceeds form limit',
    3 => 'Partial upload',
    6 => 'No temp folder',
    7 => 'Cannot write to disk'
];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => $errMap[$file['error']] ?? 'Upload error ' . $file['error']]);
    exit();
}

// 3. Validation
$allowed = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'zip', 'xlsx', 'pptx', 'txt'];
$ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode(['success' => false, 'message' => "File type '$ext' is not allowed"]);
    exit();
}
if ($file['size'] > 20 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File exceeds 20 MB limit']);
    exit();
}

// 4. Capture Data (Modified to match Modal/Form names)
$title       = trim($_POST['title'] ?? '');
$author      = trim($_POST['author'] ?? '');
$subject     = trim($_POST['subject'] ?? '');
$status      = in_array($_POST['status'] ?? '', ['Draft', 'Published', 'Final']) ? $_POST['status'] : 'Draft';

// IMPORTANT: Modal sends 'category_id', original code sent 'folder_id'
// We check both to ensure compatibility with all your pages
$categoryId  = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : (!empty($_POST['folder_id']) ? (int)$_POST['folder_id'] : null);

// New fields from your updated modal
$version     = !empty($_POST['current_version']) ? (int)$_POST['current_version'] : 1;
$description = trim($_POST['description'] ?? '');

if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Document title is required']);
    exit();
}

if (empty($categoryId)) {
    echo json_encode(['success' => false, 'message' => 'Destination folder is missing']);
    exit();
}

// 5. File Handling
$uploadDir = '../../storage/documents/users/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

$newName = 'ISJ_' . uniqid('', true) . '.' . $ext;
$dest    = $uploadDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file to disk']);
    exit();
}

// 6. Database Entry
$uploaderId   = (int)$_SESSION['user_id'];
$lastContrib  = $_SESSION['user_name'] ?? $author;
$fileSize     = (int)$file['size'];

// Updated SQL to include 'description'
$sql  = "INSERT INTO documents (title, subject, author, last_contributor, file_path, extension, size, status, current_version, category_id, uploaded_by, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    @unlink($dest);
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit();
}

// Updated bind_param: added "s" at the end for description and the variable $description
// Sequence: 6 strings, 1 int, 1 string, 3 ints, 1 string = "ssssssisiiis"
$stmt->bind_param("ssssssisiiis", $title, $subject, $author, $lastContrib, $newName, $ext, $fileSize, $status, $version, $categoryId, $uploaderId, $description);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Upload successful',
        'id' => $conn->insert_id,
        'title' => $title,
        'ext' => $ext
    ]);
} else {
    @unlink($dest);
    echo json_encode(['success' => false, 'message' => 'DB insert error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
