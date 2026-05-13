<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

$conditions = [];
$params = [];
$types = "";

// Dynamic SQL Construction
if (!empty($_GET['title'])) {
    $conditions[] = "d.title LIKE ?";
    $params[] = "%" . $_GET['title'] . "%";
    $types .= "s";
}
if (!empty($_GET['author'])) {
    $conditions[] = "d.author LIKE ?";
    $params[] = "%" . $_GET['author'] . "%";
    $types .= "s";
}
if (!empty($_GET['subject'])) {
    $conditions[] = "d.subject LIKE ?";
    $params[] = "%" . $_GET['subject'] . "%";
    $types .= "s";
}
if (!empty($_GET['category_id'])) {
    $conditions[] = "d.category_id = ?";
    $params[] = $_GET['category_id'];
    $types .= "i";
}
if (!empty($_GET['date_from'])) {
    $conditions[] = "d.upload_date >= ?";
    $params[] = $_GET['date_from'] . " 00:00:00";
    $types .= "s";
}
if (!empty($_GET['date_to'])) {
    $conditions[] = "d.upload_date <= ?";
    $params[] = $_GET['date_to'] . " 23:59:59";
    $types .= "s";
}

$sql = "SELECT d.*, c.name as category_name 
        FROM documents d 
        LEFT JOIN categories c ON d.category_id = c.id";

if (count($conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}
$sql .= " ORDER BY d.upload_date DESC";

$stmt = $conn->prepare($sql);
if (count($params) > 0) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$results = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Search Results | ISJ-DMS</title>
    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/7-pages/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/8-responsive/responsive.css">
</head> 

<body style="background: #f8fafc; padding: 40px;">

    <div style="max-width: 1100px; margin: auto;">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1>Search Results</h1>
                <p>Found <?= $results->num_rows ?> matching documents.</p>
            </div>
            <a href="search.php" class="btn-upload" style="background: #64748b;">New Search</a>
        </header>

        <div class="recent-docs-panel" style="width: 100%;">
            <table class="lean-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Title & Subject</th>
                        <th class="col-hide-sm">Author</th>
                        <th class="col-hide-sm">Version</th>
                        <th class="col-hide-sm">Modified</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($doc = $results->fetch_assoc()): ?>
                        <tr>
                            <td>📄</td>
                            <td>
                                <strong><?= htmlspecialchars($doc['title']) ?></strong><br>
                                <small style="color: #64748b;"><?= htmlspecialchars($doc['subject'] ?? 'General') ?></small>
                            </td>
                            <td class="col-hide-sm"><?= htmlspecialchars($doc['author'] ?? 'Unknown') ?></td>
                            <td class="col-hide-sm"><span class="version-badge">v<?= $doc['current_version'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($doc['upload_date'])) ?></td>
                            <td>
                                <div class="action-group">
                                    <a href="../../view.php?id=<?= $doc['id'] ?>">👁️</a>
                                    <a href="../../download.php?id=<?= $doc['id'] ?>">💾</a>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($results->num_rows === 0): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8;">
                    <p>No documents found matching your criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>