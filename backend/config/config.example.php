<?php

/*
 * Cấu hình mẫu. Muốn đổi cho máy mình thì chép file này thành config.php rồi sửa.
 * config.php không đưa lên git (.gitignore). Không có config.php thì dùng file này.
 */

return [
    // Mặc định của XAMPP: user root, không mật khẩu, cổng 3306
    'db' => [
        'dsn'      => 'mysql:host=127.0.0.1;port=3306;dbname=smartcoffee_v2;charset=utf8mb4',
        'username' => 'root',
        'password' => '',
    ],
    'timezone' => 'Asia/Ho_Chi_Minh',
    // true: lỗi 500 trả kèm chi tiết để debug. Chỉ bật trên máy cá nhân.
    'debug' => false,
    'session_name' => 'SMARTCOFFEE_SESSID',
];
