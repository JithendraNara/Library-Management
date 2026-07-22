<?php
/**
 * Router for PHP's built-in dev server:
 *   php -S 127.0.0.1:8000 router.php
 *
 * Serves static files from public/ directly, routes everything else to
 * the front controller. (We serve the file ourselves rather than returning
 * false, because the document root is the repo root, not public/.)
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$static = __DIR__ . '/public' . $uri;
if ($uri !== '/' && is_file($static)) {
    $types = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
    ];
    $ext = strtolower(pathinfo($static, PATHINFO_EXTENSION));
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
    }
    readfile($static);
    return true;
}

require __DIR__ . '/public/index.php';
