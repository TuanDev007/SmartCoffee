# Backend SmartCoffee

API PHP thuần (không framework, không thư viện ngoài), trả JSON. Hợp đồng API: [docs/api-contract.md](../docs/api-contract.md).

## Chạy trên XAMPP

1. Import `database/smartcoffee_v2.sql` bằng phpMyAdmin.
2. Đặt thư mục dự án trong `C:\xampp\htdocs\`, ví dụ `C:\xampp\htdocs\SmartCoffee`.
3. Bật **Apache** và **MySQL** trong XAMPP Control Panel.
4. **Cấu hình kết nối database:** chép `backend/config/config.example.php` thành `backend/config/config.php` rồi sửa cho đúng máy mình (cổng, mật khẩu). Ví dụ MySQL của XAMPP chạy cổng 3317 thì sửa `port=3306` thành `port=3317`. File `config.php` không đưa lên git.
5. Mở `http://localhost/SmartCoffee/backend/public/api/health`. Đúng sẽ thấy:
   ```json
   {"data":{"status":"ok"}}
   ```
   Thấy `SERVER_ERROR` "Không kết nối được database" thì kiểm lại bước 4.

Apache phải bật `mod_rewrite` (XAMPP bật sẵn) để `.htaccess` chuyển request về `public/index.php`.

## Cấu trúc

```text
public/index.php     Điểm vào duy nhất; .htaccess chuyển mọi request về đây
config/              config.example.php (mẫu), config.php (riêng từng máy, không lên git)
routes/api.php       Danh sách endpoint, mỗi dòng ghi quyền
app/Core/            Router, Request, Response, Database (PDO), Session, Auth, Csrf, lỗi
app/Middleware/      Kiểm đăng nhập (401), vai trò (403), CSRF (403)
app/Controllers/     Xử lý từng endpoint
app/Models/          Truy vấn database theo bảng (từ đợt 2)
```

## Khai báo endpoint

```php
$router->get('/api/menu', [MenuController::class, 'index'], ['roles' => ['waiter', 'cashier', 'barista', 'manager']]);
$router->post('/api/auth/login', [AuthController::class, 'login'], ['auth' => false]);
```

- Mặc định mọi route **cần đăng nhập**. Route công khai phải ghi `'auth' => false`.
- `roles`: vai trò được gọi. Bỏ trống là mọi vai trò đã đăng nhập.
- `POST`, `PATCH`, `DELETE` của route cần đăng nhập tự kiểm header `X-CSRF-Token`.
- Thứ tự kiểm: đăng nhập → vai trò → CSRF → controller.
- Chỉ thêm endpoint đã duyệt trong `docs/api-contract.md`.
