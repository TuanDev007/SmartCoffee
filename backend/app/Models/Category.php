<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Bảng categories (danh mục món). */
final class Category
{
    public static function all(): array
    {
        return array_map([self::class, 'present'], Database::fetchAll(
            'SELECT id, name, sort_order, is_active FROM categories ORDER BY sort_order, id'
        ));
    }

    public static function active(): array
    {
        return array_map([self::class, 'present'], Database::fetchAll(
            'SELECT id, name, sort_order, is_active FROM categories WHERE is_active = 1 ORDER BY sort_order, id'
        ));
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT id, name, sort_order, is_active FROM categories WHERE id = ?', [$id]);
        return $row === null ? null : self::present($row);
    }

    public static function nameTaken(string $name, int $exceptId = 0): bool
    {
        return Database::fetch('SELECT 1 FROM categories WHERE name = ? AND id <> ?', [$name, $exceptId]) !== null;
    }

    public static function create(string $name, int $sortOrder): int
    {
        Database::query('INSERT INTO categories (name, sort_order, is_active) VALUES (?, ?, 1)', [$name, $sortOrder]);
        return (int) Database::connection()->lastInsertId();
    }

    /** @param array{name?:string, sort_order?:int, is_active?:bool} $data */
    public static function update(int $id, array $data): void
    {
        $map = ['name' => 'name', 'sort_order' => 'sort_order', 'is_active' => 'is_active'];
        [$sets, $params] = Sql::sets($map, $data);
        if ($sets !== '') {
            $params[] = $id;
            Database::query("UPDATE categories SET {$sets} WHERE id = ?", $params);
        }
    }

    public static function present(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'name'       => $row['name'],
            'sort_order' => (int) $row['sort_order'],
            'is_active'  => (bool) $row['is_active'],
        ];
    }
}
