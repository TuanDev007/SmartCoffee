<?php

declare(strict_types=1);

/*
 * Danh sách endpoint. Chỉ thêm endpoint đã được duyệt trong docs/api-contract.md.
 *
 * @var App\Core\Router $router
 */

use App\Controllers\HealthController;

// Đợt 1: Nền
$router->get('/api/health', [HealthController::class, 'show'], ['auth' => false]);
