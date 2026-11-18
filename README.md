# SmartCoffee

Phần mềm quản trị quán cà phê và POS gọi món cho **một quán**. Nhóm 2, môn WEB212.

Nhân viên phục vụ gọi món bằng điện thoại, quầy pha chế nhận món theo hàng chờ, thu ngân thanh toán tại bàn và in hóa đơn, chủ quán hủy món hoặc đơn và xem báo cáo, admin quản lý hệ thống.

Thiết kế giao diện (Figma): https://www.figma.com/design/bOJYKAAD1MK3WMiRdrgjGF/huy (trang **POS v1**)

## Trạng thái

| Phần | Trạng thái |
|---|---|
| Cơ sở dữ liệu (`database/smartcoffee_v2.sql`) | Đã có script 13 bảng theo luồng thanh toán trước, pha sau; đã chạy thử trên XAMPP ngày 17/11 |
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

`database/smartcoffee_v2.sql` tạo database `smartcoffee_v2` gồm 13 bảng, thiết kế ở [docs/database-design.md](docs/database-design.md). Yêu cầu MariaDB 10.4+ (XAMPP).

1. Mở XAMPP, bật **Apache** và **MySQL**.
2. Vào `http://localhost/phpmyadmin`, tab **Import**, chọn file SQL, bấm **Go**.

Script bắt đầu bằng `DROP DATABASE IF EXISTS smartcoffee_v2`, chạy lại sẽ xóa dữ liệu cũ. Tài khoản mẫu: `admin`, `quanly.01`, `thungan.01`, `phucvu.01`, `phache.01`, mật khẩu `123456` (chỉ dùng khi phát triển).

## Làm việc nhóm

- Mỗi việc là một task Jira (mã `SC-n`). Tên nhánh git theo mã task, ví dụ `SC-4-wireframe-pos`.
- Commit nhỏ, mô tả rõ. Không sửa file trong thư mục của người khác; cần sửa thì báo người đó.
- Không push thẳng nhánh `main` khi chưa được duyệt.
