<?php

declare(strict_types=1);

namespace App\Models;

/** Tiện ích dựng câu UPDATE an toàn: tên cột chỉ lấy từ danh sách cố định trong code. */
final class Sql
{
    /**
     * @param array<string,string> $map   khóa dữ liệu => tên cột
     * @param array<string,mixed>  $data  dữ liệu đã kiểm
     * @return array{0:string, 1:list<mixed>}  "col1 = ?, col2 = ?" và tham số
     */
    public static function sets(array $map, array $data): array
    {
        $sets = [];
        $params = [];
        foreach ($map as $key => $column) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            $sets[] = "{$column} = ?";
            $params[] = is_bool($value) ? ($value ? 1 : 0) : $value;
        }
        return [implode(', ', $sets), $params];
    }
}
