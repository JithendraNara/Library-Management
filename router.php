<?php
/**
 * Router for PHP's built-in dev server:
 *   php -S 127.0.0.1:8000 -t public router.php
 *
 * Serves static files directly, routes everything else to index.php.
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

// Serve existing static files (css, images) as-is.
if ($uri !== '/' && is_file(__DIR__ . '/public' . $uri)) {
    return false;
}

require __DIR__ . '/public/index.php';
