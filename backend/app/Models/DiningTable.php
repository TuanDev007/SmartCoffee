<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Format;

/**
 * Bảng dining_tables. Trạng thái không lưu trong bảng: bàn "Có khách" khi có
 * lượt khách đang mở trong table_sessions (ended_at IS NULL) (D-31, D-41).
 */
final class DiningTable
{
    /**
     * Bàn đang bật kèm lượt khách đang mở (nếu có) và số đơn của lượt đó.
     * Số đơn không tính đơn đã hủy.
     */
    public static function activeWithSessions(?int $areaId = null): array
    {
        $sql = "SELECT t.id, t.area_id, t.code, s.id AS session_id, s.started_at,
                       (SELECT COUNT(*) FROM orders o WHERE o.table_session_id = s.id AND o.status = 'paid') AS order_count
                FROM dining_tables t
                LEFT JOIN table_sessions s ON s.table_id = t.id AND s.ended_at IS NULL
                WHERE t.is_active = 1" . ($areaId !== null ? ' AND t.area_id = ?' : '') . '
                ORDER BY t.sort_order, t.code';
        return Database::fetchAll($sql, $areaId !== null ? [$areaId] : []);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT id, area_id, code, sort_order, is_active FROM dining_tables WHERE id = ?', [$id]);
        return $row === null ? null : self::present($row);
    }

    public static function codeTaken(string $code, int $exceptId = 0): bool
    {
        return Database::fetch('SELECT 1 FROM dining_tables WHERE code = ? AND id <> ?', [$code, $exceptId]) !== null;
    }

    public static function isOccupied(int $id): bool
    {
        return Database::fetch('SELECT 1 FROM table_sessions WHERE table_id = ? AND ended_at IS NULL', [$id]) !== null;
    }

    public static function create(int $areaId, string $code, int $sortOrder): int
    {
        Database::query('INSERT INTO dining_tables (area_id, code, sort_order, is_active) VALUES (?, ?, ?, 1)', [$areaId, $code, $sortOrder]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $map = ['area_id' => 'area_id', 'code' => 'code', 'sort_order' => 'sort_order', 'is_active' => 'is_active'];
        [$sets, $params] = Sql::sets($map, $data);
        if ($sets !== '') {
            $params[] = $id;
            Database::query("UPDATE dining_tables SET {$sets} WHERE id = ?", $params);
        }
    }

    public static function present(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'area_id'    => (int) $row['area_id'],
            'code'       => $row['code'],
            'sort_order' => (int) $row['sort_order'],
            'is_active'  => (bool) $row['is_active'],
        ];
    }

    /** Dạng trên sơ đồ bàn (api-contract GET /api/tables). */
    public static function presentOnMap(array $row): array
    {
        $occupied = $row['session_id'] !== null;
        return [
            'id'      => (int) $row['id'],
            'code'    => $row['code'],
            'status'  => $occupied ? 'occupied' : 'empty',
            'session' => $occupied ? [
                'id'          => (int) $row['session_id'],
                'started_at'  => Format::datetime((string) $row['started_at']),
                'order_count' => (int) $row['order_count'],
            ] : null,
        ];
    }
}
