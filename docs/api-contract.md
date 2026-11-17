# SmartCoffee: Hợp đồng API và dữ liệu

**Trạng thái: hợp đồng API CHƯA được đặc tả.** Tài liệu này chỉ ghi những gì đã được xác nhận. Phần chưa có nguồn xác nhận ghi rõ "Chưa được đặc tả". Không tự suy diễn hay sáng tạo endpoint hoặc schema.

**Chỉ Hùng được phê duyệt thay đổi hợp đồng API và database schema.** Thành viên và AI khác được phát hiện vấn đề và đề xuất phương án, nhưng không tự ý thay đổi hợp đồng đã chốt (xem mục 5).

Nghiệp vụ nằm ở [business-rules.md](business-rules.md), không lặp lại ở đây.

Tài liệu tách ba loại: **đã phê duyệt** (hiện gần như chưa có, xem mục 1), **hiện trạng triển khai** (schema trong SQL, mục 3 và 4) và **bản nháp chưa duyệt** (`docs/04-api.md`). SQL và bản nháp **không phải nguồn phê duyệt** nghiệp vụ hay hợp đồng.

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

`docs/04-api.md` là bản nháp do AI soạn, **chưa được Hùng phê duyệt và không phải hợp đồng**. Không dùng làm căn cứ. File được giữ nguyên, chưa di chuyển, chưa xóa (xem mục 6).

## 2. Quy tắc tương thích frontend và backend

- Frontend **không tự định nghĩa endpoint**. Cần endpoint thì gửi đề xuất theo mục 5 và chờ Hùng phê duyệt.
- Cho tới khi một endpoint được đặc tả ở mục 1, không code gọi endpoint đó như thể đã tồn tại.
- Không ai tự đổi database schema hiện có (mục 3) khi chưa được Hùng phê duyệt.
- Tên trường và kiểu dữ liệu dùng giữa frontend và backend: **Chưa được đặc tả** (phụ thuộc mục 1).

## 3. Database schema hiện có trong SQL (hiện trạng, CHƯA phải hợp đồng dữ liệu được phê duyệt)

**Hợp đồng dữ liệu được phê duyệt: chưa có.** Hùng chưa phê duyệt chính thức schema nào làm hợp đồng.

Phần dưới đây chỉ **mô tả hiện trạng** của [`database/smartcoffee_pos.sql`](../database/smartcoffee_pos.sql) (MySQL 8.0+ hoặc MariaDB 10.4+, utf8mb4), trích tự động từ các lệnh `CREATE TABLE` để tra cứu. Mâu thuẫn giữa bảng dưới và file SQL thì file SQL là hiện trạng đúng. Việc schema này tồn tại không có nghĩa là nó đã được phê duyệt; thay đổi nó vẫn phải theo mục 5.

Ghi nhận từ hiện trạng: tiền lưu bằng số nguyên; không có cột số lượng khách; không có cột trạng thái bàn (suy ra từ đơn đang mở). Tên bảng, cột, giá trị liệt kê và chú thích trong SQL **không dùng để suy ra nghiệp vụ đã chốt**; nghiệp vụ đã chốt chỉ nằm ở [business-rules.md](business-rules.md), mục 1, 2 và 6.

### `store_settings`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `TINYINT UNSIGNED` | PRIMARY KEY CHECK (id = 1) |
| `store_name` | `VARCHAR(150)` | NOT NULL |
| `address` | `VARCHAR(255)` | NOT NULL |
| `phone` | `VARCHAR(20)` | NOT NULL |
| `wifi_info` | `VARCHAR(100)` | NULL |
| `bill_footer` | `VARCHAR(255)` | NULL |

### `users`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `full_name` | `VARCHAR(100)` | NOT NULL |
| `phone` | `VARCHAR(20)` | NULL |
| `username` | `VARCHAR(50)` | NOT NULL UNIQUE |
| `password_hash` | `VARCHAR(255)` | NOT NULL |
| `role` | `ENUM('admin', 'manager', 'cashier', 'waiter', 'barista')` | NOT NULL |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT TRUE |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP |

### `categories`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `name` | `VARCHAR(100)` | NOT NULL UNIQUE |
| `sort_order` | `SMALLINT` | NOT NULL DEFAULT 0 |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT TRUE |

### `products`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `category_id` | `INT UNSIGNED` | NOT NULL |
| `name` | `VARCHAR(150)` | NOT NULL |
| `price` | `INT UNSIGNED` | NOT NULL CHECK (price > 0) |
| `image` | `VARCHAR(255)` | NULL |
| `is_available` | `BOOLEAN` | NOT NULL DEFAULT TRUE |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT TRUE |

