<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Bảng table_areas (khu vực: Tầng trệt, Sân vườn, Lầu 1). */
final class TableArea
{
    public static function all(?int $onlyId = null): array
    {
        $sql = 'SELECT id, name, sort_order FROM table_areas' . ($onlyId !== null ? ' WHERE id = ?' : '') . ' ORDER BY sort_order, id';
        return Database::fetchAll($sql, $onlyId !== null ? [$onlyId] : []);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT id, name, sort_order FROM table_areas WHERE id = ?', [$id]);
        return $row === null ? null : self::present($row);
    }

    public static function nameTaken(string $name, int $exceptId = 0): bool
    {
        return Database::fetch('SELECT 1 FROM table_areas WHERE name = ? AND id <> ?', [$name, $exceptId]) !== null;
    }

    public static function create(string $name, int $sortOrder): int
    {
        Database::query('INSERT INTO table_areas (name, sort_order) VALUES (?, ?)', [$name, $sortOrder]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        [$sets, $params] = Sql::sets(['name' => 'name', 'sort_order' => 'sort_order'], $data);
        if ($sets !== '') {
            $params[] = $id;
            Database::query("UPDATE table_areas SET {$sets} WHERE id = ?", $params);
        }
    }

    public static function present(array $row): array
    {
        return ['id' => (int) $row['id'], 'name' => $row['name'], 'sort_order' => (int) $row['sort_order']];
    }
}
