<?php
// config/config.php

$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $_protocol . '://' . $_host . '/');
define('APP_NAME', 'ISJ-DMS Academic Curator');

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'production' ? 0 : 1);