<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Security: Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();

if (!$doc) {
    die("Document not found in the Academic Vault.");
}

$filePath = 'storage/uploads/' . $doc['file_path'];

// Check if file physically exists
if (!file_exists($filePath)) {
    die("Error: The physical file is missing from the storage directory.");
}

/* --- NEW: STREAMING LOGIC FOR VIEW.JS --- */
if (isset($_GET['stream']) && $_GET['stream'] === 'true') {
    $mime_types = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif'
    ];

    $ext = strtolower($doc['extension']);
    $contentType = $mime_types[$ext] ?? 'application/octet-stream';

    // Clear buffer to prevent corruption
    ob_clean();
    flush();

    header("Content-Type: " . $contentType);
    header("Content-Length: " . filesize($filePath));
    header("Content-Disposition: inline; filename=\"" . $doc['title'] . "\"");

    readfile($filePath);
    exit();
}
/*  END STREAMING LOGIC  */

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Preview: <?php echo htmlspecialchars($doc['title']); ?> | ISJ-DMS</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .view-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #1e293b;
        }

        .view-header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .preview-area {
            flex-grow: 1;
            border: none;
            width: 100%;
            background: #334155;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #64748b;
            font-weight: 500;
        }

        .doc-info h2 {
            font-size: 1.1rem;
            margin: 0;
            color: #1e293b;
        }

        .badge {
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .img-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            overflow: auto;
            padding: 20px;
        }

        .preview-img {
            max-width: 90%;
            max-height: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            border-radius: 4px;
        }
    </style>
    <link rel="stylesheet" href="assets/css/8-responsive/responsive.css">
</head>

<body>
    <div class="view-container">
        <header class="view-header">
            <a href="dashboard.php" class="back-btn">
                <?php echo icon('dashboard', 'icon-xs'); ?> Back to Dashboard
            </a>
            <div class="doc-info" style="text-align: center;">
                <h2><?php echo htmlspecialchars($doc['title']); ?></h2>
                <span class="badge">Version v<?php echo $doc['current_version']; ?></span>
            </div>
            <div class="actions">
                <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn-upload" style="background: #22c55e;">
                    <?php echo icon('download', 'icon-xs'); ?> Download
                </a>
            </div>
        </header>

        <?php if ($doc['extension'] === 'pdf'): ?>
            <iframe src="view.php?id=<?php echo $id; ?>&stream=true#toolbar=0" class="preview-area"></iframe>
        <?php elseif (in_array(strtolower($doc['extension']), ['jpg', 'png', 'jpeg', 'gif'])): ?>
            <div class="img-wrapper">
                <img src="view.php?id=<?php echo $id; ?>&stream=true" class="preview-img">
            </div>
        <?php else: ?>
            <div style="color: white; text-align: center; margin-top: 100px;">
                <div style="font-size: 3rem; margin-bottom: 20px;">📄</div>
                <h3>Preview not available for .<?php echo $doc['extension']; ?> files.</h3>
                <p>The Academic Vault protects this file type. Please download to view.</p>
                <br>
                <a href="download.php?id=<?php echo $doc['id']; ?>" class="btn-upload">Download Document</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/view.js"></script>
</body>

</html>