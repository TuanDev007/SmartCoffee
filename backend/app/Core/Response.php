<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Gửi JSON theo định dạng chung:
 *   thành công: { "data": ... }
 *   lỗi:        { "error": { "code", "message", "fields"? } }
 */
final class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        self::send(['data' => $data], $status);
    }

    public static function error(int $status, string $code, string $message, array $fields = [], array $extra = []): void
    {
        $error = ['code' => $code, 'message' => $message] + $extra;
        if ($fields !== []) {
            $error['fields'] = $fields;
        }
        self::send(['error' => $error], $status);
    }

    private static function send(array $body, int $status): void
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
