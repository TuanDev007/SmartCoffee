# SmartCoffee: Tài liệu API (bản nháp 0.1)

Đây là **hợp đồng** giữa backend (Hùng) và frontend (Tuấn Anh, Huy). Đổi endpoint hay dữ liệu trả về thì sửa file này trong cùng commit và ghi vào mục "Lịch sử thay đổi".

Nguồn sự thật: `database/smartcoffee_pos.sql` và file này. Hai nơi mâu thuẫn nhau thì dừng lại hỏi Hùng, không tự chọn.

## 1. Quy ước chung

- Địa chỉ gốc: `/api`. Dữ liệu gửi và nhận đều là JSON (`Content-Type: application/json`).
- Đăng nhập bằng **session PHP** (cookie). Frontend gọi API với `credentials: 'include'`.
- **CSRF:** `POST /api/login` trả về `csrf_token`. Mọi request `POST`, `PATCH`, `DELETE` sau đó gửi header `X-CSRF-Token`.
- Tiền là **số nguyên VND** (35000, không dùng số lẻ hay chuỗi). Giờ dạng `"2026-11-14 09:45:00"`.
- Tên trường dùng `snake_case`, đúng tên cột trong DB.
- **Quyền kiểm tra ở server.** Frontend ẩn nút chỉ để dễ dùng, không thay thế kiểm tra quyền.
- **Tiền do server tính.** Frontend không tự cộng tổng gửi lên.

### Định dạng trả về

Thành công:
```json
{ "ok": true, "data": { } }
```
Lỗi:
```json
{ "ok": false, "error": { "code": "TABLE_BUSY", "message": "Bàn đang có đơn mở." } }
```

| HTTP | Khi nào | `error.code` thường gặp |
|---|---|---|
| 400 | Sai định dạng JSON hoặc thiếu trường | `BAD_REQUEST` |
| 401 | Chưa đăng nhập hoặc phiên hết hạn | `UNAUTHENTICATED` |
| 403 | Đúng người nhưng vai trò không được phép, hoặc CSRF sai | `FORBIDDEN`, `CSRF_INVALID` |
| 404 | Không có bàn, đơn, món | `NOT_FOUND` |
| 409 | Trạng thái hiện tại không cho làm | `TABLE_BUSY`, `ORDER_NOT_OPEN`, `ITEM_NOT_DRAFT`, `INVALID_TRANSITION` |
| 422 | Dữ liệu hợp lệ về dạng nhưng sai nghiệp vụ | `PRODUCT_UNAVAILABLE`, `REASON_REQUIRED`, `NO_DRAFT_ITEMS`, `PAYMENT_MISMATCH`, `HAS_DRAFT_ITEMS` |

## 2. Vai trò

| Mã | Tên | Làm được |
|---|---|---|
| `waiter` | Phục vụ | Xem bàn, mở đơn, gọi món, gửi món, chuyển bàn, xác nhận đã mang ra. Chỉ xóa được món **nháp** |
| `barista` | Pha chế | Xem hàng chờ, đổi trạng thái món |
| `cashier` | Thu ngân | Xem đơn đang mở, thanh toán, in hóa đơn |
| `manager` | Chủ quán | Mọi việc bán hàng, **duy nhất** hủy món/đơn đã gửi, xem báo cáo |
| `admin` | Admin | Chỉ quản lý tài khoản và cài đặt. **Không** bán hàng, không hủy đơn |

## 3. Đăng nhập

| Method | Đường dẫn | Vai trò | Mô tả |
|---|---|---|---|
| POST | `/api/login` | Công khai | Đăng nhập |
| POST | `/api/logout` | Đã đăng nhập | Đăng xuất |
| GET | `/api/me` | Đã đăng nhập | Thông tin người đang dùng |

`POST /api/login`, body `{ "username": "phucvu", "password": "..." }`:
```json
{ "ok": true, "data": {
  "user": { "id": 4, "full_name": "Phục vụ Khang", "role": "waiter" },
  "csrf_token": "..."
} }
```
Sai tài khoản hoặc mật khẩu: 401 `UNAUTHENTICATED`. Tài khoản `is_active = false`: 403 `FORBIDDEN`. Vai trò lấy theo tài khoản, **không có tham số chọn vai trò**.

## 4. Thực đơn và bàn

