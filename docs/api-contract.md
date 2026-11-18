# SmartCoffee: Hợp đồng API và dữ liệu

**Trạng thái: đặc tả API đợt 1 đến 5 ĐÃ DUYỆT ngày 18/11/2026 (D-65).** Các đợt sau chưa đặc tả. Chỉ endpoint "Đã duyệt" mới là hợp đồng (D-63).

**Chỉ Hùng được phê duyệt thay đổi hợp đồng API và database schema** (D-22). Thành viên và AI được phát hiện vấn đề và đề xuất, không tự ý đổi phần đã chốt (mục 6).

Nghiệp vụ nằm ở [business-rules.md](business-rules.md), database ở [database-design.md](database-design.md); không lặp lại ở đây.

Ký hiệu nguồn: **[D-xx]** quyết định đã chốt; **[P-xx]** nội dung chờ PM duyệt; **[ĐX]** đề xuất kỹ thuật của AI.

## 1. Quy ước chung (Đã duyệt, D-65)

### 1.1 Địa chỉ và xác thực
- Mọi endpoint bắt đầu bằng `/api`. Backend trả JSON, frontend riêng [D-09].
- Đăng nhập bằng **session PHP** (cookie `PHPSESSID`, `HttpOnly`, `SameSite=Lax`), không dùng JWT [D-02]. Frontend gọi API với `credentials: 'include'`.
- **Chống CSRF:** sau khi đăng nhập, server trả `csrf_token`. Mọi request `POST`, `PATCH`, `DELETE` phải gửi header `X-CSRF-Token`. Sai hoặc thiếu thì trả `403 CSRF_INVALID` [ĐX].
- Quyền kiểm ở server theo vai trò cố định [D-03]. Cột **Quyền** ở mỗi endpoint là danh sách vai trò được gọi.

### 1.2 Định dạng
- Request và response: `Content-Type: application/json; charset=utf-8`.
- Thành công: `{ "data": ... }`. Danh sách: `{ "data": [ ... ] }`.
- Lỗi:
  ```json
  { "error": { "code": "VALIDATION_ERROR", "message": "Thông báo tiếng Việt cho người dùng", "fields": { "opening_cash": "Phải là số nguyên ≥ 0" } } }
  ```
  `fields` chỉ có khi lỗi dữ liệu đầu vào.
- Tên trường `snake_case`, giống cột database.
- **Tiền:** số nguyên VND, không có số lẻ [D-64]. **Server tự tính mọi số tiền**; frontend không gửi tổng tiền, giá món, tiền giảm [ĐX].
- **Thời gian:** chuỗi ISO 8601 giờ Việt Nam, ví dụ `"2026-11-16T08:05:00+07:00"`.
- Giá trị liệt kê dùng đúng giá trị `ENUM` trong database (mục 4).

### 1.3 Mã HTTP và mã lỗi dùng chung
| HTTP | `error.code` | Khi nào |
|---|---|---|
| 401 | `AUTH_REQUIRED` | Chưa đăng nhập hoặc hết phiên |
| 403 | `FORBIDDEN` | Vai trò không được dùng chức năng này |
| 403 | `CSRF_INVALID` | Thiếu hoặc sai `X-CSRF-Token` |
| 404 | `NOT_FOUND` | Không có bản ghi |
| 409 | (mã riêng từng endpoint) | Đúng dữ liệu nhưng sai trạng thái nghiệp vụ |
| 422 | `VALIDATION_ERROR` | Dữ liệu gửi lên sai định dạng hoặc thiếu |
| 500 | `SERVER_ERROR` | Lỗi server; không lộ chi tiết kỹ thuật ra ngoài |

## 2. Endpoint

Tất cả endpoint dưới đây: **trạng thái Đã duyệt** (D-65).

### Đợt 1: Nền

| Method, path | Quyền | Mô tả |
|---|---|---|
| `GET /api/health` | Không cần đăng nhập | Kiểm tra server và kết nối database chạy. Trả `{ "data": { "status": "ok" } }` |

Đợt 1 chủ yếu là phần dựng bên trong (router, kết nối PDO, session, kiểm quyền, định dạng lỗi ở mục 1), không có thêm endpoint.

### Đợt 2: Đăng nhập và tài khoản

**`POST /api/auth/login`**: không cần đăng nhập, không cần CSRF.
- Request: `{ "username": "thungan.01", "password": "123456" }`
- 200: `{ "data": { "user": { "id": 3, "full_name": "Minh", "staff_code": "TN01", "role": "cashier" }, "csrf_token": "..." } }`
- Lỗi: 422 `VALIDATION_ERROR` (thiếu trường); 401 `LOGIN_FAILED` (sai tên hoặc mật khẩu, hoặc tài khoản bị khóa; cùng một thông báo để không lộ tài khoản nào tồn tại).
- Server đổi `session_id` sau khi đăng nhập [ĐX]. Vai trò lấy theo tài khoản, không chọn ở màn đăng nhập [D-64].

