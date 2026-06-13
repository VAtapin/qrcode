<?php

declare(strict_types=1);

/**
 * Q to me - moderated short link and QR code service.
 *
 * @author Atapin Vladimir <atapin@gmail.com>
 * @link https://bible-media.de/
 * @copyright 2026 Atapin Vladimir / Bible Media
 * @version 1.0.0
 */

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;

$publicRoutes = [
    '' => [PublicController::class, 'gallery'],
    'new' => [PublicController::class, 'create'],
    'create' => [PublicController::class, 'create'],
    'impressum' => [PublicController::class, 'impressum'],
    'agb' => [PublicController::class, 'agb'],
    'datenschutz' => [PublicController::class, 'datenschutz'],
];

foreach ($publicRoutes as $path => $handler) {
    $router->get('/' . $path, $handler);
    foreach (supported_locales() as $locale) {
        $router->get('/' . $locale . ($path !== '' ? '/' . $path : ''), $handler);
    }
}

$router->post('/links', [PublicController::class, 'store']);
$router->get('/result/{code}', [PublicController::class, 'result']);
$router->get('/qr/{code}', [PublicController::class, 'qrPage']);
$router->get('/qr/{code}/download', [PublicController::class, 'downloadQr']);
foreach (supported_locales() as $locale) {
    $router->get('/' . $locale . '/result/{code}', [PublicController::class, 'result']);
    $router->get('/' . $locale . '/qr/{code}', [PublicController::class, 'qrPage']);
    $router->get('/' . $locale . '/qr/{code}/download', [PublicController::class, 'downloadQr']);
}
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/storage/qrcodes/{file}', [PublicController::class, 'qrImage']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/pending', [AdminController::class, 'listPending']);
$router->get('/admin/approved', [AdminController::class, 'listApproved']);
$router->get('/admin/rejected', [AdminController::class, 'listRejected']);
$router->get('/admin/blocked', [AdminController::class, 'listBlocked']);
$router->get('/admin/blacklist', [AdminController::class, 'blacklist']);
$router->post('/admin/blacklist', [AdminController::class, 'blacklistAdd']);
$router->post('/admin/blacklist/delete/{id}', [AdminController::class, 'blacklistDelete']);
$router->get('/admin/settings', [AdminController::class, 'settings']);
$router->post('/admin/settings', [AdminController::class, 'settingsUpdate']);
$router->get('/admin/edit/{id}', [AdminController::class, 'edit']);
$router->post('/admin/edit/{id}', [AdminController::class, 'update']);
$router->post('/admin/status/{id}', [AdminController::class, 'status']);
$router->post('/admin/delete/{id}', [AdminController::class, 'delete']);
$router->get('/{code}', [PublicController::class, 'redirect']);
