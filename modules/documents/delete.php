<?php
session_start();
require_once '../../config/database.php';

// 1. Authentication Check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

// 2. Must be a POST request — no GET-based deletes
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php?alert=error&msg=Invalid+request+method.");
    exit();
}

// 3. CSRF token validation — prevents accidental/malicious deletions
$submittedToken = $_POST['csrf_token'] ?? '';
$sessionToken   = $_SESSION['csrf_token'] ?? '';
if (empty($submittedToken) || !hash_equals($sessionToken, $submittedToken)) {
    header("Location: index.php?alert=error&msg=Security+token+mismatch.+Please+try+again.");
    exit();
}

// 4. Role Check
$userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'Viewer';
$userId   = (int)$_SESSION['user_id'];

if ($userRole === 'Viewer') {
    header("Location: index.php?alert=error&msg=Viewers+do+not+have+permission+to+delete+documents.");
    exit();
}

// 5. Validate Document ID from POST
$docId = isset($_POST['doc_id']) ? (int)$_POST['doc_id'] : 0;
if ($docId <= 0) {
    header("Location: index.php?alert=error&msg=Invalid+document+ID.");
    exit();
}

// 6. Fetch the document metadata
$stmt = $conn->prepare("SELECT id, title, file_path, uploaded_by FROM documents WHERE id = ?");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    header("Location: index.php?alert=error&msg=Document+not+found.");
    exit();
}

// 7. Permission Logic: Only Admin or the uploader can delete
$isAdmin = ($userRole === 'Admin');
$isOwner = ($doc['uploaded_by'] == $userId);

if (!$isAdmin && !$isOwner) {
    header("Location: index.php?alert=error&msg=You+do+not+have+permission+to+delete+this+document.");
    exit();
}

// 8. Delete physical file from the user-specific storage path
$filePath = '../../storage/documents/users/' . $doc['file_path'];
if (file_exists($filePath)) {
    @unlink($filePath);
}

// 9. Delete related records (Foreign Key Cleanup)
$delShares = $conn->prepare("DELETE FROM document_shares WHERE document_id = ?");
$delShares->bind_param("i", $docId);
$delShares->execute();

$delVersions = $conn->prepare("DELETE FROM document_versions WHERE document_id = ?");
$delVersions->bind_param("i", $docId);
$delVersions->execute();

// 10. Delete the Document record itself
$delDoc = $conn->prepare("DELETE FROM documents WHERE id = ?");
if (!$delDoc) {
    header("Location: index.php?alert=error&msg=" . urlencode("Database error: " . $conn->error));
    exit();
}

$delDoc->bind_param("i", $docId);

if ($delDoc->execute()) {
    // Regenerate CSRF token after a successful action
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $msg = urlencode("Document '" . $doc['title'] . "' was deleted successfully.");
    header("Location: index.php?alert=success&msg=$msg");
    exit();
} else {
    header("Location: index.php?alert=error&msg=" . urlencode("Failed to delete document: " . $conn->error));
    exit();
}