**`POST /api/auth/logout`**: mọi vai trò. 200 `{ "data": null }`. Hủy session.

**`GET /api/auth/me`**: mọi vai trò. 200 trả `user` và `csrf_token` như login. Frontend gọi khi mở app để biết còn đăng nhập không.

**Tài khoản** (admin tạo, không có đăng ký [D-64], admin chỉ quản lý hệ thống [D-04]):

| Method, path | Quyền | Request | Ghi chú |
|---|---|---|---|
| `GET /api/users` | admin | | Danh sách, không trả `password_hash` |
| `POST /api/users` | admin | `username, password, full_name, staff_code, role` | 409 `USERNAME_TAKEN`, `STAFF_CODE_TAKEN` |
| `PATCH /api/users/{id}` | admin | Một hoặc nhiều trường: `full_name, role, is_active, password` | Không xóa tài khoản, chỉ khóa (`is_active: false`). Admin không tự khóa chính mình (409 `CANNOT_DISABLE_SELF`) |

### Đợt 3: Thực đơn và bàn

**`GET /api/menu`**: waiter, cashier, barista, manager.
- Trả danh mục đang bật, mỗi danh mục kèm món `available` và `sold_out`; món `discontinued` không trả [D-58].
- 200:
  ```json
  { "data": [ { "id": 1, "name": "Cà phê", "products": [
      { "id": 1, "name": "Cà phê sữa", "price": 35000, "description": null, "image_path": null,
        "status": "available", "has_sweetness": true, "has_bitterness": true, "has_ice": true } ] } ] }
  ```

**Quản lý thực đơn** (manager [D-64]):

| Method, path | Request | Ghi chú |
|---|---|---|
| `GET /api/categories` | | Kể cả danh mục đã tắt |
| `POST /api/categories` | `name, sort_order` | 409 `NAME_TAKEN` |
| `PATCH /api/categories/{id}` | `name, sort_order, is_active` | |
| `GET /api/products` | Lọc `?category_id=&status=` | Kể cả món ngừng bán |
| `POST /api/products` | `category_id, name, price, description, has_sweetness, has_bitterness, has_ice` | `price` > 0 |
| `PATCH /api/products/{id}` | Các trường trên và `status` | Đổi giá không ảnh hưởng đơn cũ (đơn lưu giá lúc bán [D-64]) |

Ảnh món: chưa đặc tả, làm sau đợt 5.

**`GET /api/tables`**: waiter, manager.
- Lọc `?area_id=`. Trả khu vực và bàn đang bật, kèm trạng thái tính từ lượt khách đang mở [D-31, D-41].
- 200:
  ```json
  { "data": [ { "id": 1, "name": "Tầng trệt", "tables": [
      { "id": 1, "code": "B01", "status": "occupied", "session": { "id": 1, "started_at": "2026-11-16T08:05:00+07:00", "order_count": 1 } },
      { "id": 3, "code": "B03", "status": "empty", "session": null } ] } ] }
  ```
- Không có số khách [D-10].

**Quản lý khu vực và bàn** (manager [D-64]): `POST /api/table-areas` (`name, sort_order`), `PATCH /api/table-areas/{id}`, `POST /api/tables` (`area_id, code, sort_order`), `PATCH /api/tables/{id}` (`area_id, code, sort_order, is_active`). 409 `CODE_TAKEN`; không tắt được bàn đang có khách (409 `TABLE_OCCUPIED`).

### Đợt 4: Ca thu ngân

**`GET /api/shifts/current`**: mọi vai trò trừ admin.
- 200 khi có ca mở: `{ "data": { "id": 1, "code": "CA1611-01", "cashier": { "id": 3, "full_name": "Minh", "staff_code": "TN01" }, "opened_at": "...", "opening_cash": 500000 } }`
- 200 khi chưa có ca: `{ "data": null }`. Phục vụ dùng để hiện trạng thái "Chưa mở ca" và chặn bán [D-38].

**`POST /api/shifts`**: cashier. Thu ngân tự mở ca [D-47].
- Request: `{ "opening_cash": 500000 }` (số nguyên ≥ 0).
- 201: ca vừa mở (cùng dạng `current`). Server tự sinh `code`.
- Lỗi: 409 `SHIFT_ALREADY_OPEN` (đã có ca mở, kèm tên thu ngân đang giữ ca) [D-47].

