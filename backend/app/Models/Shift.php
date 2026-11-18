<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Format;

/**
 * Bảng shifts (ca thu ngân). Toàn hệ thống chỉ một ca mở (D-47),
 * database chặn bằng UNIQUE uq_shifts_one_open.
 */
final class Shift
{
    private const COLUMNS = 's.id, s.code, s.cashier_id, s.status, s.opened_at, s.opening_cash, s.closed_at,
                             s.closing_cash_counted, s.expected_cash, s.note,
                             u.full_name AS cashier_name, u.staff_code AS cashier_code';

    public static function current(): ?array
    {
        return Database::fetch('SELECT ' . self::COLUMNS . " FROM shifts s JOIN users u ON u.id = s.cashier_id WHERE s.status = 'open'");
    }

    public static function find(int $id): ?array
    {
        return Database::fetch('SELECT ' . self::COLUMNS . ' FROM shifts s JOIN users u ON u.id = s.cashier_id WHERE s.id = ?', [$id]);
    }

    /** Ca của một thu ngân, lọc theo ngày mở ca (YYYY-MM-DD) nếu có. Mới nhất trước. */
    public static function ofCashier(int $cashierId, ?string $date): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM shifts s JOIN users u ON u.id = s.cashier_id WHERE s.cashier_id = ?';
        $params = [$cashierId];
        if ($date !== null) {
            $sql .= ' AND s.opened_at >= ? AND s.opened_at < ?';
            $params[] = $date . ' 00:00:00';
            $params[] = (new \DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d') . ' 00:00:00';
        }
        return Database::fetchAll($sql . ' ORDER BY s.opened_at DESC', $params);
    }

    /**
     * Mở ca. Mã ca: CA + ngày tháng + số thứ tự trong ngày, ví dụ CA1811-01.
     * Ném PDOException (mã 23000) nếu đã có ca mở.
     */
    public static function open(int $cashierId, int $openingCash): int
    {
        $now = new \DateTimeImmutable();
        $prefix = 'CA' . $now->format('dm') . '-';
        $count = (int) (Database::fetch('SELECT COUNT(*) AS n FROM shifts WHERE code LIKE ?', [$prefix . '%'])['n'] ?? 0);
        $code = $prefix . str_pad((string) ($count + 1), 2, '0', STR_PAD_LEFT);

        Database::query(
            "INSERT INTO shifts (code, cashier_id, status, opened_at, opening_cash) VALUES (?, ?, 'open', ?, ?)",
            [$code, $cashierId, $now->format('Y-m-d H:i:s'), $openingCash]
        );
        return (int) Database::connection()->lastInsertId();
    }

    /** Đóng ca nếu còn mở. Trả false nếu ca đã đóng (có người đóng trước). */
    public static function close(int $id, int $counted, int $expected, ?string $note): bool
    {
        $stmt = Database::query(
            "UPDATE shifts SET status = 'closed', closed_at = ?, closing_cash_counted = ?, expected_cash = ?, note = ?
             WHERE id = ? AND status = 'open'",
            [(new \DateTimeImmutable())->format('Y-m-d H:i:s'), $counted, $expected, $note, $id]
        );
        return $stmt->rowCount() === 1;
    }

    /** Các giao dịch tiền của ca, cũ trước (D-33, D-49). */
    public static function transactions(int $shiftId): array
    {
        return Database::fetchAll(
            'SELECT o.code AS order_code, x.type, x.method, x.amount, x.reason, x.created_at,
                    u.full_name, u.staff_code, u.role
             FROM payment_transactions x
             JOIN orders o ON o.id = x.order_id
             JOIN users u ON u.id = x.handled_by
             WHERE x.shift_id = ?
             ORDER BY x.created_at, x.id',
            [$shiftId]
        );
    }

    /**
     * Tổng tiền của ca. Tiền mặt dự kiến = đầu ca + tiền mặt thu (thu lần đầu, thu thêm) − tiền mặt hoàn.
     *
     * @return array{cash_in:int, cash_refund:int, transfer_in:int, transfer_refund:int, expected_cash:int}
     */
    public static function totals(array $shift, array $transactions): array
    {
        $t = ['cash_in' => 0, 'cash_refund' => 0, 'transfer_in' => 0, 'transfer_refund' => 0];
        foreach ($transactions as $x) {
            $key = ($x['method'] === 'cash' ? 'cash' : 'transfer') . ($x['type'] === 'refund' ? '_refund' : '_in');
            $t[$key] += (int) $x['amount'];
        }
        $t['expected_cash'] = (int) $shift['opening_cash'] + $t['cash_in'] - $t['cash_refund'];
        return $t;
    }

    /** Dạng ngắn (GET /api/shifts/current, POST /api/shifts). */
    public static function present(array $s): array
    {
        return [
            'id'           => (int) $s['id'],
            'code'         => $s['code'],
            'cashier'      => ['id' => (int) $s['cashier_id'], 'full_name' => $s['cashier_name'], 'staff_code' => $s['cashier_code']],
            'opened_at'    => Format::datetime((string) $s['opened_at']),
            'opening_cash' => (int) $s['opening_cash'],
        ];
    }

    /** Dạng danh sách (GET /api/shifts). */
    public static function presentListItem(array $s): array
    {
        return [
            'id'                   => (int) $s['id'],
            'code'                 => $s['code'],
            'status'               => $s['status'],
            'opened_at'            => Format::datetime((string) $s['opened_at']),
            'closed_at'            => Format::datetime($s['closed_at'] === null ? null : (string) $s['closed_at']),
            'opening_cash'         => (int) $s['opening_cash'],
            'closing_cash_counted' => $s['closing_cash_counted'] === null ? null : (int) $s['closing_cash_counted'],
            'expected_cash'        => $s['expected_cash'] === null ? null : (int) $s['expected_cash'],
        ];
    }
}
