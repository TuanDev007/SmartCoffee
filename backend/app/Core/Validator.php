<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kiểm dữ liệu đầu vào đơn giản. Gom lỗi theo từng trường rồi ném 422 một lần.
 *
 *   $v = new Validator($request->json());
 *   $name = $v->string('full_name', required: true, max: 100);
 *   $v->throwIfFailed();
 */
final class Validator
{
    /** @var array<string,string> */
    private array $errors = [];

    public function __construct(private readonly array $data)
    {
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function string(string $key, bool $required = false, int $min = 0, int $max = 255, ?string $pattern = null, ?string $patternMessage = null): ?string
    {
        if (!$this->has($key) || $this->data[$key] === null) {
            if ($required) {
                $this->errors[$key] = 'Bắt buộc nhập.';
            }
            return null;
        }
        $value = $this->data[$key];
        if (!is_string($value)) {
            $this->errors[$key] = 'Phải là chuỗi ký tự.';
            return null;
        }
        $value = trim($value);
        $len = mb_strlen($value);
        if ($required && $len === 0) {
            $this->errors[$key] = 'Bắt buộc nhập.';
            return null;
        }
        if ($len < $min) {
            $this->errors[$key] = "Tối thiểu {$min} ký tự.";
        } elseif ($len > $max) {
            $this->errors[$key] = "Tối đa {$max} ký tự.";
        } elseif ($pattern !== null && !preg_match($pattern, $value)) {
            $this->errors[$key] = $patternMessage ?? 'Không đúng định dạng.';
        }
        return $value;
    }

    /** Chuỗi giữ nguyên (không cắt khoảng trắng), dùng cho mật khẩu. */
    public function raw(string $key, bool $required = false, int $min = 0, int $max = 255): ?string
    {
        if (!$this->has($key) || $this->data[$key] === null || $this->data[$key] === '') {
            if ($required) {
                $this->errors[$key] = 'Bắt buộc nhập.';
            }
            return null;
        }
        $value = $this->data[$key];
        if (!is_string($value)) {
            $this->errors[$key] = 'Phải là chuỗi ký tự.';
            return null;
        }
        $len = mb_strlen($value);
        if ($len < $min) {
            $this->errors[$key] = "Tối thiểu {$min} ký tự.";
        } elseif ($len > $max) {
            $this->errors[$key] = "Tối đa {$max} ký tự.";
        }
        return $value;
    }

    public function bool(string $key, bool $required = false): ?bool
    {
        if (!$this->has($key) || $this->data[$key] === null) {
            if ($required) {
                $this->errors[$key] = 'Bắt buộc nhập.';
            }
            return null;
        }
        if (!is_bool($this->data[$key])) {
            $this->errors[$key] = 'Phải là true hoặc false.';
            return null;
        }
        return $this->data[$key];
    }

    /** @param list<string> $allowed */
    public function enum(string $key, array $allowed, bool $required = false): ?string
    {
        $value = $this->string($key, $required, 0, 50);
        if ($value !== null && !in_array($value, $allowed, true)) {
            $this->errors[$key] = 'Chỉ nhận: ' . implode(', ', $allowed) . '.';
            return null;
        }
        return $value;
    }

    public function fail(string $key, string $message): void
    {
        $this->errors[$key] = $message;
    }

    public function throwIfFailed(): void
    {
        if ($this->errors !== []) {
            throw HttpException::validation($this->errors);
        }
    }
}
