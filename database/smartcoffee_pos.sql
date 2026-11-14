-- =====================================================================
-- SMARTCOFFEE - Phần mềm quản trị + POS gọi món (bản 1)
-- Phạm vi: 1 quán, nhân viên phục vụ gọi món bằng điện thoại,
--          quầy pha chế nhận món, thu ngân thanh toán tại bàn và in hóa đơn.
-- Hệ quản trị: MySQL 8.0+ hoặc MariaDB 10.4+ (XAMPP)   |   Bảng mã: utf8mb4
-- Tiền tệ: VND, lưu bằng số nguyên (không có phần lẻ).
-- =====================================================================

DROP DATABASE IF EXISTS smartcoffee_pos;
CREATE DATABASE smartcoffee_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcoffee_pos;

-- ---------------------------------------------------------------------
-- 1. THÔNG TIN QUÁN (1 dòng duy nhất, dùng để in đầu hóa đơn)
-- ---------------------------------------------------------------------
CREATE TABLE store_settings (
    id            TINYINT UNSIGNED PRIMARY KEY CHECK (id = 1),
    store_name    VARCHAR(150) NOT NULL,
    address       VARCHAR(255) NOT NULL,
    phone         VARCHAR(20)  NOT NULL,
    wifi_info     VARCHAR(100) NULL,
    bill_footer   VARCHAR(255) NULL
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 2. NHÂN VIÊN
-- ---------------------------------------------------------------------
-- role quyết định nhân viên được làm gì:
--   admin    : chỉ quản lý hệ thống (tài khoản, cài đặt); KHÔNG bán hàng, KHÔNG hủy đơn
--   manager  : chủ quán; làm được mọi việc bán hàng, quản lý món, bàn, xem báo cáo,
--              và là người DUY NHẤT hủy món/đơn đã gửi quầy pha chế
--   cashier  : thanh toán, in hóa đơn
--   waiter   : mở đơn, gọi món, gửi món, chuyển bàn; chỉ tự hủy được món/đơn còn là nháp
--   barista  : xem hàng chờ ở quầy, đổi trạng thái món
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100) NOT NULL,
    phone           VARCHAR(20)  NULL,
    username        VARCHAR(50)  NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin', 'manager', 'cashier', 'waiter', 'barista') NOT NULL,
    is_active       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. THỰC ĐƠN
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL UNIQUE,
    sort_order   SMALLINT NOT NULL DEFAULT 0,
    is_active    BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE = InnoDB;

