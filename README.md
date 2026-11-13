# SmartCoffee • Nhóm 2

Website giới thiệu quán cà phê gồm intro 3D, menu, bài viết, liên hệ và giao diện đặt bàn.

## Chạy PHP MVC

Cài PHP 8.0 trở lên. Trên Windows mở setup.bat. Hoặc chạy từ thư mục repository:

```sh
php -S 127.0.0.1:4178 -t public router.php
```

Mở http://127.0.0.1:4178/ để xem intro hoặc http://127.0.0.1:4178/brunivo/index.html để xem trang chủ. Nếu cổng bận, thay 4178 bằng một cổng trống. Với Apache, đặt DocumentRoot vào public, bật mod_rewrite và cho phép .htaccess.

## Kiến trúc

public/index.php nhận request. PageController gọi model PageCatalog để tìm trang theo URL và render template trong app/Views. Các URL .html trước đây vẫn dùng được. Hình, CSS và JavaScript nằm trong public. Intro cần WebGL.

## Database và tài khoản demo

Model hiện đọc danh mục trang từ hệ thống tệp. Chưa có MySQL, PDO, tài khoản đăng nhập hay API lưu đặt bàn. Giỏ hàng demo dùng localStorage. Chạy website với tư cách khách.

## Báo cáo GĐ1

Hai bản Word/PDF trong docs ghi nhận trạng thái frontend trước khi chuyển sang PHP MVC, tại commit c3c91b3. Các giao diện tham khảo Your-Cafe-3D và Brunivo/Webflow giữ thông tin nguồn trong dự án.
