# SmartCoffee: Hợp đồng API và dữ liệu

**Trạng thái: hợp đồng API CHƯA được đặc tả.** Tài liệu này chỉ ghi những gì đã được xác nhận. Phần chưa có nguồn xác nhận ghi rõ "Chưa được đặc tả". Không tự suy diễn hay sáng tạo endpoint hoặc schema.

**Chỉ Hùng được phê duyệt thay đổi hợp đồng API và database schema.** Thành viên và AI khác được phát hiện vấn đề và đề xuất phương án, nhưng không tự ý thay đổi hợp đồng đã chốt (xem mục 5).

Nghiệp vụ nằm ở [business-rules.md](business-rules.md), không lặp lại ở đây.

Tài liệu tách ba loại: **đã phê duyệt** (hiện gần như chưa có, xem mục 1), **hiện trạng triển khai** (schema trong SQL, mục 3 và 4) và **bản đề xuất chưa duyệt** (endpoint ghi rõ trạng thái "Đề xuất"). SQL và bản nháp **không phải nguồn phê duyệt** nghiệp vụ hay hợp đồng.

## 1. Endpoint API

**Chưa được đặc tả.** Chưa có endpoint nào được Hùng xác nhận.

| Nhóm | HTTP method, request, response, status code |
|---|---|
| Đăng nhập, đăng xuất, thông tin người dùng | Chưa được đặc tả |
| Thực đơn, danh mục, bàn | Chưa được đặc tả |
| Đơn và món (mở đơn, thêm, sửa, xóa, gửi món, chuyển bàn) | Chưa được đặc tả |
| Quầy pha chế (hàng chờ, đổi trạng thái món) | Chưa được đặc tả |
| Hủy món, hủy đơn | Chưa được đặc tả |
| Thanh toán, hóa đơn, in lại | Chưa được đặc tả |
| Báo cáo | Chưa được đặc tả |
| Quản trị (tài khoản, thực đơn, bàn) | Chưa được đặc tả |

Định dạng JSON chung, mã lỗi, cách xác thực request: **Chưa được đặc tả**. Điều đã được PM chốt: backend trả JSON, frontend riêng (quyết định D-09) và đăng nhập bằng session PHP, không dùng JWT (D-02), xem nhật ký quyết định trong [business-rules.md](business-rules.md), mục 7.

Đặc tả API viết ngay trong file này, theo từng đợt backend. Mỗi endpoint ghi trạng thái **Đề xuất** hoặc **Đã duyệt**. Chỉ endpoint **Đã duyệt** mới là hợp đồng (D-63). Bản nháp cũ `docs/04-api.md` (luồng cũ) đã xóa ngày 17/11.

## 2. Quy tắc tương thích frontend và backend

- Frontend **không tự định nghĩa endpoint**. Cần endpoint thì gửi đề xuất theo mục 5 và chờ Hùng phê duyệt.
- Cho tới khi một endpoint được đặc tả ở mục 1, không code gọi endpoint đó như thể đã tồn tại.
- Không ai tự đổi database schema hiện có (mục 3) khi chưa được Hùng phê duyệt.
- Tên trường và kiểu dữ liệu dùng giữa frontend và backend: **Chưa được đặc tả** (phụ thuộc mục 1).

## 3. Database schema

- Thiết kế: [docs/database-design.md](database-design.md), phiên bản 0.1. PM cho viết SQL ngày 17/11/2026.
- Script: [`database/smartcoffee_v2.sql`](../database/smartcoffee_v2.sql), database `smartcoffee_v2`, 13 bảng. **Chưa chạy thử trên MariaDB.**
- Bản cũ `database/smartcoffee_pos.sql` (luồng cũ) đã xóa khỏi `main` ngày 17/11, còn trong lịch sử git.
- Thay đổi schema vẫn phải theo mục 5.

## 4. Giá trị liệt kê

Xem các cột `ENUM` trong [docs/database-design.md](database-design.md), mục 3.

## 5. Quy trình đề xuất và phê duyệt thay đổi

Áp dụng cho mọi thay đổi endpoint, định dạng request/response, cấu trúc dữ liệu hoặc database schema.

1. **Phát hiện vấn đề:** ai cũng có quyền, kể cả AI.
2. **Đề xuất:** gửi cho Hùng (comment Jira). Nêu vấn đề, **tối đa 2 phương án**, khuyến nghị, và tác động lên frontend, backend, database, tài liệu.
3. **Phê duyệt:** chỉ Hùng phê duyệt. Chưa có xác nhận rõ ràng thì coi như chưa được phép.
4. **Thực hiện:** sau khi được phê duyệt mới sửa. Cập nhật tài liệu này trong cùng commit với thay đổi.
5. Không tự ý thay đổi hợp đồng đã chốt dù tin rằng đề xuất là đúng.

## 6. Việc đang chờ Hùng quyết

- Đặc tả các endpoint ở mục 1. Hiện toàn bộ là "Chưa được đặc tả".
- Phê duyệt (hoặc yêu cầu sửa) schema ở mục 3 và 4 làm hợp đồng dữ liệu. Hiện chỉ là hiện trạng trong SQL.
- Duyệt đặc tả API từng đợt (D-63).
