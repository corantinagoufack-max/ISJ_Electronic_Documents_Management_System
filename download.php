<?php
session_start();
require_once 'config/database.php';

// Must be logged in to download
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$docId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = (int)$_SESSION['user_id'];
$role   = $_SESSION['user_role'];

if ($docId <= 0) {
    header("Location: modules/documents/index.php?alert=error&msg=Invalid+document+ID.");
    exit();
}

// Fetch document
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: modules/documents/index.php?alert=error&msg=Document+not+found.");
    exit();
}

// Permission check: Admin sees all, others see own or shared or published
if ($role !== 'Admin') {
    $isOwner  = ($doc['uploaded_by'] == $userId);
    $isPublic = in_array($doc['status'], ['Published', 'Final']);

    if (!$isOwner && !$isPublic) {
        // Check if shared with this user
        $chk = $conn->prepare("SELECT id FROM document_shares WHERE document_id = ? AND shared_with_user_id = ?");
        $chk->bind_param("ii", $docId, $userId);
        $chk->execute();
        $isShared = $chk->get_result()->num_rows > 0;

        if (!$isShared) {
            header("Location: modules/documents/index.php?alert=error&msg=You+do+not+have+permission+to+download+this+document.");
            exit();
        }
    }
}

// Build the file path — check both storage locations
$paths = [
    'storage/documents/users/' . $doc['file_path'],
    'storage/uploads/' . $doc['file_path'],
    'documents/users/' . $doc['file_path'],
];

$filePath = null;
foreach ($paths as $p) {
    if (file_exists($p)) {
        $filePath = $p;
        break;
    }
}

if (!$filePath) {
    header("Location: modules/documents/index.php?alert=error&msg=The+file+could+not+be+found+on+the+server.");
    exit();
}

// Serve the file as a download
$downloadName = $doc['title'] . '.' . $doc['extension'];

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . addslashes($downloadName) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
flush();
readfile($filePath);
exit();
