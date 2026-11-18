<?php

declare(strict_types=1);

/*
 * Danh sách endpoint. Chỉ thêm endpoint đã được duyệt trong docs/api-contract.md.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\UserController;

// Đợt 1: Nền
$router->get('/api/health', [HealthController::class, 'show'], ['auth' => false]);

// Đợt 2: Đăng nhập và tài khoản
$router->post('/api/auth/login', [AuthController::class, 'login'], ['auth' => false]);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->get('/api/auth/me', [AuthController::class, 'me']);

$router->get('/api/users', [UserController::class, 'index'], ['roles' => ['admin']]);
$router->post('/api/users', [UserController::class, 'store'], ['roles' => ['admin']]);
$router->patch('/api/users/{id}', [UserController::class, 'update'], ['roles' => ['admin']]);
