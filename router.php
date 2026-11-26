<?php
// Routeur de développement local (Windows et Linux).
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($path) && str_starts_with($path, '/assets/')) {
    $root = realpath(__DIR__ . '/public/assets');
    $file = realpath(__DIR__ . '/public' . $path);
    $types = ['css' => 'text/css; charset=UTF-8', 'js' => 'application/javascript; charset=UTF-8', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'ico' => 'image/x-icon', 'woff2' => 'font/woff2'];
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($root === false || $file === false || !is_file($file) || !isset($types[$extension]) || !str_starts_with(str_replace('\\', '/', $file), rtrim(str_replace('\\', '/', $root), '/') . '/')) {
        http_response_code(404);
        exit('Ressource introuvable.');
    }
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        http_response_code(405);
        exit;
    }
    header('Content-Type: ' . $types[$extension]);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') readfile($file);
    exit;
}
require __DIR__ . '/public/index.php';
