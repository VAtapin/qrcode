<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\PublicController;

$router->get('/', [PublicController::class, 'create']);
$router->post('/links', [PublicController::class, 'store']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/storage/qrcodes/{file}', [PublicController::class, 'qrImage']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/pending', [AdminController::class, 'listPending']);
$router->get('/admin/approved', [AdminController::class, 'listApproved']);
$router->get('/admin/rejected', [AdminController::class, 'listRejected']);
$router->get('/admin/blocked', [AdminController::class, 'listBlocked']);
$router->get('/admin/edit/{id}', [AdminController::class, 'edit']);
$router->post('/admin/edit/{id}', [AdminController::class, 'update']);
$router->post('/admin/status/{id}', [AdminController::class, 'status']);
$router->post('/admin/delete/{id}', [AdminController::class, 'delete']);
$router->get('/{code}', [PublicController::class, 'redirect']);
