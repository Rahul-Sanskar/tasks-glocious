<?php
/**
 * PHP built-in server router.
 * Enables clean URL rewriting (ENABLE_URL_REWRITE) without Apache/nginx.
 *
 * Usage:
 *   php -S localhost:8080 router.php
 */

// Serve real static files (CSS, JS, images, fonts) directly
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false; // let PHP serve the file as-is
}

// Tell Kanboard that URL rewriting is active
$_SERVER['HTTP_MOD_REWRITE'] = 'On';

// Route everything else through index.php
require __DIR__ . '/index.php';