**`GET /api/shifts`**: cashier. Chỉ trả các ca **của chính tài khoản này** [D-32].
- Lọc `?date=2026-11-16`. Trả `id, code, status, opened_at, closed_at, opening_cash, closing_cash_counted, expected_cash`.

**`GET /api/shifts/{id}/summary`**: cashier (chỉ ca của mình).
- 200:
  ```json
  { "data": {
      "shift": { "id": 1, "code": "CA1611-01", "status": "open", "opened_at": "...", "closed_at": null },
      "opening_cash": 500000,
      "cash_in": 58000, "cash_refund": 42000,
      "transfer_in": 77000, "transfer_refund": 0,
      "expected_cash": 516000,
      "closing_cash_counted": null, "difference": null,
      "transactions": [
        { "order_code": "SC-1611-001", "type": "payment", "method": "cash", "amount": 30000,
          "handled_by": { "full_name": "Tuấn Anh", "staff_code": "PV01", "role": "waiter" }, "reason": null, "created_at": "..." },
        { "order_code": "SC-1611-003", "type": "refund", "method": "cash", "amount": 42000,
          "handled_by": { "full_name": "Quản lý", "staff_code": "QL01", "role": "manager" }, "reason": "Khách đổi yêu cầu", "created_at": "..." } ] } }
  ```
- Mỗi giao dịch ghi rõ ai thu và vai trò, để biết đơn nào phục vụ thu, đơn nào thu ngân thu [D-33]. Hoàn tiền tính vào ca của đơn [D-49].

**`POST /api/shifts/{id}/close`**: cashier (chỉ ca của mình, đang mở).
- Request: `{ "closing_cash_counted": 516000, "note": null }`.
- Server tính và lưu `expected_cash`, đổi ca sang `closed`. 200 trả `summary` như trên.
- Lỗi: 409 `SHIFT_NOT_OPEN`.

### Đợt 5: Bán hàng (đợt lõi)

Thanh toán xong mới có đơn; tạo đơn, món, tiền, lượt bàn, hóa đơn trong **một transaction** [D-25, D-26, ĐX].

**`POST /api/orders/preview`**: waiter, cashier. Tính tiền trước khi thanh toán, **không lưu gì**.
- Request:
  ```json
  { "order_type": "dine_in", "table_id": 1, "voucher_code": "CAFE5",
    "items": [ { "product_id": 1, "quantity": 1, "sweetness": "less", "bitterness": "normal", "ice": "less", "note": null } ] }
  ```
- 200:
  ```json
  { "data": { "subtotal": 35000, "discount": 5000, "total": 30000,
      "voucher": { "code": "CAFE5", "discount_type": "amount", "discount_value": 5000 },
      "items": [ { "product_id": 1, "product_name": "Cà phê sữa", "unit_price": 35000, "quantity": 1, "line_total": 35000 } ] } }
  ```
- Voucher sai không làm hỏng cả preview: trả 200 với `"voucher": null` và `"voucher_error": "VOUCHER_EXPIRED"`, để màn hình hiện dòng báo lỗi dưới ô voucher.

**`POST /api/orders`**: waiter (chỉ `dine_in`), cashier (chỉ `takeaway`) [D-54, D-55, D-61].
- Request: như preview, thêm `payment`:
  ```json
  { "client_request_id": "8f2c1e4a-3b7d-4c55-9a10-2f6e8d1b0c3e",
    "order_type": "dine_in", "table_id": 1, "voucher_code": "CAFE5",
    "items": [ { "product_id": 1, "quantity": 1, "sweetness": "less", "bitterness": "normal", "ice": "less", "note": null } ],
    "payment": { "method": "cash", "received_amount": 50000 } }
  ```
  - `client_request_id` **bắt buộc**: chuỗi ngẫu nhiên (UUID) do frontend tạo **một lần khi mở màn thanh toán**; bấm lại hoặc gửi lại thì dùng đúng mã cũ [D-66].
  - `table_id` bắt buộc khi `dine_in`, không gửi khi `takeaway`.
  - `received_amount` chỉ dùng khi `cash`, phải ≥ tổng tiền [D-64].
  - Món không cho tùy chỉnh (ví dụ trà không có độ đắng) thì trường đó phải `null`.
- 201:
  ```json
  { "data": { "id": 1, "code": "SC-1611-001", "order_type": "dine_in", "table": { "id": 1, "code": "B01" },
      "subtotal": 35000, "discount": 5000, "total": 30000,
      "payment": { "method": "cash", "amount": 30000, "received_amount": 50000, "change": 20000 },
      "invoice_no": "HD-1611-001", "paid_at": "...",
      "items": [ { "id": 1, "product_name": "Cà phê sữa", "quantity": 1, "unit_price": 35000, "status": "waiting" } ] } }
  ```
