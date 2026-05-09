<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Only logged-in users who are NOT Viewers can upload
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}
if ($_SESSION['user_role'] === 'Viewer') {
    header("Location: ../../dashboard.php?alert=error&msg=Viewers+are+not+allowed+to+upload+documents.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: upload.php");
    exit();
}

// Validate file was submitted
if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
    header("Location: upload.php?alert=error&msg=No+file+was+selected.+Please+choose+a+file+to+upload.");
    exit();
}

$file = $_FILES['document'];

// Handle specific upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE   => 'The file exceeds the server maximum upload size.',
        UPLOAD_ERR_FORM_SIZE  => 'The file exceeds the form maximum size.',
        UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded. Please try again.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server error: Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Server error: Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'Server error: A PHP extension blocked the upload.',
    ];
    $msg = $uploadErrors[$file['error']] ?? 'An unknown upload error occurred.';
    header("Location: upload.php?alert=error&msg=" . urlencode($msg));
    exit();
}

// Validate required fields
$title = trim($_POST['title'] ?? '');
if (empty($title)) {
    header("Location: upload.php?alert=error&msg=Document+title+is+required.");
    exit();
}

$category_id = (int)($_POST['category_id'] ?? 0);
if ($category_id <= 0) {
    header("Location: upload.php?alert=error&msg=Please+select+a+valid+category.");
    exit();
}

// Allowed file types
$allowedExtensions = ['pdf', 'docx', 'doc', 'jpg', 'jpeg', 'png', 'zip', 'xlsx', 'pptx', 'txt'];
$fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($fileExt, $allowedExtensions)) {
    $msg = "File type '." . $fileExt . "' is not allowed. Accepted types: PDF, DOCX, DOC, JPG, PNG, ZIP, XLSX, PPTX, TXT.";
    header("Location: upload.php?alert=error&msg=" . urlencode($msg));
    exit();
}

// Max file size: 20MB
$maxSize = 20 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    header("Location: upload.php?alert=error&msg=File+is+too+large.+Maximum+allowed+size+is+20MB.");
    exit();
}

// Prepare storage directory
$uploadDir = '../../storage/documents/users/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        header("Location: upload.php?alert=error&msg=Server+error:+Could+not+create+upload+directory.");
        exit();
    }
}

// Generate a unique filename
$newFileName     = "ISJ_" . uniqid('', true) . "." . $fileExt;
$fileDestination = $uploadDir . $newFileName;

// ==================================================
// TEXT EXTRACTION FOR SEARCH INSIDE DOCUMENTS
// ==================================================
$extractedText = '';

if (function_exists('extractTextFromFile')) {
    try {
        // Extract text from the temporary file before moving it
        $extractedText = extractTextFromFile($file['tmp_name'], $fileExt);
        
        // Clean up the extracted text
        $extractedText = trim($extractedText);
        $extractedText = preg_replace('/\s+/', ' ', $extractedText);
        
        // Limit to 65,535 characters for TEXT column
        if (strlen($extractedText) > 65535) {
            $extractedText = substr($extractedText, 0, 65535);
        }
        
        // Optional: Log how much text was extracted
        error_log("Extracted " . strlen($extractedText) . " characters from {$title}.{$fileExt}");
        
    } catch (Exception $e) {
        error_log("Text extraction failed for {$title}: " . $e->getMessage());
        $extractedText = '';
    }
}
// ==================================================

if (!move_uploaded_file($file['tmp_name'], $fileDestination)) {
    header("Location: upload.php?alert=error&msg=Failed+to+save+the+uploaded+file.+Please+try+again.");
    exit();
}

// Save document record to database
$uploader_id      = (int)$_SESSION['user_id'];
$subject          = trim($_POST['subject'] ?? '');
$author           = trim($_POST['author'] ?? '');
$description      = trim($_POST['description'] ?? '');
$version          = max(1, (int)($_POST['current_version'] ?? 1));
$status           = in_array($_POST['status'] ?? '', ['Draft', 'Published', 'Final']) ? $_POST['status'] : 'Draft';
$last_contributor = $_SESSION['user_name'] ?? $author;
$fileSize         = (int)$file['size'];

$sql = "INSERT INTO documents 
        (title, subject, author, last_contributor, description, file_path, extension, size, status, current_version, category_id, uploaded_by, extracted_text) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    @unlink($fileDestination);
    header("Location: upload.php?alert=error&msg=" . urlencode("Database preparation error: " . $conn->error));
    exit();
}

$stmt->bind_param(
    "sssssssisiiis",
    $title, $subject, $author, $last_contributor,
    $description, $newFileName, $fileExt, $fileSize,
    $status, $version, $category_id, $uploader_id, $extractedText
);

if ($stmt->execute()) {
    header("Location: index.php?alert=success&msg=" . urlencode("Document '$title' was uploaded successfully."));
    exit();
} else {
    @unlink($fileDestination);
    header("Location: upload.php?alert=error&msg=" . urlencode("Database error: " . $stmt->error));
    exit();
}
?>