| Method | Đường dẫn | Vai trò | Mô tả |
|---|---|---|---|
| GET | `/api/categories` | waiter, manager | Danh mục đang bán, theo `sort_order` |
| GET | `/api/products?category_id=&q=` | waiter, manager | Món đang bán. `q` tìm theo tên |
| GET | `/api/tables?area=` | waiter, cashier, manager | Sơ đồ bàn |

Món: `{ "id": 7, "category_id": 1, "name": "Cà phê sữa", "price": 35000, "image": null, "is_available": true }`. `is_available = false` là **tạm hết**: vẫn hiện nhưng không gọi được.

Bàn: `{ "id": 1, "code": "B01", "area": "Tầng trệt", "capacity": 4, "status": "free", "order_id": null }`. `status` là `free` (không có đơn mở) hoặc `occupied` (có đơn mở, kèm `order_id`). Trạng thái được suy ra từ đơn mở, DB không lưu riêng. **Không có trạng thái "Chờ thanh toán"** (đã chốt 14/11): khách gọi món xong thì thanh toán ngay tại bàn, đơn vẫn `open` cho tới khi trả tiền.

## 5. Đơn và món

| Method | Đường dẫn | Vai trò | Mô tả |
|---|---|---|---|
| POST | `/api/orders` | waiter, manager | Mở đơn |
| GET | `/api/orders?status=open` | cashier, manager, waiter | Danh sách đơn đang mở |
| GET | `/api/orders/{id}` | waiter, cashier, manager | Chi tiết đơn kèm món |
| POST | `/api/orders/{id}/items` | waiter, manager | Thêm món **nháp** (cũng dùng cho Gọi thêm) |
| PATCH | `/api/orders/{id}/items/{item_id}` | waiter, manager | Sửa số lượng, ghi chú (chỉ món nháp) |
| DELETE | `/api/orders/{id}/items/{item_id}` | waiter, manager | Xóa món nháp |
| POST | `/api/orders/{id}/send` | waiter, manager | Gửi món: các món nháp thành `pending` |
| POST | `/api/orders/{id}/move` | waiter, manager | Chuyển bàn |

**Mở đơn**, body `{ "order_type": "dine_in", "table_id": 1 }` (đơn mang đi: `"takeaway"`, không có `table_id`). Trả 201 với `code` do server sinh (ví dụ `DH-20261114-005`). Bàn đã có đơn mở: 409 `TABLE_BUSY`.

**Thêm món**, body `{ "product_id": 7, "quantity": 1, "note": "ít đá" }`. Món tạm hết hoặc ngừng bán: 422 `PRODUCT_UNAVAILABLE`. Đơn không còn mở: 409 `ORDER_NOT_OPEN`. `unit_price` do server lấy từ giá hiện tại, frontend không gửi.

**Sửa hoặc xóa món** không phải nháp: 409 `ITEM_NOT_DRAFT`. Xóa món nháp là xóa hẳn dòng.

**Gửi món** không có món nháp: 422 `NO_DRAFT_ITEMS`. Sau khi gửi, server tính lại `subtotal` (chỉ cộng món đã gửi, chưa hủy).

**Chuyển bàn**, body `{ "table_id": 3 }`. Bàn mới đang có đơn: 409 `TABLE_BUSY`.

Chi tiết đơn:
```json
{ "ok": true, "data": {
  "id": 12, "code": "DH-20261114-005", "order_type": "dine_in",
  "table": { "id": 1, "code": "B01", "area": "Tầng trệt" },
  "status": "open", "subtotal": 35000, "discount": 0, "total": 35000,
  "items": [
    { "id": 31, "product_id": 7, "product_name": "Cà phê sữa", "quantity": 1,
      "unit_price": 35000, "note": "ít đá", "status": "pending" }
  ]
} }
```
`items[].status` là một trong `draft`, `pending`, `preparing`, `ready`, `served`, `cancelled`.

## 6. Quầy pha chế

| Method | Đường dẫn | Vai trò | Mô tả |
|---|---|---|---|
| GET | `/api/queue` | barista, manager | Món `pending` và `preparing`, gửi trước ở trên |
| PATCH | `/api/items/{id}/status` | barista, waiter | Đổi trạng thái món |

