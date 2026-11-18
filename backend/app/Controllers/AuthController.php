<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

/** Đăng nhập, đăng xuất, người đang đăng nhập (api-contract đợt 2; D-02, D-64). */
final class AuthController
{
    /** Hash giả để thời gian kiểm tra như nhau dù tài khoản có tồn tại hay không. */
    private const DUMMY_HASH = '$2y$10$V5hm5ucYnDKEvYuzFFFLSOkCCh7RI.wg50ngtVgGBMtxrKNvHVxDm';

    /** POST /api/auth/login */
    public function login(Request $request): void
    {
        $v = new Validator($request->json());
        $username = $v->string('username', required: true, max: 50);
        $password = $v->raw('password', required: true, max: 255);
        $v->throwIfFailed();

        $user = User::findForLogin((string) $username);
        $ok = password_verify((string) $password, $user['password_hash'] ?? self::DUMMY_HASH);

        // Sai tên, sai mật khẩu, tài khoản khóa: cùng một thông báo
        if ($user === null || !$ok || !(bool) $user['is_active']) {
            throw new HttpException(401, 'LOGIN_FAILED', 'Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::update((int) $user['id'], ['password' => (string) $password]);
        }

        Auth::login($user);
        Response::json(['user' => User::brief($user), 'csrf_token' => Csrf::token()]);
    }

    /** POST /api/auth/logout */
    public function logout(Request $request): void
    {
        Auth::logout();
        Response::json(null);
    }

    /** GET /api/auth/me */
    public function me(Request $request): void
    {
        Response::json(['user' => User::brief(Auth::user()), 'csrf_token' => Csrf::token()]);
    }
}
