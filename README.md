# SmartCoffee

Báo cáo giai đoạn 1 môn WEB212 – Phát triển dự án website với AI, nhóm 2.

## Thành viên

| MSSV | Họ tên | Nhóm |
|---|---|---|
| PI02957 | Ngô Ngọc Tuấn Anh | 2 |
| PI05839 | Nguyễn Lâm Sơn Hùng | 2 |
| PI02906 | Lầu Gia Huy | 2 |
| PI02156 | Lê Ngọc Gia Bảo | 2 |

## Phạm vi hiện tại

SmartCoffee là bản frontend giới thiệu quán cà phê: intro 3D WebGL, chọn cấu hình đồ uống, giỏ hàng demo cục bộ, trang chủ, giới thiệu, menu, chi tiết đồ uống, blog, liên hệ và giao diện đặt bàn. Website dùng HTML/CSS/JavaScript; Node.js chỉ phục vụ tệp tĩnh để xem thử.

**Chưa có backend PHP MVC/OOP/PDO, MySQL, đăng nhập, phân quyền admin, xử lý đặt bàn/đơn hàng hoặc thanh toán thực.** Thông báo “ORDER CONFIRMED” trong intro là mô phỏng, không phải đơn hàng được lưu trên máy chủ. Phản hồi ở intro dùng `mailto:`; các form Webflow chưa có backend SmartCoffee đi kèm.

## Cài đặt và chạy

1. Cài Node.js bản LTS từ https://nodejs.org/.
2. Clone repository hoặc tải ZIP và giải nén toàn bộ.
3. Windows: bấm đúp `setup.bat`. Hoặc mở terminal tại thư mục dự án và chạy:

```sh
node start-smartcoffee.cjs
```

4. Mở địa chỉ được in trong terminal. Mặc định là `http://127.0.0.1:4178/`; nếu cổng bận, server chọn cổng khác.
5. Chọn **ENTER EXPERIENCE**, cuộn qua intro, chọn mẫu đồ uống rồi bấm **Vào trang chủ SmartCoffee**. Có thể vào thẳng `/brunivo/index.html` trên cùng địa chỉ máy chủ.
6. Giữ terminal mở; nhấn `Ctrl+C` để dừng.

Không cần `npm install`. Không mở HTML bằng `file://`. Intro cần trình duyệt hỗ trợ WebGL; một số liên kết/dịch vụ ngoài cần Internet.

## Cấu trúc

```text
setup.bat                       Khởi chạy trên Windows
start-smartcoffee.cjs            HTTP server tĩnh chỉ GET và HEAD
HUONG-DAN.txt                    Hướng dẫn gốc
public/Your-Cafe-3D/             Intro 3D và bundle JavaScript/CSS
public/brunivo/                  Website nhiều trang và tài nguyên
docs/                           Báo cáo DOCX và PDF
```

## Database và tài khoản demo

- Chưa có database MySQL hay script `.sql` trong phiên bản này.
- `localStorage` (`your_cafe_cart`, `your_cafe_editor_config`) chỉ lưu trạng thái ở từng trình duyệt, không thay thế database.
- Khách truy cập không cần đăng nhập. **Chưa có tài khoản admin/khách demo** vì chưa triển khai xác thực.
- Site Editor của intro chỉ chỉnh cấu hình cục bộ, không phải trang quản trị có phân quyền.

## Kiểm tra ngày 13/11/2026

- GET intro, trang chủ, menu, liên hệ, đặt bàn: HTTP 200.
- HEAD intro: HTTP 200; đường dẫn không tồn tại: HTTP 404.
- POST trang đặt bàn: HTTP 405, đúng với server tĩnh, đồng thời xác nhận chưa có API nhận đơn.
- Nút đi từ intro đến trang chủ giải quyết đúng đường dẫn `/brunivo/index.html`.
- Trang chủ ở viewport 390 × 844 không có tràn ngang tại thời điểm kiểm tra; chưa thay thế kiểm thử thiết bị thực.
- Trường ngày của form đặt bàn thiếu `name`; số khách đang là input text. Cần sửa khi triển khai nghiệp vụ thật.

## Phần cần phát triển tiếp theo yêu cầu môn học

PHP MVC (`app/Models`, `app/Views`, `app/Controllers`, `index.php`, `.htaccess`); PHP class và kế thừa/đóng gói; PDO prepared statements; MySQL/SQL; CRUD thực đơn; đặt bàn và quản trị; tài khoản demo; kiểm thử nghiệp vụ; triển khai hosting có Live URL.

Repository GitHub là nơi lưu mã nguồn, **không phải Live URL website**. Sơ đồ ERD/MVC trong báo cáo là thiết kế đề xuất cho giai đoạn tiếp theo, chưa phải phần đã được triển khai.

## Nguồn giao diện

Gói frontend hiện có chứa intro Your-Cafe-3D và giao diện Brunivo/Webflow được tùy chỉnh tên SmartCoffee. Giữ nguyên thông tin tác giả và trang `public/brunivo/template-info/licenses.html`; các thư viện và tài nguyên bên thứ ba tuân theo điều kiện của chủ sở hữu. Không xem toàn bộ tài nguyên mẫu là mã do nhóm tự viết.
