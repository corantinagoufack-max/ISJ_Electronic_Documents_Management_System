<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISJ-DMS | Modern Academic Vault for ISJ</title>
    <link rel="stylesheet" href="assets/css/app.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php
    // Allow pages to add their own CSS after the global ones
    if (isset($page_css)) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars($page_css) . '">';
    }
    ?>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container container">
            <a href="index.php" class="logo">
                <i class="fas fa-folder-open"></i>
                <span>ISJ-DMS</span>
            </a>
            <!-- Hamburger for mobile -->
            <button class="hamburger" id="hamburger" aria-label="Open navigation" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="#">Features</a>
                <a href="#">Students</a>
                <a href="#">Teachers</a>
                <a href="#">Support</a>
                <a href="login.php" class="login-btn">Login</a>
            </div>
        </div>
    </nav>
    <main>
    <script src="assets/js/main.js" defer></script>