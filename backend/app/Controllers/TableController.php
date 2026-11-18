<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\DiningTable;
use App\Models\TableArea;

/** Sơ đồ bàn, khu vực và bàn (api-contract đợt 3; D-10, D-31, D-41, D-64). */
final class TableController
{
    /** GET /api/tables?area_id= : khu vực kèm bàn đang bật và trạng thái Trống / Có khách */
    public function index(Request $request): void
    {
        $areaId = null;
        if (isset($request->query['area_id']) && $request->query['area_id'] !== '') {
            $raw = (string) $request->query['area_id'];
            if (!ctype_digit($raw)) {
                throw HttpException::validation(['area_id' => 'Phải là số nguyên.']);
            }
            $areaId = (int) $raw;
        }

        $byArea = [];
        foreach (DiningTable::activeWithSessions($areaId) as $row) {
            $byArea[(int) $row['area_id']][] = DiningTable::presentOnMap($row);
        }
        $out = [];
        foreach (TableArea::all($areaId) as $area) {
            $out[] = ['id' => (int) $area['id'], 'name' => $area['name'], 'tables' => $byArea[(int) $area['id']] ?? []];
        }
        Response::json($out);
    }

    /** POST /api/table-areas */
    public function storeArea(Request $request): void
    {
        $v = new Validator($request->json());
        $name = $v->string('name', required: true, max: 50);
        $sort = $v->int('sort_order', min: 0, max: 32767) ?? 0;
        $v->throwIfFailed();
        if (TableArea::nameTaken($name)) {
            throw HttpException::conflict('NAME_TAKEN', 'Tên khu vực đã có.');
        }
        Response::json(TableArea::find(TableArea::create($name, $sort)), 201);
    }

    /** PATCH /api/table-areas/{id} */
    public function updateArea(Request $request): void
    {
        $id = (int) $request->param('id');
        if (TableArea::find($id) === null) {
            throw HttpException::notFound('Không tìm thấy khu vực.');
        }
        $v = new Validator($request->json());
        $data = [];
        if ($v->has('name')) {
            $data['name'] = $v->string('name', required: true, max: 50);
        }
        if ($v->has('sort_order')) {
            $data['sort_order'] = $v->int('sort_order', required: true, min: 0, max: 32767);
        }
        if ($data === []) {
            $v->fail('_', 'Gửi ít nhất một trường: name, sort_order.');
        }
        $v->throwIfFailed();
        if (isset($data['name']) && TableArea::nameTaken($data['name'], $id)) {
            throw HttpException::conflict('NAME_TAKEN', 'Tên khu vực đã có.');
        }
        TableArea::update($id, $data);
        Response::json(TableArea::find($id));
    }

    /** POST /api/tables */
    public function store(Request $request): void
    {
        $v = new Validator($request->json());
        $areaId = $v->int('area_id', required: true, min: 1);
        $code = $v->string('code', required: true, max: 10);
        $sort = $v->int('sort_order', min: 0, max: 32767) ?? 0;
        if ($areaId !== null && TableArea::find($areaId) === null) {
            $v->fail('area_id', 'Khu vực không tồn tại.');
        }
        $v->throwIfFailed();
        if (DiningTable::codeTaken($code)) {
            throw HttpException::conflict('CODE_TAKEN', 'Mã bàn đã có.');
        }
        Response::json(DiningTable::find(DiningTable::create($areaId, $code, $sort)), 201);
    }

    /** PATCH /api/tables/{id} */
    public function update(Request $request): void
    {
        $id = (int) $request->param('id');
        if (DiningTable::find($id) === null) {
            throw HttpException::notFound('Không tìm thấy bàn.');
        }
        $v = new Validator($request->json());
        $data = [];
        if ($v->has('area_id')) {
            $data['area_id'] = $v->int('area_id', required: true, min: 1);
            if ($data['area_id'] !== null && TableArea::find($data['area_id']) === null) {
                $v->fail('area_id', 'Khu vực không tồn tại.');
            }
        }
        if ($v->has('code')) {
            $data['code'] = $v->string('code', required: true, max: 10);
        }
        if ($v->has('sort_order')) {
            $data['sort_order'] = $v->int('sort_order', required: true, min: 0, max: 32767);
        }
        if ($v->has('is_active')) {
            $data['is_active'] = $v->bool('is_active', required: true);
        }
        if ($data === []) {
            $v->fail('_', 'Gửi ít nhất một trường: area_id, code, sort_order, is_active.');
        }
        $v->throwIfFailed();

        if (isset($data['code']) && DiningTable::codeTaken($data['code'], $id)) {
            throw HttpException::conflict('CODE_TAKEN', 'Mã bàn đã có.');
        }
        if (($data['is_active'] ?? true) === false && DiningTable::isOccupied($id)) {
            throw HttpException::conflict('TABLE_OCCUPIED', 'Bàn đang có khách, chưa tắt được.');
        }
        DiningTable::update($id, $data);
        Response::json(DiningTable::find($id));
    }
}
