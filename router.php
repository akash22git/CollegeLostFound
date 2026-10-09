<?php
/**
 * Local Development Router for PHP Built-in Web Server
 * 
 * Enables clean URLs locally (matching Apache .htaccess behavior)
 * 
 * Usage:
 *   php -S localhost:8000 router.php
 * or
 *   php -S localhost:8000 -t public router.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = urldecode($uri);

$publicDir = is_dir(__DIR__ . '/public') ? __DIR__ . '/public' : __DIR__;

// 1. Root index
if ($path === '/' || $path === '') {
    require $publicDir . '/index.php';
    exit;
}

// Normalize aliases (e.g. /registration -> /register)
$cleanPath = rtrim($path, '/');
if ($cleanPath === '/registration' || $cleanPath === '/registration.php') {
    $cleanPath = '/register';
    $path = '/register';
}

$targetFile = $publicDir . $path;

// 2. Direct PHP file request (e.g. /login.php, /register.php)
if (is_file($targetFile) && str_ends_with($targetFile, '.php')) {
    require $targetFile;
    exit;
}

// 3. Clean URL matching a PHP file (e.g. /login -> /public/login.php, /register -> /public/register.php)
if (is_file($targetFile . '.php')) {
    require $targetFile . '.php';
    exit;
}

// 3b. Clean alias with .php
if (is_file($publicDir . $cleanPath . '.php')) {
    require $publicDir . $cleanPath . '.php';
    exit;
}

// 4. Directory with index.php (e.g. /admin -> /public/admin/dashboard.php or index.php)
if (is_dir($targetFile)) {
    if (is_file($targetFile . '/dashboard.php')) {
        require $targetFile . '/dashboard.php';
        exit;
    }
    if (is_file($targetFile . '/index.php')) {
        require $targetFile . '/index.php';
        exit;
    }
}

// 5. Static file handling (CSS, JS, images, fonts, icons)
if (is_file($targetFile)) {
    // If the server root matches public directory, let PHP built-in server handle it
    if (realpath($_SERVER['DOCUMENT_ROOT'] ?? '') === realpath($publicDir)) {
        return false;
    }

    // Otherwise, stream static asset with correct content-type header
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf'
    ];

    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $contentType = $mimeTypes[$ext] ?? mime_content_type($targetFile) ?: 'application/octet-stream';

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . filesize($targetFile));
    readfile($targetFile);
    exit;
}

// 6. Not found
http_response_code(404);
echo "404 Not Found";
