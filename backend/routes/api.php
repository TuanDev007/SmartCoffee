<?php

declare(strict_types=1);

/*
 * Danh sách endpoint. Chỉ thêm endpoint đã được duyệt trong docs/api-contract.md.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\CategoryController;
use App\Controllers\HealthController;
use App\Controllers\MenuController;
use App\Controllers\ProductController;
use App\Controllers\ShiftController;
use App\Controllers\TableController;
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

// Đợt 3: Thực đơn và bàn
$router->get('/api/menu', [MenuController::class, 'index'], ['roles' => ['waiter', 'cashier', 'barista', 'manager']]);

$router->get('/api/categories', [CategoryController::class, 'index'], ['roles' => ['manager']]);
$router->post('/api/categories', [CategoryController::class, 'store'], ['roles' => ['manager']]);
$router->patch('/api/categories/{id}', [CategoryController::class, 'update'], ['roles' => ['manager']]);

$router->get('/api/products', [ProductController::class, 'index'], ['roles' => ['manager']]);
$router->post('/api/products', [ProductController::class, 'store'], ['roles' => ['manager']]);
$router->patch('/api/products/{id}', [ProductController::class, 'update'], ['roles' => ['manager']]);

$router->get('/api/tables', [TableController::class, 'index'], ['roles' => ['waiter', 'manager']]);
$router->post('/api/table-areas', [TableController::class, 'storeArea'], ['roles' => ['manager']]);
$router->patch('/api/table-areas/{id}', [TableController::class, 'updateArea'], ['roles' => ['manager']]);
$router->post('/api/tables', [TableController::class, 'store'], ['roles' => ['manager']]);
$router->patch('/api/tables/{id}', [TableController::class, 'update'], ['roles' => ['manager']]);

// Đợt 4: Ca thu ngân
$router->get('/api/shifts/current', [ShiftController::class, 'current'], ['roles' => ['manager', 'cashier', 'waiter', 'barista']]);
$router->post('/api/shifts', [ShiftController::class, 'open'], ['roles' => ['cashier']]);
$router->get('/api/shifts', [ShiftController::class, 'index'], ['roles' => ['cashier']]);
$router->get('/api/shifts/{id}/summary', [ShiftController::class, 'summary'], ['roles' => ['cashier']]);
$router->post('/api/shifts/{id}/close', [ShiftController::class, 'close'], ['roles' => ['cashier']]);
