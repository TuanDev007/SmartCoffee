<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Bảng products (món). Trạng thái: available / sold_out / discontinued (D-58). */
final class Product
{
    public const STATUSES = ['available', 'sold_out', 'discontinued'];

    private const COLUMNS = 'id, category_id, name, price, description, image_path, status, has_sweetness, has_bitterness, has_ice';

    /** Món đang hiện trên thực đơn: đang bán và tạm hết, không có món ngừng bán. */
    public static function onMenu(): array
    {
        return array_map([self::class, 'present'], Database::fetchAll(
            'SELECT ' . self::COLUMNS . " FROM products WHERE status IN ('available', 'sold_out') ORDER BY category_id, name"
        ));
    }

    public static function search(?int $categoryId, ?string $status): array
    {
        $where = [];
        $params = [];
        if ($categoryId !== null) {
            $where[] = 'category_id = ?';
            $params[] = $categoryId;
        }
        if ($status !== null) {
            $where[] = 'status = ?';
            $params[] = $status;
        }
        $sql = 'SELECT ' . self::COLUMNS . ' FROM products'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY category_id, name';
        return array_map([self::class, 'present'], Database::fetchAll($sql, $params));
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT ' . self::COLUMNS . ' FROM products WHERE id = ?', [$id]);
        return $row === null ? null : self::present($row);
    }

    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO products (category_id, name, price, description, status, has_sweetness, has_bitterness, has_ice)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['category_id'], $d['name'], $d['price'], $d['description'], 'available',
                $d['has_sweetness'] ? 1 : 0, $d['has_bitterness'] ? 1 : 0, $d['has_ice'] ? 1 : 0,
            ]
        );
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $map = [
            'category_id' => 'category_id', 'name' => 'name', 'price' => 'price', 'description' => 'description',
            'status' => 'status', 'has_sweetness' => 'has_sweetness', 'has_bitterness' => 'has_bitterness', 'has_ice' => 'has_ice',
        ];
        [$sets, $params] = Sql::sets($map, $data);
        if ($sets !== '') {
            $params[] = $id;
            Database::query("UPDATE products SET {$sets} WHERE id = ?", $params);
        }
    }

    public static function present(array $row): array
    {
        return [
            'id'             => (int) $row['id'],
            'category_id'    => (int) $row['category_id'],
            'name'           => $row['name'],
            'price'          => (int) $row['price'],
            'description'    => $row['description'],
            'image_path'     => $row['image_path'],
            'status'         => $row['status'],
            'has_sweetness'  => (bool) $row['has_sweetness'],
            'has_bitterness' => (bool) $row['has_bitterness'],
            'has_ice'        => (bool) $row['has_ice'],
        ];
    }
}
