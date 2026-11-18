<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Đọc cấu hình: ưu tiên config.php (riêng từng máy), không có thì dùng config.example.php.
 */
final class Config
{
    private static array $values = [];

    public static function load(string $dir): array
    {
        $file = is_file($dir . '/config.php') ? $dir . '/config.php' : $dir . '/config.example.php';
        self::$values = require $file;
        return self::$values;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$values[$key] ?? $default;
    }
}