-- is_available = FALSE: tạm hết món trong ngày (vẫn hiện, không gọi được)
-- is_active    = FALSE: ngừng bán hẳn (ẩn khỏi menu, giữ lại để báo cáo đơn cũ)
CREATE TABLE products (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id    INT UNSIGNED NOT NULL,
    name           VARCHAR(150) NOT NULL,
    price          INT UNSIGNED NOT NULL CHECK (price > 0),
    image          VARCHAR(255) NULL,
    is_available   BOOLEAN NOT NULL DEFAULT TRUE,
    is_active      BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. BÀN
-- ---------------------------------------------------------------------
-- Bàn đang có khách hay trống được suy ra từ đơn đang mở (orders.status = 'open'),
-- không lưu riêng để tránh hai nơi ghi lệch nhau.
CREATE TABLE dining_tables (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(10)  NOT NULL UNIQUE,
    area        VARCHAR(50)  NOT NULL,
    capacity    TINYINT UNSIGNED NOT NULL CHECK (capacity > 0),
    is_active   BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 5. ĐƠN GỌI MÓN
-- ---------------------------------------------------------------------
-- Hủy đơn: đơn chỉ có món nháp thì waiter hủy được; đơn đã có món gửi quầy thì chỉ
-- manager hủy và phải nhập cancel_reason (ứng dụng kiểm tra, cơ sở dữ liệu chỉ bắt buộc
-- cancelled_by). Đơn đã thanh toán (paid) không hủy trong hệ thống.
-- subtotal chỉ cộng các món ĐÃ GỬI quầy và chưa hủy (món nháp chưa tính). Mỗi lần
-- bấm "Gửi món" hoặc hủy món, ứng dụng tính lại subtotal.
-- Một đơn = một lượt khách. Chuyển bàn = đổi table_id của đơn đang mở.
-- Đơn tại bàn bắt buộc có bàn; đơn mang đi không có bàn.
-- open_table_id: cột tự tính, chỉ có giá trị khi đơn đang mở. Gắn UNIQUE vào cột này
-- để mỗi bàn chỉ có tối đa 1 đơn đang mở (đơn đã đóng thì không bị giới hạn).
CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20) NOT NULL UNIQUE,
    order_type      ENUM('dine_in', 'takeaway') NOT NULL DEFAULT 'dine_in',
    table_id        INT UNSIGNED NULL,
    status          ENUM('open', 'paid', 'cancelled') NOT NULL DEFAULT 'open',
    opened_by       INT UNSIGNED NOT NULL,
    opened_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    closed_at       DATETIME NULL,
    subtotal        INT UNSIGNED NOT NULL DEFAULT 0,
    discount        INT UNSIGNED NOT NULL DEFAULT 0,
    total           INT UNSIGNED NOT NULL DEFAULT 0,
    note            VARCHAR(255) NULL,
    cancelled_by    INT UNSIGNED NULL,
    cancel_reason   VARCHAR(255) NULL,
    open_table_id   INT UNSIGNED AS (CASE WHEN status = 'open' THEN table_id ELSE NULL END) STORED,
    CONSTRAINT fk_orders_table     FOREIGN KEY (table_id)  REFERENCES dining_tables(id),
    CONSTRAINT fk_orders_opened_by FOREIGN KEY (opened_by) REFERENCES users(id),
    CONSTRAINT fk_orders_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id),
    CONSTRAINT ck_orders_cancel    CHECK (status <> 'cancelled' OR cancelled_by IS NOT NULL),
    CONSTRAINT ck_orders_table     CHECK (order_type = 'takeaway' OR table_id IS NOT NULL),
    CONSTRAINT ck_orders_total     CHECK (discount <= subtotal AND total + discount = subtotal),
    UNIQUE KEY uq_orders_open_table (open_table_id),
    INDEX idx_orders_status_time (status, opened_at)
) ENGINE = InnoDB;

