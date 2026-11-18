<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\HttpException;

/** Chặn vai trò không có trong danh sách của route: 403 FORBIDDEN (D-03). */
final class RoleMiddleware
{
    /** @param list<string> $roles */
    public static function handle(array $roles): void
    {
        $role = Auth::role();
        if ($role === null) {
            throw HttpException::unauthenticated();
        }
        if (!in_array($role, $roles, true)) {
            throw HttpException::forbidden();
        }
    }
}
