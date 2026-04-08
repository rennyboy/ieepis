<?php
/**
 * Laravel Development Server Router
 * Bypasses Artisan to avoid DOM extension issues
 * Usage: php -S localhost:8001 run-server.php
 */

$publicPath = __DIR__ . '/public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Handle static files
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    return false;
}

// Route everything else through index.php
require_once $publicPath . '/index.php';
