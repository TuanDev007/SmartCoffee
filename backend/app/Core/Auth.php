<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Người đang đăng nhập. Mỗi request đọc lại tài khoản từ database,
 * nên tài khoản bị khóa (is_active = 0) mất quyền ngay ở request kế tiếp.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $loaded = false;

    public static function user(): ?array
    {
        if (self::$loaded) {
            return self::$user;
        }
        self::$loaded = true;

        $id = Session::get('user_id');
        if (!is_int($id)) {
            return null;
        }
        $user = Database::fetch(
            'SELECT id, username, full_name, staff_code, role, is_active FROM users WHERE id = ?',
            [$id]
        );
        if ($user === null || !(bool) $user['is_active']) {
            Session::destroy();
            return null;
        }
        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u === null ? null : (int) $u['id'];
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /** Lưu người dùng vào session sau khi đăng nhập đúng (dùng ở đợt 2). */
    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id', (int) $user['id']);
        Csrf::rotate();
        self::$user = $user;
        self::$loaded = true;
    }

    public static function logout(): void
    {
        Session::destroy();
        self::$user = null;
        self::$loaded = true;
    }
}
