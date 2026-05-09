<?php
// config/init.php

// 1. Start Session for Authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. Load Configuration & Constants
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// 3. Set Global Timezone (Optional but recommended for logs)
date_default_timezone_set('Africa/Douala');

// 4. Global helper for Icons (since we use it on almost every page)
if (!function_exists('icon')) {
    function icon($name, $classes = '')
    {
        // Adjust path based on where the file is called from
        // We use absolute path to ensure it works in root or subfolders
        $path = $_SERVER['DOCUMENT_ROOT'] . "/isj-dms1/assets/icons/{$name}.svg";

        if (file_exists($path)) {
            $svg = file_get_contents($path);
            $svg = preg_replace('/<\?xml[^?]*\?>/', '', $svg);
            return "<span class=\"icon {$classes}\">{$svg}</span>";
        }
        return "<span class=\"icon-placeholder\"></span>";
    }
}