### `dining_tables`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `code` | `VARCHAR(10)` | NOT NULL UNIQUE |
| `area` | `VARCHAR(50)` | NOT NULL |
| `capacity` | `TINYINT UNSIGNED` | NOT NULL CHECK (capacity > 0) |
| `is_active` | `BOOLEAN` | NOT NULL DEFAULT TRUE |

### `orders`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `code` | `VARCHAR(20)` | NOT NULL UNIQUE |
| `order_type` | `ENUM('dine_in', 'takeaway')` | NOT NULL DEFAULT 'dine_in' |
| `table_id` | `INT UNSIGNED` | NULL |
| `status` | `ENUM('open', 'paid', 'cancelled')` | NOT NULL DEFAULT 'open' |
| `opened_by` | `INT UNSIGNED` | NOT NULL |
| `opened_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP |
| `closed_at` | `DATETIME` | NULL |
| `subtotal` | `INT UNSIGNED` | NOT NULL DEFAULT 0 |
| `discount` | `INT UNSIGNED` | NOT NULL DEFAULT 0 |
| `total` | `INT UNSIGNED` | NOT NULL DEFAULT 0 |
| `note` | `VARCHAR(255)` | NULL |
| `cancelled_by` | `INT UNSIGNED` | NULL |
| `cancel_reason` | `VARCHAR(255)` | NULL |
| `open_table_id` | `INT UNSIGNED` | AS (CASE WHEN status = 'open' THEN table_id ELSE NULL END) STORED |

### `order_items`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `order_id` | `INT UNSIGNED` | NOT NULL |
| `product_id` | `INT UNSIGNED` | NOT NULL |
| `quantity` | `SMALLINT UNSIGNED` | NOT NULL CHECK (quantity > 0) |
| `unit_price` | `INT UNSIGNED` | NOT NULL |
| `note` | `VARCHAR(255)` | NULL |
| `status` | `ENUM('draft', 'pending', 'preparing', 'ready', 'served', 'cancelled')` | NOT NULL DEFAULT 'draft' |
| `cancelled_by` | `INT UNSIGNED` | NULL |
| `cancel_reason` | `VARCHAR(255)` | NULL |
| `created_by` | `INT UNSIGNED` | NOT NULL |
| `created_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP |
| `updated_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP |

### `payments`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `order_id` | `INT UNSIGNED` | NOT NULL |
| `method` | `ENUM('cash', 'transfer', 'card')` | NOT NULL |
| `amount` | `INT UNSIGNED` | NOT NULL CHECK (amount > 0) |
| `received_amount` | `INT UNSIGNED` | NULL |
| `cashier_id` | `INT UNSIGNED` | NOT NULL |
| `paid_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP |

### `invoices`

| Cột | Kiểu | Ràng buộc |
|---|---|---|
| `id` | `INT UNSIGNED` | AUTO_INCREMENT PRIMARY KEY |
| `order_id` | `INT UNSIGNED` | NOT NULL UNIQUE |
| `invoice_no` | `VARCHAR(20)` | NOT NULL UNIQUE |
| `total` | `INT UNSIGNED` | NOT NULL |
| `issued_by` | `INT UNSIGNED` | NOT NULL |
| `issued_at` | `DATETIME` | NOT NULL DEFAULT CURRENT_TIMESTAMP |
| `print_count` | `SMALLINT UNSIGNED` | NOT NULL DEFAULT 1 |

Ràng buộc ở mức bảng (khóa ngoại, `CHECK`, `UNIQUE`, chỉ mục) xem trong file SQL. Hiện trạng đáng chú ý: `orders.open_table_id` là cột tự tính để mỗi bàn chỉ có một đơn đang mở; đơn và món ở trạng thái hủy bắt buộc có `cancelled_by`; món bị hủy bắt buộc có `cancel_reason`, còn đơn bị hủy thì SQL không bắt buộc `cancel_reason`.

## 4. Giá trị liệt kê hiện có trong SQL (hiện trạng)

| Trường | Giá trị |
|---|---|
| `users.role` | `admin`, `manager`, `cashier`, `waiter`, `barista` |
| `orders.order_type` | `dine_in`, `takeaway` |
| `orders.status` | `open`, `paid`, `cancelled` |
| `order_items.status` | `draft`, `pending`, `preparing`, `ready`, `served`, `cancelled` |
| `payments.method` | `cash`, `transfer`, `card` |

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
- `docs/04-api.md`: **giữ nguyên ở trạng thái bản nháp chưa phê duyệt, chưa di chuyển, chưa xóa** (quyết định D-21). Bước tiếp theo do Hùng quyết.
