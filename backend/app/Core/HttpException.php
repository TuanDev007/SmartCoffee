<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lỗi có chủ đích, trả về client theo định dạng chung (api-contract mục 1.2, 1.3).
 */
final class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $fields = [],
        public readonly array $extra = [],
    ) {
        parent::__construct($message);
    }

    public static function unauthenticated(): self
    {
        return new self(401, 'AUTH_REQUIRED', 'Bạn cần đăng nhập.');
    }

    public static function forbidden(string $message = 'Bạn không có quyền dùng chức năng này.'): self
    {
        return new self(403, 'FORBIDDEN', $message);
    }

    public static function notFound(string $message = 'Không tìm thấy.'): self
    {
        return new self(404, 'NOT_FOUND', $message);
    }

    /** @param array<string,string> $fields */
    public static function validation(array $fields, string $message = 'Dữ liệu chưa hợp lệ.'): self
    {
        return new self(422, 'VALIDATION_ERROR', $message, $fields);
    }

    /** Sai trạng thái nghiệp vụ, mã lỗi riêng từng endpoint. */
    public static function conflict(string $code, string $message, array $extra = []): self
    {
        return new self(409, $code, $message, [], $extra);
    }
}