-- Mỗi dòng là một lần gọi một món. Khách gọi thêm cùng món thì thêm dòng mới,
-- để quầy pha chế thấy đó là món mới cần làm.
-- unit_price: giá TẠI LÚC GỌI (đổi giá món sau này không làm sai đơn cũ).
-- note: yêu cầu riêng như ít đá, ít đường, size L.
-- status: vòng đời của món
--   draft (nháp, chưa gửi quầy; waiter còn sửa, xóa được; quầy pha chế chưa thấy)
--     -> bấm "Gửi món" -> pending (chờ) -> preparing (đang pha) -> ready (xong)
--     -> served (đã mang ra)
--   cancelled: hủy món ĐÃ GỬI (không tính tiền). Chỉ manager hủy, bắt buộc có
--     cancelled_by và cancel_reason. Món nháp thì xóa hẳn dòng, không cần lưu vết.
CREATE TABLE order_items (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    quantity      SMALLINT UNSIGNED NOT NULL CHECK (quantity > 0),
    unit_price    INT UNSIGNED NOT NULL,
    note          VARCHAR(255) NULL,
    status        ENUM('draft', 'pending', 'preparing', 'ready', 'served', 'cancelled') NOT NULL DEFAULT 'draft',
    cancelled_by  INT UNSIGNED NULL,
    cancel_reason VARCHAR(255) NULL,
    created_by    INT UNSIGNED NOT NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_order      FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product    FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_items_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_items_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users(id),
    CONSTRAINT ck_items_cancel     CHECK (status <> 'cancelled' OR (cancelled_by IS NOT NULL AND cancel_reason IS NOT NULL)),
    INDEX idx_items_queue (status, created_at)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 6. THANH TOÁN VÀ HÓA ĐƠN
-- ---------------------------------------------------------------------
-- Tách riêng khỏi orders để một đơn có thể trả bằng nhiều cách
-- (ví dụ một phần tiền mặt, một phần chuyển khoản).
-- received_amount: tiền khách đưa (tiền mặt), dùng để tính tiền thối.
CREATE TABLE payments (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          INT UNSIGNED NOT NULL,
    method            ENUM('cash', 'transfer', 'card') NOT NULL,
    amount            INT UNSIGNED NOT NULL CHECK (amount > 0),
    received_amount   INT UNSIGNED NULL,
    cashier_id        INT UNSIGNED NOT NULL,
    paid_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_order   FOREIGN KEY (order_id)   REFERENCES orders(id),
    CONSTRAINT fk_payments_cashier FOREIGN KEY (cashier_id) REFERENCES users(id),
    CONSTRAINT ck_payments_received CHECK (received_amount IS NULL OR received_amount >= amount)
) ENGINE = InnoDB;

-- Mỗi đơn đã thanh toán có đúng 1 hóa đơn. print_count đếm số lần in (in lại khi khách xin).
-- total lưu lại số tiền trên hóa đơn tại thời điểm xuất.
CREATE TABLE invoices (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id      INT UNSIGNED NOT NULL UNIQUE,
    invoice_no    VARCHAR(20) NOT NULL UNIQUE,
    total         INT UNSIGNED NOT NULL,
    issued_by     INT UNSIGNED NOT NULL,
    issued_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    print_count   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_invoices_order     FOREIGN KEY (order_id)  REFERENCES orders(id),
    CONSTRAINT fk_invoices_issued_by FOREIGN KEY (issued_by) REFERENCES users(id)
) ENGINE = InnoDB;

-- =====================================================================
-- DỮ LIỆU MẪU
-- =====================================================================

INSERT INTO store_settings (id, store_name, address, phone, wifi_info, bill_footer) VALUES
(1, 'SmartCoffee', '[ĐỊA CHỈ MẪU] Biên Hòa, Đồng Nai', '0900000000', 'SmartCoffee / matkhau123', 'Cảm ơn quý khách, hẹn gặp lại!');

-- Mật khẩu chưa được mã hóa thật vì chưa có backend.
-- Khi làm PHP: tạo bằng password_hash('...', PASSWORD_DEFAULT) rồi cập nhật cột này.
INSERT INTO users (full_name, phone, username, password_hash, role) VALUES
('Quản trị hệ thống', '0900000001', 'admin',    'CHUA_MA_HOA', 'admin'),    -- id 1
('Chủ quán',          '0900000002', 'quanly',   'CHUA_MA_HOA', 'manager'),  -- id 2
('Thu ngân Mai',      '0900000003', 'thungan',  'CHUA_MA_HOA', 'cashier'),  -- id 3
('Phục vụ Khang',     '0900000004', 'phucvu',   'CHUA_MA_HOA', 'waiter'),   -- id 4
('Pha chế Phúc',      '0900000005', 'phache',   'CHUA_MA_HOA', 'barista');  -- id 5

INSERT INTO categories (name, sort_order) VALUES
('Cà phê', 1),       -- id 1
('Trà', 2),          -- id 2
('Nước ép', 3),      -- id 3
('Bánh', 4);         -- id 4

INSERT INTO products (category_id, name, price, is_available) VALUES
(1, 'Cà phê đen đá',          25000, TRUE),   -- id 1
(1, 'Cà phê sữa đá',          29000, TRUE),   -- id 2
(1, 'Bạc xỉu',                32000, TRUE),   -- id 3
(1, 'Cà phê muối',            35000, TRUE),   -- id 4
(2, 'Trà đào cam sả',         39000, TRUE),   -- id 5
(2, 'Trà vải',                35000, TRUE),   -- id 6
(3, 'Nước cam',               35000, TRUE),   -- id 7
(4, 'Bánh croissant',         30000, FALSE);  -- id 8 (tạm hết)

INSERT INTO dining_tables (code, area, capacity) VALUES
('B01', 'Tầng trệt', 2),  -- id 1
('B02', 'Tầng trệt', 4),  -- id 2
('B03', 'Tầng trệt', 4),  -- id 3
('B04', 'Sân vườn', 4),   -- id 4
('B05', 'Sân vườn', 6),   -- id 5
('L01', 'Lầu 1', 2),      -- id 6
('L02', 'Lầu 1', 4),      -- id 7
('L03', 'Lầu 1', 8);      -- id 8

-- Đơn 1: bàn B02, đã thanh toán và in hóa đơn (in lại 1 lần)
-- Đơn 2: bàn B04, đang phục vụ, có món ở nhiều trạng thái khác nhau (kể cả 1 món nháp)
-- Đơn 3: mang đi, đã thanh toán bằng chuyển khoản
-- Đơn 4: bàn L01, khách bỏ về nên manager hủy (id 2)
INSERT INTO orders (code, order_type, table_id, status, opened_by, opened_at, closed_at, subtotal, discount, total, cancelled_by, cancel_reason) VALUES
('DH-20261114-001', 'dine_in',  2,    'paid',      4, '2026-11-14 08:05:00', '2026-11-14 09:10:00', 128000, 8000, 120000, NULL, NULL),
('DH-20261114-002', 'dine_in',  4,    'open',      4, '2026-11-14 09:30:00', NULL,                  138000,    0, 138000, NULL, NULL),
('DH-20261114-003', 'takeaway', NULL, 'paid',      3, '2026-11-14 09:40:00', '2026-11-14 09:45:00',  58000,    0,  58000, NULL, NULL),
('DH-20261114-004', 'dine_in',  6,    'cancelled', 4, '2026-11-14 10:00:00', '2026-11-14 10:05:00',      0,    0,      0, 2,    'Khách bỏ về');

INSERT INTO order_items (order_id, product_id, quantity, unit_price, note, status, created_by, created_at, cancelled_by, cancel_reason) VALUES
-- Đơn 1: 2×29.000 + 35.000 + 35.000 = 128.000 (cà phê đen đã hủy, không tính); giảm 8.000 -> 120.000
(1, 2, 2, 29000, NULL,            'served', 4, '2026-11-14 08:06:00', NULL, NULL),
(1, 4, 1, 35000, 'ít ngọt',       'served', 4, '2026-11-14 08:06:00', NULL, NULL),
(1, 6, 1, 35000, NULL,            'served', 4, '2026-11-14 08:20:00', NULL, NULL),
(1, 1, 1, 25000, NULL,            'cancelled', 4, '2026-11-14 08:21:00', 2, 'Khách đổi món'),
-- Đơn 2: món đã mang ra, món đang pha, món vừa gửi, 1 món nháp (chưa tính tiền); 2×32.000 + 39.000 + 35.000 = 138.000
(2, 3, 2, 32000, NULL,            'served',    4, '2026-11-14 09:31:00', NULL, NULL),
(2, 5, 1, 39000, 'ít đá',         'preparing', 4, '2026-11-14 09:31:00', NULL, NULL),
(2, 4, 1, 35000, NULL,            'pending',   4, '2026-11-14 09:50:00', NULL, NULL),
(2, 7, 1, 35000, 'không đường',   'cancelled', 4, '2026-11-14 09:52:00', 2, 'Khách không muốn gọi nữa'),
(2, 1, 1, 25000, 'ít đường',      'draft',     4, '2026-11-14 10:20:00', NULL, NULL),
-- Đơn 3: mang đi
(3, 2, 2, 29000, NULL,            'served', 3, '2026-11-14 09:40:00', NULL, NULL),
-- Đơn 4: hủy cả đơn
(4, 1, 1, 25000, NULL,            'cancelled', 4, '2026-11-14 10:00:00', 2, 'Khách bỏ về');

INSERT INTO payments (order_id, method, amount, received_amount, cashier_id, paid_at) VALUES
(1, 'cash',     100000, 100000, 3, '2026-11-14 09:10:00'),
(1, 'transfer',  20000, NULL,   3, '2026-11-14 09:10:00'),
(3, 'transfer',  58000, NULL,   3, '2026-11-14 09:45:00');

INSERT INTO invoices (order_id, invoice_no, total, issued_by, issued_at, print_count) VALUES
(1, 'HD-000001', 120000, 3, '2026-11-14 09:10:00', 2),
(3, 'HD-000002',  58000, 3, '2026-11-14 09:45:00', 1);

-- =====================================================================
-- TRUY VẤN MẪU (bỏ dấu -- ở đầu dòng để chạy thử)
-- =====================================================================

-- 1. Sơ đồ bàn: bàn nào đang có khách
-- SELECT t.code, t.area,
--        CASE WHEN o.id IS NULL THEN 'Trống' ELSE 'Có khách' END AS trang_thai,
--        o.code AS don_dang_mo
-- FROM dining_tables t
-- LEFT JOIN orders o ON o.table_id = t.id AND o.status = 'open'
-- WHERE t.is_active = TRUE
-- ORDER BY t.code;

-- 2. Hàng chờ ở quầy pha chế: món chờ và đang pha, món gọi trước làm trước
-- SELECT oi.id, t.code AS ban, p.name AS mon, oi.quantity, oi.note, oi.status, oi.created_at
-- FROM order_items oi
-- JOIN orders o   ON o.id = oi.order_id
-- JOIN products p ON p.id = oi.product_id
-- LEFT JOIN dining_tables t ON t.id = o.table_id
-- WHERE oi.status IN ('pending', 'preparing') AND o.status = 'open'
-- ORDER BY oi.created_at;

-- 3. Chuyển bàn: chuyển đơn đang mở từ bàn B04 (id 4) sang bàn B05 (id 5)
--    Nếu bàn mới đang có đơn mở, ràng buộc uq_orders_open_table sẽ báo lỗi.
-- UPDATE orders SET table_id = 5 WHERE table_id = 4 AND status = 'open';

-- 4. Kiểm tra tổng tiền đơn khớp với các món đã gửi, chưa hủy (kết quả rỗng là khớp)
-- SELECT o.code, o.subtotal, COALESCE(SUM(oi.quantity * oi.unit_price), 0) AS tinh_lai
-- FROM orders o
-- LEFT JOIN order_items oi ON oi.order_id = o.id AND oi.status NOT IN ('draft', 'cancelled')
-- WHERE o.status <> 'cancelled'
-- GROUP BY o.id, o.code, o.subtotal
-- HAVING o.subtotal <> COALESCE(SUM(oi.quantity * oi.unit_price), 0);

-- 5. Doanh thu theo ngày (chỉ tính đơn đã thanh toán)
-- SELECT DATE(closed_at) AS ngay, COUNT(*) AS so_don, SUM(total) AS doanh_thu
-- FROM orders
-- WHERE status = 'paid'
-- GROUP BY DATE(closed_at)
-- ORDER BY ngay;

-- 6. Dữ liệu in hóa đơn của đơn số 1
-- SELECT s.store_name, s.address, s.phone, i.invoice_no, i.issued_at, t.code AS ban,
--        p.name, oi.quantity, oi.unit_price, oi.quantity * oi.unit_price AS thanh_tien,
--        o.subtotal, o.discount, o.total, s.bill_footer
-- FROM invoices i
-- JOIN orders o       ON o.id = i.order_id
-- JOIN order_items oi ON oi.order_id = o.id AND oi.status NOT IN ('draft', 'cancelled')
-- JOIN products p     ON p.id = oi.product_id
-- LEFT JOIN dining_tables t ON t.id = o.table_id
-- CROSS JOIN store_settings s
-- WHERE o.id = 1;

-- 7. Gửi món: đẩy các món nháp của đơn 2 tới quầy pha chế
--    (sau đó ứng dụng tính lại subtotal của đơn)
-- UPDATE order_items SET status = 'pending' WHERE order_id = 2 AND status = 'draft';

-- 8. Waiter hủy món nháp: xóa hẳn dòng, chỉ xóa được món còn nháp
-- DELETE FROM order_items WHERE id = 9 AND status = 'draft';

-- 9. Manager hủy món đã gửi: bắt buộc có người hủy và lý do (thiếu là báo lỗi CHECK)
-- UPDATE order_items
-- SET status = 'cancelled', cancelled_by = 2, cancel_reason = 'Pha sai món'
-- WHERE id = 7 AND status IN ('pending', 'preparing', 'ready', 'served');
