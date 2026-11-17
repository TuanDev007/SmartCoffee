-- =====================================================================
-- SmartCoffee v2: luồng thanh toán trước, pha sau
-- Theo docs/database-design.md (0.1, 17/11/2026) và business-rules D-01..D-62.
-- MariaDB 10.4 (XAMPP). Tạo database MỚI tên smartcoffee_v2,
-- không đụng database cũ smartcoffee_pos.
--
-- Tài khoản mẫu (mật khẩu đều là 123456, chỉ dùng khi phát triển):
--   admin, quanly.01, thungan.01, phucvu.01, phache.01
-- =====================================================================

DROP DATABASE IF EXISTS smartcoffee_v2;
CREATE DATABASE smartcoffee_v2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcoffee_v2;

-- ---------------------------------------------------------------------
-- 1. Cấu hình quán (một dòng duy nhất)
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
-- 2. Tài khoản (D-02, D-03, D-14, D-33, D-35)
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    password_hash  VARCHAR(255) NOT NULL,
    full_name      VARCHAR(100) NOT NULL,
    staff_code     VARCHAR(20)  NOT NULL UNIQUE,
    role           ENUM('admin', 'manager', 'cashier', 'waiter', 'barista') NOT NULL,
    is_active      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 3. Thực đơn (D-58)
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL UNIQUE,
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE = InnoDB;

CREATE TABLE products (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    price           INT UNSIGNED NOT NULL,
    description     VARCHAR(255) NULL,
    image_path      VARCHAR(255) NULL,
    status          ENUM('available', 'sold_out', 'discontinued') NOT NULL DEFAULT 'available',
    has_sweetness   TINYINT(1)   NOT NULL DEFAULT 1,
    has_bitterness  TINYINT(1)   NOT NULL DEFAULT 1,
    has_ice         TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id),
    CONSTRAINT ck_products_price CHECK (price > 0),
    INDEX idx_products_category_status (category_id, status)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 4. Bàn và lượt khách (D-10, D-31, D-34, D-41, D-42, D-51, D-53)
--    Bàn "Có khách" = có một lượt khách đang mở (ended_at IS NULL).
-- ---------------------------------------------------------------------
CREATE TABLE table_areas (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(50) NOT NULL UNIQUE,
    sort_order  SMALLINT    NOT NULL DEFAULT 0
) ENGINE = InnoDB;

CREATE TABLE dining_tables (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id     INT UNSIGNED NOT NULL,
    code        VARCHAR(10)  NOT NULL UNIQUE,
    sort_order  SMALLINT     NOT NULL DEFAULT 0,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT fk_tables_area FOREIGN KEY (area_id) REFERENCES table_areas(id)
) ENGINE = InnoDB;

