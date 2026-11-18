<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Mã chống CSRF gắn với session. Frontend gửi lại qua header X-CSRF-Token
 * với mọi request POST, PATCH, DELETE (api-contract mục 1.1).
 */
final class Csrf
{
    private const KEY = 'csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || $token === '') {
            $token = self::rotate();
        }
        return $token;
    }

    public static function rotate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::KEY, $token);
        return $token;
    }

    public static function isValid(?string $given): bool
    {
        $expected = Session::get(self::KEY);
        return is_string($expected) && $expected !== '' && is_string($given) && hash_equals($expected, $given);
    }
}
