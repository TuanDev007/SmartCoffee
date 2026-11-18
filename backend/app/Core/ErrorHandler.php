<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Bắt mọi lỗi chưa xử lý và trả JSON. Lỗi ngoài ý muốn trả 500 SERVER_ERROR,
 * không lộ chi tiết kỹ thuật (trừ khi bật debug), chi tiết ghi vào log của PHP.
 */
final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;
        ini_set('display_errors', '0');
        error_reporting(E_ALL);

        // Cảnh báo, notice cũng coi là lỗi để không lọt dữ liệu sai
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (\Throwable $e): void {
            self::render($e);
        });
    }

    public static function render(\Throwable $e): void
    {
        if ($e instanceof HttpException) {
            Response::error($e->status, $e->errorCode, $e->getMessage(), $e->fields, $e->extra);
            return;
        }

        error_log(sprintf('[SmartCoffee] %s: %s in %s:%d', $e::class, $e->getMessage(), $e->getFile(), $e->getLine()));
        $extra = self::$debug ? ['debug' => $e::class . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine()] : [];
        Response::error(500, 'SERVER_ERROR', 'Hệ thống đang gặp lỗi. Vui lòng thử lại.', [], $extra);
    }
}
