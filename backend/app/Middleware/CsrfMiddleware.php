<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;

/** Chặn POST/PATCH/DELETE thiếu hoặc sai X-CSRF-Token: 403 CSRF_INVALID. */
final class CsrfMiddleware
{
    public static function handle(Request $request): void
    {
        if (!Csrf::isValid($request->header('X-CSRF-Token'))) {
            throw new HttpException(403, 'CSRF_INVALID', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang.');
        }
    }
}
