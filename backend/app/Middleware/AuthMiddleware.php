<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;

/** Chặn request chưa đăng nhập: 401 AUTH_REQUIRED. */
final class AuthMiddleware
{
    public static function handle(): void
    {
        if (Auth::user() === null) {
            throw HttpException::unauthenticated();
        }
    }
}
