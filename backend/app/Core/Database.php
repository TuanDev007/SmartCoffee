<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Một kết nối PDO dùng chung cho cả request. Chỉ dùng prepared statements.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $db = Config::get('db');
        $pdo = new PDO($db['dsn'], $db['username'] ?? null, $db['password'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_STRINGIFY_FETCHES  => false,
        ]);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            // Giờ trong database khớp giờ Việt Nam
            $pdo->exec("SET time_zone = '+07:00'");
        }

        return self::$pdo = $pdo;
    }

    /** Chạy câu lệnh có tham số, trả về PDOStatement. */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Chạy $work trong một transaction. Lỗi ở đâu thì hủy toàn bộ, không lưu gì.
     *
     * @template T
     * @param callable(PDO):T $work
     * @return T
     */
    public static function transaction(callable $work): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $work($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
