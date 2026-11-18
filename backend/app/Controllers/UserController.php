<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\User;

/**
 * Admin quản lý tài khoản (api-contract đợt 2; D-04, D-64).
 * Không có đăng ký, không xóa tài khoản, chỉ khóa.
 */
final class UserController
{
    private const USERNAME_PATTERN = '/^[a-z0-9._]+$/';
    private const USERNAME_HINT = 'Chỉ dùng chữ thường không dấu, số, dấu chấm, gạch dưới.';
    private const PASSWORD_MIN = 6;

    /** GET /api/users */
    public function index(Request $request): void
    {
        Response::json(User::all());
    }

    /** POST /api/users */
    public function store(Request $request): void
    {
        $v = new Validator($request->json());
        $data = [
            'username'   => $v->string('username', required: true, min: 3, max: 50, pattern: self::USERNAME_PATTERN, patternMessage: self::USERNAME_HINT),
            'password'   => $v->raw('password', required: true, min: self::PASSWORD_MIN, max: 255),
            'full_name'  => $v->string('full_name', required: true, max: 100),
            'staff_code' => $v->string('staff_code', required: true, max: 20),
            'role'       => $v->enum('role', User::ROLES, required: true),
        ];
        $v->throwIfFailed();

        if (User::usernameTaken($data['username'])) {
            throw HttpException::conflict('USERNAME_TAKEN', 'Tên đăng nhập đã có người dùng.');
        }
        if (User::staffCodeTaken($data['staff_code'])) {
            throw HttpException::conflict('STAFF_CODE_TAKEN', 'Mã nhân viên đã có người dùng.');
        }

        $id = User::create($data);
        Response::json(User::find($id), 201);
    }

    /** PATCH /api/users/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        if (User::find($id) === null) {
            throw HttpException::notFound('Không tìm thấy tài khoản.');
        }

        $v = new Validator($request->json());
        $data = [];
        if ($v->has('full_name')) {
            $data['full_name'] = $v->string('full_name', required: true, max: 100);
        }
        if ($v->has('role')) {
            $data['role'] = $v->enum('role', User::ROLES, required: true);
        }
        if ($v->has('is_active')) {
            $data['is_active'] = $v->bool('is_active', required: true);
        }
        if ($v->has('password')) {
            $data['password'] = $v->raw('password', required: true, min: self::PASSWORD_MIN, max: 255);
        }
        if ($data === []) {
            $v->fail('_', 'Gửi ít nhất một trường: full_name, role, is_active, password.');
        }
        $v->throwIfFailed();

        if (($data['is_active'] ?? true) === false && $id === Auth::id()) {
            throw HttpException::conflict('CANNOT_DISABLE_SELF', 'Không thể tự khóa tài khoản của chính mình.');
        }

        User::update($id, $data);
        Response::json(User::find($id));
    }
}