- **Gửi trùng:** `client_request_id` đã có đơn thì trả **200** với đúng đơn đó, không tạo đơn mới, không thu tiền lần hai [D-66]. Mã đã dùng bởi người khác: 409 `REQUEST_ID_CONFLICT`.
- Server làm theo thứ tự, lỗi ở bước nào thì không lưu gì:
  1. Có ca mở, nếu không: 409 `NO_OPEN_SHIFT` [D-38].
  2. Vai trò khớp loại đơn, nếu không: 403 `ORDER_TYPE_NOT_ALLOWED`.
  3. Bàn tồn tại và đang bật; món tồn tại và `available`, nếu không: 409 `PRODUCT_UNAVAILABLE` (kèm `product_id`) [D-58].
  4. Voucher (nếu có): tồn tại và bật (422 `VOUCHER_INVALID`), còn hạn (422 `VOUCHER_EXPIRED`), còn lượt (422 `VOUCHER_USED_UP`) [D-48]. Tối đa một voucher [D-57].
  5. Tính tiền. Percent: `discount = floor(subtotal × value / 100)`; amount: `discount = min(value, subtotal)` [ĐX].
  6. Tiền mặt thiếu: 422 `INSUFFICIENT_CASH`.
  7. Bàn chưa có lượt mở thì tạo lượt mới (bàn thành Có khách) [D-31]; có rồi thì gắn đơn vào lượt đó (gọi thêm là đơn mới [D-28]).
  8. Lưu đơn `paid`, món `waiting` (vào hàng chờ pha chế), dòng `payment` với `handled_by` là người đang đăng nhập [D-33], hóa đơn, tăng `used_count` của voucher.

**`GET /api/orders/{id}`**: waiter, cashier, manager. Chi tiết đơn như response ở trên, thêm trạng thái từng món, lý do hủy hoặc đổi, các giao dịch hoàn hoặc thu thêm.

## 3. Quy tắc tương thích frontend và backend

- Frontend **không tự định nghĩa endpoint**. Cần endpoint thì đề xuất theo mục 6.
- Chỉ gọi endpoint "Đã duyệt" như hợp đồng. Endpoint "Đề xuất" có thể còn đổi.
- Không ai tự đổi database schema khi chưa được Hùng phê duyệt.

## 4. Database schema

- Thiết kế: [docs/database-design.md](database-design.md), phiên bản 0.1. PM cho viết SQL ngày 17/11/2026.
- Script: [`database/smartcoffee_v2.sql`](../database/smartcoffee_v2.sql), database `smartcoffee_v2`, 13 bảng. **PM đã import và kiểm thử trên XAMPP ngày 17/11/2026**, ràng buộc "một ca mở" chặn đúng.
- Bản cũ `database/smartcoffee_pos.sql` (luồng cũ) đã xóa khỏi `main` ngày 17/11, còn trong lịch sử git.
- Giá trị liệt kê: xem các cột `ENUM` trong [database-design.md](database-design.md), mục 3.

## 5. Kế hoạch các đợt sau (chưa đặc tả)

- **Đợt 6:** hàng chờ pha chế, đổi trạng thái món, phục vụ xác nhận Đã mang ra, đơn của bàn, trả bàn, chuyển và gộp bàn.
- **Đợt 7:** manager hủy món, đổi món, hủy đơn (có hoàn hoặc thu thêm); quản lý voucher.
- **Đợt 8:** xem và in lại hóa đơn.

## 6. Quy trình đề xuất và phê duyệt thay đổi

Áp dụng cho mọi thay đổi endpoint, định dạng request/response, cấu trúc dữ liệu hoặc database schema.

1. **Phát hiện vấn đề:** ai cũng có quyền, kể cả AI.
2. **Đề xuất:** gửi cho Hùng (comment Jira). Nêu vấn đề, **tối đa 2 phương án**, khuyến nghị, và tác động lên frontend, backend, database, tài liệu.
3. **Phê duyệt:** chỉ Hùng phê duyệt. Chưa có xác nhận rõ ràng thì coi như chưa được phép.
4. **Thực hiện:** sau khi được phê duyệt mới sửa. Cập nhật tài liệu này trong cùng commit với thay đổi.
5. Không tự ý thay đổi hợp đồng đã chốt dù tin rằng đề xuất là đúng.

## 7. Việc đang chờ Hùng quyết

- Đặc tả đợt 6 đến 8 (mục 5).
