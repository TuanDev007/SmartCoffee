<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Bảng users. Không bao giờ trả password_hash ra ngoài, trừ hàm dùng riêng cho đăng nhập.
 */
final class User
{
    public const ROLES = ['admin', 'manager', 'cashier', 'waiter', 'barista'];

    private const PUBLIC_COLUMNS = 'id, username, full_name, staff_code, role, is_active, created_at';

    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users ORDER BY id');
        return array_map([self::class, 'present'], $rows);
    }

    public static function find(int $id): ?array
    {
        $row = Database::fetch('SELECT ' . self::PUBLIC_COLUMNS . ' FROM users WHERE id = ?', [$id]);
        return $row === null ? null : self::present($row);
    }

    /** Dùng riêng cho đăng nhập: có password_hash. */
    public static function findForLogin(string $username): ?array
    {
        return Database::fetch(
            'SELECT id, username, password_hash, full_name, staff_code, role, is_active FROM users WHERE username = ?',
            [$username]
        );
    }

    public static function usernameTaken(string $username, ?int $exceptId = null): bool
    {
        return Database::fetch(
            'SELECT 1 FROM users WHERE username = ? AND id <> ?',
            [$username, $exceptId ?? 0]
        ) !== null;
    }

    public static function staffCodeTaken(string $code, ?int $exceptId = null): bool
    {
        return Database::fetch(
            'SELECT 1 FROM users WHERE staff_code = ? AND id <> ?',
            [$code, $exceptId ?? 0]
        ) !== null;
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO users (username, password_hash, full_name, staff_code, role, is_active) VALUES (?, ?, ?, ?, ?, 1)',
            [$data['username'], password_hash($data['password'], PASSWORD_DEFAULT), $data['full_name'], $data['staff_code'], $data['role']]
        );
        return (int) Database::connection()->lastInsertId();
    }

    /**
     * Cập nhật các trường được gửi. Tên cột lấy từ danh sách cố định, không lấy từ người dùng.
     *
     * @param array{full_name?:string, role?:string, is_active?:bool, password?:string} $data
     */
    public static function update(int $id, array $data): void
    {
        $columns = [
            'full_name'  => 'full_name',
            'role'       => 'role',
            'is_active'  => 'is_active',
            'password'   => 'password_hash',
        ];
        $sets = [];
        $params = [];
        foreach ($columns as $key => $column) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($key === 'password') {
                $value = password_hash($value, PASSWORD_DEFAULT);
            } elseif ($key === 'is_active') {
                $value = $value ? 1 : 0;
            }
            $sets[] = "{$column} = ?";
            $params[] = $value;
        }
        if ($sets === []) {
            return;
        }
        $params[] = $id;
        Database::query('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
    }

    /** Dạng trả cho client: kiểu dữ liệu đúng (số, true/false). */
    public static function present(array $row): array
    {
        $out = [
            'id'         => (int) $row['id'],
            'username'   => $row['username'],
            'full_name'  => $row['full_name'],
            'staff_code' => $row['staff_code'],
            'role'       => $row['role'],
            'is_active'  => (bool) $row['is_active'],
        ];
        if (isset($row['created_at'])) {
            $out['created_at'] = (new \DateTimeImmutable((string) $row['created_at']))->format(DATE_ATOM);
        }
        return $out;
    }

    /** Thông tin người đang đăng nhập trả kèm login và /me (api-contract đợt 2). */
    public static function brief(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'full_name'  => $row['full_name'],
            'staff_code' => $row['staff_code'],
            'role'       => $row['role'],
        ];
    }
}
