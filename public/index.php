<?php

declare(strict_types=1);

use App\Core\Router;

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once dirname(__DIR__) . '/app/bootstrap.php';

$router = new Router();
require dirname(__DIR__) . '/routes/web.php';

$path = $_SERVER['REDIRECT_APP_REQUEST_PATH']
    ?? $_SERVER['APP_REQUEST_PATH']
    ?? $_SERVER['REDIRECT_URL']
    ?? parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)
    ?: '/';
$path = '/' . ltrim((string) $path, '/');
$path = preg_replace('#^/public/index\.php#', '', $path) ?: '/';
$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