CREATE TABLE table_sessions (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    table_id       INT UNSIGNED NOT NULL,
    started_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_by     INT UNSIGNED NOT NULL,
    ended_at       DATETIME     NULL,
    ended_by       INT UNSIGNED NULL,
    -- Mỗi bàn tối đa một lượt khách đang mở
    open_table_id  INT UNSIGNED AS (CASE WHEN ended_at IS NULL THEN table_id ELSE NULL END) STORED,
    CONSTRAINT fk_sessions_table      FOREIGN KEY (table_id)   REFERENCES dining_tables(id),
    CONSTRAINT fk_sessions_started_by FOREIGN KEY (started_by) REFERENCES users(id),
    CONSTRAINT fk_sessions_ended_by   FOREIGN KEY (ended_by)   REFERENCES users(id),
    CONSTRAINT ck_sessions_end CHECK (ended_at IS NULL OR (ended_by IS NOT NULL AND ended_at >= started_at)),
    UNIQUE KEY uq_sessions_open_table (open_table_id),
    INDEX idx_sessions_table_time (table_id, started_at)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 5. Ca thu ngân (D-32, D-37, D-47)
-- ---------------------------------------------------------------------
CREATE TABLE shifts (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code                  VARCHAR(20)  NOT NULL UNIQUE,
    cashier_id            INT UNSIGNED NOT NULL,
    status                ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    opened_at             DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    opening_cash          INT UNSIGNED NOT NULL,
    closed_at             DATETIME     NULL,
    closing_cash_counted  INT UNSIGNED NULL,
    expected_cash         INT UNSIGNED NULL,
    note                  VARCHAR(255) NULL,
    -- Toàn hệ thống chỉ một ca mở
    open_flag             TINYINT UNSIGNED AS (CASE WHEN status = 'open' THEN 1 ELSE NULL END) STORED,
    CONSTRAINT fk_shifts_cashier FOREIGN KEY (cashier_id) REFERENCES users(id),
    CONSTRAINT ck_shifts_closed CHECK (
        status = 'open'
        OR (closed_at IS NOT NULL AND closing_cash_counted IS NOT NULL AND expected_cash IS NOT NULL AND closed_at >= opened_at)
    ),
    UNIQUE KEY uq_shifts_one_open (open_flag),
    INDEX idx_shifts_cashier_time (cashier_id, opened_at)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 6. Voucher (D-43, D-48, D-57)
-- ---------------------------------------------------------------------
CREATE TABLE vouchers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30)  NOT NULL UNIQUE,
    discount_type   ENUM('percent', 'amount') NOT NULL,
    discount_value  INT UNSIGNED NOT NULL,
    expires_at      DATETIME     NOT NULL,
    usage_limit     INT UNSIGNED NOT NULL,
    used_count      INT UNSIGNED NOT NULL DEFAULT 0,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_by      INT UNSIGNED NOT NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_vouchers_created_by FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT ck_vouchers_value   CHECK (discount_value > 0 AND (discount_type <> 'percent' OR discount_value <= 100)),
    CONSTRAINT ck_vouchers_usage   CHECK (usage_limit > 0 AND used_count <= usage_limit)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 7. Đơn hàng: chỉ tạo khi đã thanh toán (D-25, D-26, D-28, D-35, D-38, D-55, D-61)
-- ---------------------------------------------------------------------
CREATE TABLE orders (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code              VARCHAR(20)  NOT NULL UNIQUE,
    order_type        ENUM('dine_in', 'takeaway') NOT NULL,
    table_session_id  INT UNSIGNED NULL,
    shift_id          INT UNSIGNED NOT NULL,
    created_by        INT UNSIGNED NOT NULL,
    status            ENUM('paid', 'cancelled') NOT NULL DEFAULT 'paid',
    subtotal          INT UNSIGNED NOT NULL,
    voucher_id        INT UNSIGNED NULL,
    discount          INT UNSIGNED NOT NULL DEFAULT 0,
    total             INT UNSIGNED NOT NULL,
    paid_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancelled_by      INT UNSIGNED NULL,
    cancelled_at      DATETIME     NULL,
    cancel_reason     VARCHAR(255) NULL,
    CONSTRAINT fk_orders_session      FOREIGN KEY (table_session_id) REFERENCES table_sessions(id),
    CONSTRAINT fk_orders_shift        FOREIGN KEY (shift_id)         REFERENCES shifts(id),
    CONSTRAINT fk_orders_created_by   FOREIGN KEY (created_by)       REFERENCES users(id),
    CONSTRAINT fk_orders_voucher      FOREIGN KEY (voucher_id)       REFERENCES vouchers(id),
    CONSTRAINT fk_orders_cancelled_by FOREIGN KEY (cancelled_by)     REFERENCES users(id),
    CONSTRAINT ck_orders_type     CHECK ((order_type = 'dine_in' AND table_session_id IS NOT NULL)
                                      OR (order_type = 'takeaway' AND table_session_id IS NULL)),
    CONSTRAINT ck_orders_total    CHECK (discount <= subtotal AND total + discount = subtotal),
    CONSTRAINT ck_orders_voucher  CHECK (voucher_id IS NOT NULL OR discount = 0),
    CONSTRAINT ck_orders_cancel   CHECK (status <> 'cancelled'
                                      OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL)),
    INDEX idx_orders_shift (shift_id, paid_at),
    INDEX idx_orders_session (table_session_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 8. Món trong đơn (D-12, D-29, D-30, D-36, D-56; P-06, P-07, P-08)
-- ---------------------------------------------------------------------
CREATE TABLE order_items (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          INT UNSIGNED NOT NULL,
    product_id        INT UNSIGNED NOT NULL,
    product_name      VARCHAR(100) NOT NULL,
    unit_price        INT UNSIGNED NOT NULL,
    quantity          SMALLINT UNSIGNED NOT NULL,
    sweetness         ENUM('less', 'normal', 'more') NULL,
    bitterness        ENUM('less', 'normal', 'more') NULL,
    ice               ENUM('none', 'less', 'normal', 'more') NULL,
    note              VARCHAR(255) NULL,
    status            ENUM('waiting', 'preparing', 'done', 'served', 'cancelled', 'replaced') NOT NULL DEFAULT 'waiting',
    replaces_item_id  INT UNSIGNED NULL,
    prepared_by       INT UNSIGNED NULL,
    served_by         INT UNSIGNED NULL,
    served_at         DATETIME     NULL,
    cancelled_by      INT UNSIGNED NULL,
    cancelled_at      DATETIME     NULL,
    cancel_reason     VARCHAR(255) NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_order        FOREIGN KEY (order_id)         REFERENCES orders(id),
    CONSTRAINT fk_items_product      FOREIGN KEY (product_id)       REFERENCES products(id),
    CONSTRAINT fk_items_replaces     FOREIGN KEY (replaces_item_id) REFERENCES order_items(id),
    CONSTRAINT fk_items_prepared_by  FOREIGN KEY (prepared_by)      REFERENCES users(id),
    CONSTRAINT fk_items_served_by    FOREIGN KEY (served_by)        REFERENCES users(id),
    CONSTRAINT fk_items_cancelled_by FOREIGN KEY (cancelled_by)     REFERENCES users(id),
    CONSTRAINT ck_items_qty     CHECK (quantity > 0),
    CONSTRAINT ck_items_served  CHECK (status <> 'served' OR (served_by IS NOT NULL AND served_at IS NOT NULL)),
    CONSTRAINT ck_items_cancel  CHECK (status NOT IN ('cancelled', 'replaced')
                                      OR (cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL AND cancel_reason IS NOT NULL)),
    INDEX idx_items_queue (status, created_at),
    INDEX idx_items_order (order_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 9. Sổ thu chi (D-29, D-33, D-36, D-39, D-40, D-49, D-50, D-52, D-60, D-62)
--    payment: thu lần đầu (mỗi đơn đúng một dòng)
--    extra_charge: thu thêm khi đổi món; refund: hoàn tiền
--    shift_id luôn bằng ca của đơn (code kiểm).
-- ---------------------------------------------------------------------
CREATE TABLE payment_transactions (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id          INT UNSIGNED NOT NULL,
    shift_id          INT UNSIGNED NOT NULL,
    type              ENUM('payment', 'extra_charge', 'refund') NOT NULL,
    method            ENUM('cash', 'transfer') NOT NULL,
    amount            INT UNSIGNED NOT NULL,
    received_amount   INT UNSIGNED NULL,
    order_item_id     INT UNSIGNED NULL,
    handled_by        INT UNSIGNED NOT NULL,
    reason            VARCHAR(255) NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Mỗi đơn đúng một dòng payment, nên mỗi đơn một phương thức (D-40)
    payment_order_id  INT UNSIGNED AS (CASE WHEN type = 'payment' THEN order_id ELSE NULL END) STORED,
    CONSTRAINT fk_tx_order      FOREIGN KEY (order_id)      REFERENCES orders(id),
    CONSTRAINT fk_tx_shift      FOREIGN KEY (shift_id)      REFERENCES shifts(id),
    CONSTRAINT fk_tx_item       FOREIGN KEY (order_item_id) REFERENCES order_items(id),
    CONSTRAINT fk_tx_handled_by FOREIGN KEY (handled_by)    REFERENCES users(id),
    CONSTRAINT ck_tx_amount   CHECK (amount > 0),
    CONSTRAINT ck_tx_received CHECK (received_amount IS NULL OR (method = 'cash' AND received_amount >= amount)),
    CONSTRAINT ck_tx_reason   CHECK (type = 'payment' OR reason IS NOT NULL),
    UNIQUE KEY uq_tx_one_payment (payment_order_id),
    INDEX idx_tx_shift (shift_id, created_at),
    INDEX idx_tx_order (order_id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- 10. Hóa đơn (P-11)
-- ---------------------------------------------------------------------
CREATE TABLE invoices (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id         INT UNSIGNED NOT NULL UNIQUE,
    invoice_no       VARCHAR(20)  NOT NULL UNIQUE,
    total            INT UNSIGNED NOT NULL,
    issued_by        INT UNSIGNED NOT NULL,
    issued_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    print_count      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    last_printed_at  DATETIME     NULL,
    CONSTRAINT fk_invoices_order     FOREIGN KEY (order_id)  REFERENCES orders(id),
    CONSTRAINT fk_invoices_issued_by FOREIGN KEY (issued_by) REFERENCES users(id)
) ENGINE = InnoDB;

-- =====================================================================
-- DỮ LIỆU MẪU (khớp màn POS v2; chỉ dùng khi phát triển)
-- =====================================================================

INSERT INTO store_settings (id, store_name, address, phone, wifi_info, bill_footer) VALUES
(1, 'SmartCoffee', '[ĐỊA CHỈ MẪU] Biên Hòa, Đồng Nai', '0900000000', 'SmartCoffee / matkhau123', 'Cảm ơn và hẹn gặp lại!');

-- Mật khẩu 123456 (password_hash của PHP)
SET @pw = '$2y$10$V5hm5ucYnDKEvYuzFFFLSOkCCh7RI.wg50ngtVgGBMtxrKNvHVxDm';
INSERT INTO users (id, username, password_hash, full_name, staff_code, role) VALUES
(1, 'admin',      @pw, 'Quản trị hệ thống', 'AD01', 'admin'),
(2, 'quanly.01',  @pw, 'Quản lý',           'QL01', 'manager'),
(3, 'thungan.01', @pw, 'Minh',              'TN01', 'cashier'),
(4, 'phucvu.01',  @pw, 'Tuấn Anh',          'PV01', 'waiter'),
(5, 'phache.01',  @pw, 'Pha chế 01',        'PC01', 'barista');

INSERT INTO categories (id, name, sort_order) VALUES
(1, 'Cà phê', 1),
(2, 'Trà',    2);

INSERT INTO products (id, category_id, name, price, status, has_sweetness, has_bitterness, has_ice) VALUES
(1, 1, 'Cà phê sữa',     35000, 'available', 1, 1, 1),
(2, 1, 'Cà phê đen',     28000, 'available', 1, 1, 1),
(3, 1, 'Bạc xỉu',        38000, 'available', 1, 1, 1),
(4, 2, 'Trà đào cam sả', 42000, 'available', 1, 0, 1),
(5, 2, 'Trà vải',        40000, 'sold_out',  1, 0, 1);

INSERT INTO table_areas (id, name, sort_order) VALUES
(1, 'Tầng trệt', 1),
(2, 'Sân vườn',  2),
(3, 'Lầu 1',     3);

INSERT INTO dining_tables (id, area_id, code, sort_order) VALUES
(1, 1, 'B01', 1), (2, 1, 'B02', 2), (3, 1, 'B03', 3), (4, 1, 'B04', 4),
(5, 2, 'V01', 1), (6, 2, 'V02', 2), (7, 2, 'V03', 3), (8, 2, 'V04', 4),
(9, 3, 'L01', 1), (10, 3, 'L02', 2), (11, 3, 'L03', 3), (12, 3, 'L04', 4);

INSERT INTO vouchers (id, code, discount_type, discount_value, expires_at, usage_limit, used_count, created_by) VALUES
(1, 'CAFE5',  'amount',   5000, '2026-12-31 23:59:59', 100, 1, 2),
(2, 'CAFE10', 'percent',    10, '2026-11-30 23:59:59',  50, 0, 2);

-- Ca đang mở của thu ngân Minh, tiền đầu ca 500.000 đ
INSERT INTO shifts (id, code, cashier_id, status, opened_at, opening_cash) VALUES
(1, 'CA1611-01', 3, 'open', '2026-11-16 07:00:00', 500000);

-- B01 và B02 đang có khách
INSERT INTO table_sessions (id, table_id, started_at, started_by) VALUES
(1, 1, '2026-11-16 08:05:00', 4),
(2, 2, '2026-11-16 08:20:00', 4);

INSERT INTO orders (id, code, order_type, table_session_id, shift_id, created_by, status, subtotal, voucher_id, discount, total, paid_at) VALUES
-- Phục vụ bán tại B01, voucher CAFE5, tiền mặt
(1, 'SC-1611-001', 'dine_in',  1,    1, 4, 'paid', 35000, 1,    5000, 30000, '2026-11-16 08:05:00'),
-- Thu ngân bán mang đi, tiền mặt
(2, 'SC-1611-002', 'takeaway', NULL, 1, 3, 'paid', 28000, NULL,    0, 28000, '2026-11-16 08:10:00'),
-- Phục vụ bán tại B02, chuyển khoản; sau đó manager hủy 1 món
(3, 'SC-1611-003', 'dine_in',  2,    1, 4, 'paid', 77000, NULL,    0, 77000, '2026-11-16 08:20:00');

INSERT INTO order_items (id, order_id, product_id, product_name, unit_price, quantity, sweetness, bitterness, ice, note, status, prepared_by, served_by, served_at, cancelled_by, cancelled_at, cancel_reason) VALUES
(1, 1, 1, 'Cà phê sữa',     35000, 1, 'less',   'normal', 'less', NULL,        'waiting', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 2, 2, 'Cà phê đen',     28000, 1, 'normal', 'normal', 'normal', 'Ly giấy', 'done',    5,    NULL, NULL, NULL, NULL, NULL),
(3, 3, 1, 'Cà phê sữa',     35000, 1, 'normal', 'normal', 'normal', NULL,      'served',  5,    4, '2026-11-16 08:30:00', NULL, NULL, NULL),
(4, 3, 4, 'Trà đào cam sả', 42000, 1, 'normal', NULL,     'normal', NULL,      'cancelled', 5,  4, '2026-11-16 08:31:00', 2, '2026-11-16 08:40:00', 'Khách đổi yêu cầu');

INSERT INTO payment_transactions (order_id, shift_id, type, method, amount, received_amount, order_item_id, handled_by, reason, created_at) VALUES
(1, 1, 'payment', 'cash',     30000, 50000, NULL, 4, NULL, '2026-11-16 08:05:00'),
(2, 1, 'payment', 'cash',     28000, 30000, NULL, 3, NULL, '2026-11-16 08:10:00'),
(3, 1, 'payment', 'transfer', 77000, NULL,  NULL, 4, NULL, '2026-11-16 08:20:00'),
-- Đơn chuyển khoản nhưng hoàn bằng tiền mặt trong két (D-62)
(3, 1, 'refund',  'cash',     42000, NULL,  4,    2, 'Khách đổi yêu cầu', '2026-11-16 08:40:00');

INSERT INTO invoices (order_id, invoice_no, total, issued_by, issued_at, print_count, last_printed_at) VALUES
(1, 'HD-1611-001', 30000, 4, '2026-11-16 08:05:00', 1, '2026-11-16 08:05:00'),
(2, 'HD-1611-002', 28000, 3, '2026-11-16 08:10:00', 1, '2026-11-16 08:10:00'),
(3, 'HD-1611-003', 77000, 4, '2026-11-16 08:20:00', 1, '2026-11-16 08:20:00');

-- =====================================================================
-- TRUY VẤN KIỂM TRA NHANH (chạy sau khi import)
-- =====================================================================
-- Bàn đang có khách:
--   SELECT t.code FROM dining_tables t JOIN table_sessions s ON s.open_table_id = t.id;
-- Tiền mặt dự kiến của ca đang mở (500.000 + 30.000 + 28.000 − 42.000 = 516.000):
--   SELECT s.opening_cash
--        + COALESCE(SUM(CASE WHEN x.method = 'cash' AND x.type IN ('payment','extra_charge') THEN x.amount END), 0)
--        - COALESCE(SUM(CASE WHEN x.method = 'cash' AND x.type = 'refund' THEN x.amount END), 0) AS expected_cash
--   FROM shifts s LEFT JOIN payment_transactions x ON x.shift_id = s.id WHERE s.status = 'open' GROUP BY s.id;
-- Hàng chờ pha chế:
--   SELECT id, product_name, status FROM order_items WHERE status IN ('waiting','preparing') ORDER BY created_at;
