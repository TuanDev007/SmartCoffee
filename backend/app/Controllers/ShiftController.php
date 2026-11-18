<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Format;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Shift;

/** Ca thu ngân (api-contract đợt 4; D-32, D-33, D-37, D-38, D-47, D-49). */
final class ShiftController
{
    /** GET /api/shifts/current: ca đang mở hoặc null */
    public function current(Request $request): void
    {
        $shift = Shift::current();
        Response::json($shift === null ? null : Shift::present($shift));
    }

    /** POST /api/shifts: thu ngân tự mở ca */
    public function open(Request $request): void
    {
        $v = new Validator($request->json());
        $openingCash = $v->int('opening_cash', required: true, min: 0, max: 1000000000);
        $v->throwIfFailed();

        $existing = Shift::current();
        if ($existing !== null) {
            throw $this->alreadyOpen($existing);
        }
        try {
            $id = Shift::open((int) Auth::id(), $openingCash);
        } catch (\PDOException $e) {
            // Hai người bấm mở ca cùng lúc: database chặn ca thứ hai
            if ($e->getCode() === '23000' && ($existing = Shift::current()) !== null) {
                throw $this->alreadyOpen($existing);
            }
            throw $e;
        }
        Response::json(Shift::present(Shift::find($id)), 201);
    }

    /** GET /api/shifts?date=YYYY-MM-DD: chỉ ca của chính thu ngân này */
    public function index(Request $request): void
    {
        $date = null;
        if (isset($request->query['date']) && $request->query['date'] !== '') {
            $date = (string) $request->query['date'];
            $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if ($d === false || $d->format('Y-m-d') !== $date) {
                throw HttpException::validation(['date' => 'Ngày theo dạng YYYY-MM-DD, ví dụ 2026-11-16.']);
            }
        }
        Response::json(array_map([Shift::class, 'presentListItem'], Shift::ofCashier((int) Auth::id(), $date)));
    }

    /** GET /api/shifts/{id}/summary */
    public function summary(Request $request): void
    {
        Response::json($this->buildSummary($this->ownShift($request)));
    }

    /** POST /api/shifts/{id}/close */
    public function close(Request $request): void
    {
        $shift = $this->ownShift($request);

        $v = new Validator($request->json());
        $counted = $v->int('closing_cash_counted', required: true, min: 0, max: 1000000000);
        $note = $v->optionalString('note', 255);
        $v->throwIfFailed();

        if ($shift['status'] !== 'open') {
            throw HttpException::conflict('SHIFT_NOT_OPEN', 'Ca này đã kết.');
        }

        $closed = Database::transaction(function () use ($shift, $counted, $note): bool {
            $totals = Shift::totals($shift, Shift::transactions((int) $shift['id']));
            return Shift::close((int) $shift['id'], $counted, max(0, $totals['expected_cash']), $note);
        });
        if (!$closed) {
            throw HttpException::conflict('SHIFT_NOT_OPEN', 'Ca này đã kết.');
        }
        Response::json($this->buildSummary(Shift::find((int) $shift['id'])));
    }

    /** Ca phải tồn tại và thuộc thu ngân đang đăng nhập (D-32). */
    private function ownShift(Request $request): array
    {
        $shift = Shift::find((int) $request->param('id'));
        if ($shift === null) {
            throw HttpException::notFound('Không tìm thấy ca.');
        }
        if ((int) $shift['cashier_id'] !== Auth::id()) {
            throw HttpException::forbidden('Đây không phải ca của bạn.');
        }
        return $shift;
    }

    private function buildSummary(array $shift): array
    {
        $transactions = Shift::transactions((int) $shift['id']);
        $totals = Shift::totals($shift, $transactions);
        $closed = $shift['status'] === 'closed';
        // Ca đã kết: dùng số đã chốt lúc kết ca, không tính lại
        $expected = $closed ? (int) $shift['expected_cash'] : $totals['expected_cash'];
        $counted = $shift['closing_cash_counted'] === null ? null : (int) $shift['closing_cash_counted'];

        return [
            'shift' => [
                'id'        => (int) $shift['id'],
                'code'      => $shift['code'],
                'status'    => $shift['status'],
                'opened_at' => Format::datetime((string) $shift['opened_at']),
                'closed_at' => Format::datetime($shift['closed_at'] === null ? null : (string) $shift['closed_at']),
            ],
            'opening_cash'         => (int) $shift['opening_cash'],
            'cash_in'              => $totals['cash_in'],
            'cash_refund'          => $totals['cash_refund'],
            'transfer_in'          => $totals['transfer_in'],
            'transfer_refund'      => $totals['transfer_refund'],
            'expected_cash'        => $expected,
            'closing_cash_counted' => $counted,
            'difference'           => $counted === null ? null : $counted - $expected,
            'transactions'         => array_map(static fn (array $x): array => [
                'order_code' => $x['order_code'],
                'type'       => $x['type'],
                'method'     => $x['method'],
                'amount'     => (int) $x['amount'],
                'handled_by' => ['full_name' => $x['full_name'], 'staff_code' => $x['staff_code'], 'role' => $x['role']],
                'reason'     => $x['reason'],
                'created_at' => Format::datetime((string) $x['created_at']),
            ], $transactions),
        ];
    }

    private function alreadyOpen(array $shift): HttpException
    {
        return HttpException::conflict(
            'SHIFT_ALREADY_OPEN',
            'Đang có ca mở của thu ngân ' . $shift['cashier_name'] . '.',
            ['shift' => Shift::present($shift)]
        );
    }
}