`GET /api/queue` **không** trả món nháp. Mỗi phần tử: `{ "item_id": 31, "order_id": 12, "order_code": "...", "table_code": "B01", "product_name": "Cà phê sữa", "quantity": 1, "note": "ít đá", "status": "pending", "created_at": "..." }`. Frontend hỏi lại mỗi vài giây (polling, mục tiêu dưới 5 giây).

`PATCH /api/items/{id}/status`, body `{ "status": "preparing" }`. Chỉ các bước sau hợp lệ, bước khác trả 409 `INVALID_TRANSITION`:

| Từ | Sang | Ai làm |
|---|---|---|
| `pending` | `preparing` | barista |
| `preparing` | `ready` | barista |
| `ready` | `served` | waiter |

## 7. Hủy (chỉ manager)

| Method | Đường dẫn | Mô tả |
|---|---|---|
| POST | `/api/orders/{id}/items/{item_id}/cancel` | Hủy một món đã gửi, **chưa mang ra** (`pending`, `preparing`, `ready`) |
| POST | `/api/orders/{id}/cancel` | Hủy cả đơn |

Body `{ "reason": "Khách đổi món" }`. **`reason` bắt buộc**, thiếu hoặc rỗng: 422 `REASON_REQUIRED`. Vai trò khác: 403. Đơn đã `paid`: 409 `ORDER_NOT_OPEN`. Món nháp không dùng API này (xóa bằng `DELETE`). Sau khi hủy món, server tính lại `subtotal`.

**Món đã `served` (đã mang ra) không hủy được** (đã chốt 14/11): gọi API hủy món cho món `served` trả 409 `INVALID_TRANSITION`. Frontend không hiện nút "Hủy món" cho món `served`.

**Hủy cả đơn** (đã chốt 14/11): đơn chuyển `cancelled`, mọi món đã gửi chuyển `cancelled` cùng lý do, `subtotal`, `discount`, `total` về 0, bàn được trả về trống.

## 8. Thanh toán và hóa đơn

| Method | Đường dẫn | Vai trò | Mô tả |
|---|---|---|---|
| POST | `/api/orders/{id}/pay` | cashier, manager | Thanh toán và đóng đơn |
| GET | `/api/orders/{id}/invoice` | cashier, manager | Dữ liệu in hóa đơn khổ 80 mm |
| POST | `/api/invoices/{id}/print` | cashier, manager | Ghi nhận một lần in lại |

**Thanh toán**, body (trả kết hợp = nhiều dòng trong `payments`):
```json
{ "discount": 0,
  "payments": [
    { "method": "cash", "amount": 20000, "received_amount": 50000 },
    { "method": "transfer", "amount": 15000 }
  ] }
```
`method` là `cash`, `transfer` hoặc `card`. Tổng `amount` phải bằng `subtotal - discount`, sai thì 422 `PAYMENT_MISMATCH`. `received_amount` (tiền khách đưa) chỉ dùng cho tiền mặt và phải ≥ `amount`. Đơn còn món nháp: 422 `HAS_DRAFT_ITEMS`. Server chạy trong **một transaction**: ghi thanh toán, đóng đơn (`paid`, `closed_at`), tạo hóa đơn. Trả về hóa đơn và `change` (tiền thối).

**Hóa đơn** gồm thông tin quán (`store_settings`), `invoice_no`, danh sách món (không nháp, không hủy), tổng, các dòng thanh toán, `print_count`.

## 9. Báo cáo (manager)

| Method | Đường dẫn | Mô tả |
|---|---|---|
| GET | `/api/reports/daily?date=2026-11-14` | Doanh thu và số đơn trong ngày |
| GET | `/api/reports/top-products?from=&to=` | Món bán chạy |

Chỉ tính đơn `paid`. Chi tiết trường trả về sẽ chốt khi làm màn hình báo cáo.

## 10. Quản trị (admin, manager)

Quản lý tài khoản (`/api/users`), món, danh mục, bàn: **chưa chi tiết** ở bản 0.1, sẽ bổ sung khi chốt màn hình quản lý.

## 11. Việc chưa chốt (cần Hùng quyết)

Hiện không còn mục nào. Hai mục cũ (hủy món `served`, hủy cả đơn) đã chốt ở mục 7.

## Lịch sử thay đổi

- 0.1 (14/11/2026): bản nháp đầu tiên.
