<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Category;
use App\Models\Product;

/** Manager quản lý món (api-contract đợt 3; D-58, D-64). */
final class ProductController
{
    /** GET /api/products?category_id=&status= : kể cả món ngừng bán */
    public function index(Request $request): void
    {
        $fields = [];
        $categoryId = null;
        if (isset($request->query['category_id']) && $request->query['category_id'] !== '') {
            $raw = (string) $request->query['category_id'];
            if (!ctype_digit($raw)) {
                $fields['category_id'] = 'Phải là số nguyên.';
            } else {
                $categoryId = (int) $raw;
            }
        }
        $status = null;
        if (isset($request->query['status']) && $request->query['status'] !== '') {
            $status = (string) $request->query['status'];
            if (!in_array($status, Product::STATUSES, true)) {
                $fields['status'] = 'Chỉ nhận: ' . implode(', ', Product::STATUSES) . '.';
            }
        }
        if ($fields !== []) {
            throw HttpException::validation($fields);
        }
        Response::json(Product::search($categoryId, $status));
    }

    /** POST /api/products */
    public function store(Request $request): void
    {
        $v = new Validator($request->json());
        $data = [
            'category_id'    => $v->int('category_id', required: true, min: 1),
            'name'           => $v->string('name', required: true, max: 100),
            'price'          => $v->int('price', required: true, min: 1, max: 100000000),
            'description'    => $v->optionalString('description', 255),
            'has_sweetness'  => $v->bool('has_sweetness') ?? true,
            'has_bitterness' => $v->bool('has_bitterness') ?? true,
            'has_ice'        => $v->bool('has_ice') ?? true,
        ];
        if ($data['category_id'] !== null && Category::find($data['category_id']) === null) {
            $v->fail('category_id', 'Danh mục không tồn tại.');
        }
        $v->throwIfFailed();

        Response::json(Product::find(Product::create($data)), 201);
    }

    /** PATCH /api/products/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        if (Product::find($id) === null) {
            throw HttpException::notFound('Không tìm thấy món.');
        }
        $v = new Validator($request->json());
        $data = [];
        if ($v->has('category_id')) {
            $data['category_id'] = $v->int('category_id', required: true, min: 1);
            if ($data['category_id'] !== null && Category::find($data['category_id']) === null) {
                $v->fail('category_id', 'Danh mục không tồn tại.');
            }
        }
        if ($v->has('name')) {
            $data['name'] = $v->string('name', required: true, max: 100);
        }
        if ($v->has('price')) {
            $data['price'] = $v->int('price', required: true, min: 1, max: 100000000);
        }
        if ($v->has('description')) {
            $data['description'] = $v->optionalString('description', 255);
        }
        if ($v->has('status')) {
            $data['status'] = $v->enum('status', Product::STATUSES, required: true);
        }
        foreach (['has_sweetness', 'has_bitterness', 'has_ice'] as $flag) {
            if ($v->has($flag)) {
                $data[$flag] = $v->bool($flag, required: true);
            }
        }
        if ($data === []) {
            $v->fail('_', 'Gửi ít nhất một trường: category_id, name, price, description, status, has_sweetness, has_bitterness, has_ice.');
        }
        $v->throwIfFailed();

        Product::update($id, $data);
        Response::json(Product::find($id));
    }
}
