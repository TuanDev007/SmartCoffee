# SmartCoffee

Phần mềm quản trị quán cà phê và POS gọi món cho **một quán**. Nhóm 2, môn WEB212.

Nhân viên phục vụ gọi món bằng điện thoại, quầy pha chế nhận món theo hàng chờ, thu ngân thanh toán tại bàn và in hóa đơn, chủ quán hủy món hoặc đơn và xem báo cáo, admin quản lý hệ thống.

Thiết kế giao diện (Figma): https://www.figma.com/design/bOJYKAAD1MK3WMiRdrgjGF/huy (trang **POS v1**)

## Trạng thái

| Phần | Trạng thái |
|---|---|
| Cơ sở dữ liệu (`database/smartcoffee_pos.sql`) | Đã có script, 9 bảng, dữ liệu mẫu, 9 truy vấn mẫu |
| Backend (`backend/`) | Chưa lập trình |
| Frontend (`frontend/`) | Đang thiết kế wireframe, chưa lập trình |
| Báo cáo (`docs/`) | Bản giai đoạn 1, đang cập nhật |

Bản website giới thiệu và intro 3D cũ nằm trong `old-version/`, không thuộc hệ thống POS.

## Cấu trúc

```text
backend/
  public/            Điểm vào duy nhất của API (index.php sau này)
  config/            Cấu hình kết nối CSDL, hằng số
  routes/            Khai báo đường dẫn API
  app/Core/          Router, kết nối PDO, định dạng JSON, xác thực
  app/Controllers/   Nhận request, trả JSON
  app/Models/        Truy vấn CSDL
  app/Middleware/    Kiểm tra đăng nhập và vai trò
frontend/
  login/  phuc-vu/  pha-che/  thu-ngan/  quan-ly/   Mỗi trang một thư mục
  shared/            Component dùng chung
database/            Script MySQL
docs/                Báo cáo
old-version/         Phiên bản cũ (website giới thiệu, intro 3D), giữ để tham khảo
```

Mỗi người làm trong thư mục của mình để ít đụng file nhau.

| Thư mục | Phụ trách (dự kiến, chưa chốt) |
|---|---|
| `backend/`, `database/` | Hùng |
| `frontend/login`, `phuc-vu`, `thu-ngan` | Tuấn Anh |
| `frontend/pha-che`, `quan-ly`, `shared` | Huy |

## Quy tắc nghiệp vụ đã chốt

- Đăng nhập bằng session PHP; 4 vai trò cố định trong code: phục vụ, pha chế, thu ngân, manager. Admin chỉ quản lý hệ thống (tài khoản, cài đặt), không gọi món, không thanh toán, không hủy đơn.
- Mỗi bàn tối đa **một đơn đang mở**.
- Món mới thêm vào đơn là **nháp**: phục vụ sửa hoặc xóa tự do, quầy pha chế chưa thấy. Bấm **Gửi món** thì chuyển thành đã gửi.
- Món đã gửi: **chỉ manager** hủy được, bắt buộc nhập lý do. Hủy cả đơn cũng chỉ manager.
- Đơn đã thanh toán không hủy.

## Cơ sở dữ liệu

`database/smartcoffee_pos.sql` tạo database `smartcoffee_pos`: `store_settings`, `users`, `categories`, `products`, `dining_tables`, `orders`, `order_items`, `payments`, `invoices`. Yêu cầu MySQL 8.0+ hoặc MariaDB 10.4+ (XAMPP).

1. Mở XAMPP, bật **Apache** và **MySQL**.
2. Vào `http://localhost/phpmyadmin`, tab **Import**, chọn file SQL, bấm **Go**.

Script bắt đầu bằng `DROP DATABASE IF EXISTS smartcoffee_pos`, chạy lại sẽ xóa dữ liệu cũ. Có sẵn tài khoản mẫu cho các vai trò (`admin`, `quanly`, `thungan`, `phucvu`, `phache`).

## Làm việc nhóm

- Mỗi việc là một task Jira (mã `SC-n`). Tên nhánh git theo mã task, ví dụ `SC-4-wireframe-pos`.
- Commit nhỏ, mô tả rõ. Không sửa file trong thư mục của người khác; cần sửa thì báo người đó.
- Không push thẳng nhánh `main` khi chưa được duyệt.
