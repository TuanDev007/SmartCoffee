<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;

/** Manager quản lý danh mục (api-contract đợt 3; D-64). */
final class CategoryController
{
    /** GET /api/categories: kể cả danh mục đã tắt */
    public function index(Request $request): void
    {
        Response::json(Category::all());
    }

    /** POST /api/categories */
    public function store(Request $request): void
    {
        $v = new Validator($request->json());
        $name = $v->string('name', required: true, max: 100);
        $sort = $v->int('sort_order', min: 0, max: 32767) ?? 0;
        $v->throwIfFailed();

        if (Category::nameTaken($name)) {
            throw HttpException::conflict('NAME_TAKEN', 'Tên danh mục đã có.');
        }
        Response::json(Category::find(Category::create($name, $sort)), 201);
    }

    /** PATCH /api/categories/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        if (Category::find($id) === null) {
            throw HttpException::notFound('Không tìm thấy danh mục.');
        }
        $v = new Validator($request->json());
        $data = [];
        if ($v->has('name')) {
            $data['name'] = $v->string('name', required: true, max: 100);
        }
        if ($v->has('sort_order')) {
            $data['sort_order'] = $v->int('sort_order', required: true, min: 0, max: 32767);
        }
        if ($v->has('is_active')) {
            $data['is_active'] = $v->bool('is_active', required: true);
        }
        if ($data === []) {
            $v->fail('_', 'Gửi ít nhất một trường: name, sort_order, is_active.');
        }
        $v->throwIfFailed();

        if (isset($data['name']) && Category::nameTaken($data['name'], $id)) {
            throw HttpException::conflict('NAME_TAKEN', 'Tên danh mục đã có.');
        }
        Category::update($id, $data);
        Response::json(Category::find($id));
    }
}
