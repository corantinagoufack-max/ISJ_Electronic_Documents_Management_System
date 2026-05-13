<?php
// Note: session_start() and database connection should be handled by the parent file
// but we include the icon helper here.

if (!function_exists('icon')) {
    function icon($name, $class = "")
    {
        // Map extension names to icon files
        $map = [
            'pdf' => 'file-pdf',
            'docx' => 'file-word',
            'doc' => 'file-word',
            'jpg' => 'file-image',
            'jpeg' => 'file-image',
            'png' => 'file-image',
            'zip' => 'file-archive',
            'rar' => 'file-archive',
            'default' => 'file-text'
        ];
        if (strpos($name, 'file-type-') === 0) {
            $ext = str_replace('file-type-', '', $name);
            $name = $map[$ext] ?? $map['default'];
        }

        // Adjust path to root directory
        $path = dirname(__DIR__) . "/assets/icons/{$name}.svg";

        if (!file_exists($path)) return "<span style='font-size:1.2em'>📄</span>";

        $svg = file_get_contents($path);
        $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
        if (!empty($class)) $svg = str_replace('<svg', "<svg class=\"{$class}\"", $svg);
        return $svg;
    }
}

// Get current page to set 'active' class
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile sidebar toggle -->
<button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
    </svg>
</button>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<aside class="sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <div class="brand-header">
            <img src="../../assets/images/logo.png" alt="ISJ" class="brand-logo-img">
            <div class="brand-text">
                <h3>The Academic Vault</h3>
                <span>ISJ-DMS GLOBAL</span>
            </div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../../dashboard.php" class="nav-item">
            <?php echo icon('dashboard', 'nav-icon'); ?> Dashboard
        </a>
        <a href="../../modules/documents/index.php" class="nav-item">
            <?php echo icon('documents', 'nav-icon'); ?> My Documents
        </a>
        <a href="../../modules/documents/shared.php" class="nav-item">
            <?php echo icon('shared', 'nav-icon'); ?> Shared with Me
        </a>
        <a href="../../tags/index.php" class="nav-item">
            <?php echo icon('tags', 'nav-icon'); ?> Tags
        </a>
        <a href="../../modules/folder-browser/index.php" class="nav-item active">
            <?php echo icon('folder', 'nav-icon'); ?> Folder Browser
        </a>
        <div class="nav-divider"></div>

        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin'): ?>
            <a href="../../usermanagement.php" class="nav-item">
                <?php echo icon('users', 'nav-icon'); ?> User Management
            </a>
        <?php endif; ?>

        <a href="../../modules/users/settings.php" class="nav-item">
            <?php echo icon('settings', 'nav-icon'); ?> Settings
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="../../modules/documents/upload.php" class="btn-upload">
            <?php echo icon('upload', ''); ?> Upload Document
        </a>
        <a href="../../logout.php" class="logout-link">
            <?php echo icon('logout', ''); ?> Logout
        </a>
    </div>
</aside>