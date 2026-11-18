<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thông tin của một request: method, đường dẫn (đã bỏ phần thư mục cài đặt),
 * query, body JSON, header, tham số trên đường dẫn ({id}).
 */
final class Request
{
    /** @var array<string,string> */
    public array $params = [];

    private ?array $json = null;

    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        private readonly string $rawBody,
        private readonly array $headers,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Bỏ phần thư mục chứa index.php, ví dụ /SmartCoffee/backend/public/api/health → /api/health
        $baseDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        if ($baseDir !== '' && str_starts_with($uriPath, $baseDir)) {
            $uriPath = substr($uriPath, strlen($baseDir));
        }
        $path = '/' . trim($uriPath, '/');

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = (string) $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = (string) $_SERVER['CONTENT_TYPE'];
        }

        return new self($method, $path, $_GET, (string) file_get_contents('php://input'), $headers);
    }

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * Body JSON dạng mảng. Body rỗng → mảng rỗng. JSON hỏng hoặc không phải object → 422.
     */
    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        if (trim($this->rawBody) === '') {
            return $this->json = [];
        }
        try {
            $data = json_decode($this->rawBody, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw HttpException::validation([], 'Dữ liệu gửi lên không phải JSON hợp lệ.');
        }
        if (!is_array($data) || array_is_list($data) && $data !== []) {
            throw HttpException::validation([], 'Dữ liệu gửi lên phải là một đối tượng JSON.');
        }
        return $this->json = $data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->json()[$key] ?? $default;
    }

    public function param(string $key): ?string
    {
        return $this->params[$key] ?? null;
    }

    public function isMutating(): bool
    {
        return in_array($this->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
