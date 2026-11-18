<?php

declare(strict_types=1);

namespace App\Core;

/** Định dạng dữ liệu trả cho client (api-contract mục 1.2). */
final class Format
{
    /** Giờ trong database (giờ Việt Nam) → ISO 8601, ví dụ 2026-11-16T08:05:00+07:00. */
    public static function datetime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (new \DateTimeImmutable($value))->format(DATE_ATOM);
    }
}
