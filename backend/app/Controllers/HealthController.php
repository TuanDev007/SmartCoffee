<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** GET /api/health: server chạy và kết nối được database (api-contract đợt 1). */
final class HealthController
{
    public function show(Request $request): void
    {
        try {
            Database::query('SELECT 1');
        } catch (\PDOException $e) {
            error_log('[SmartCoffee] Health check DB error: ' . $e->getMessage());
            throw new HttpException(500, 'SERVER_ERROR', 'Không kết nối được database.');
        }
        Response::json(['status' => 'ok']);
    }
}
