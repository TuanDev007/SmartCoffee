-- =====================================================================
-- SMARTCOFFEE - Cơ sở dữ liệu Giai đoạn 1 (WEB212, Nhóm 2)
-- Bám theo ERD ở mục 3.3 của báo cáo (Hình 3).
-- Hệ quản trị: MySQL 8.0+ hoặc MariaDB 10.4+ (bản đi kèm XAMPP)
-- Bảng mã: utf8mb4
--
-- Cách nhập: mở phpMyAdmin (XAMPP) > Import > chọn file này > Go
--      hoặc: mysql -u root -p < database/smartcoffee.sql
-- =====================================================================

DROP DATABASE IF EXISTS smartcoffee;
CREATE DATABASE smartcoffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smartcoffee;

-- ---------------------------------------------------------------------
-- users: tài khoản quản trị và khách hàng
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    phone           VARCHAR(20)  NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('admin', 'staff', 'customer') NOT NULL DEFAULT 'customer',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- categories, products: thực đơn
-- ---------------------------------------------------------------------
CREATE TABLE categories (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name   VARCHAR(100) NOT NULL,
    slug   VARCHAR(120) NOT NULL UNIQUE
) ENGINE = InnoDB;

CREATE TABLE products (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    slug          VARCHAR(160) NOT NULL UNIQUE,
    description   TEXT NULL,
    price         DECIMAL(10, 2) NOT NULL CHECK (price >= 0),
    image         VARCHAR(255) NULL,
    is_active     BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- cafe_tables, reservations: đặt bàn
-- ---------------------------------------------------------------------
CREATE TABLE cafe_tables (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(10) NOT NULL UNIQUE,
    capacity   TINYINT UNSIGNED NOT NULL CHECK (capacity > 0),
    status     ENUM('available', 'maintenance') NOT NULL DEFAULT 'available'
) ENGINE = InnoDB;

-- user_id được phép NULL: khách vãng lai đặt bàn không cần tài khoản,
-- khi đó lưu tên và số điện thoại liên hệ.
-- Quy tắc "guest_count không vượt capacity" và "không đặt trùng giờ"
-- cần so với bảng khác nên sẽ kiểm tra ở backend (PHP), không dùng CHECK.
CREATE TABLE reservations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NULL,
    table_id        INT UNSIGNED NULL,
    contact_name    VARCHAR(100) NOT NULL,
    contact_phone   VARCHAR(20)  NOT NULL,
    reserved_at     DATETIME NOT NULL,
    guest_count     TINYINT UNSIGNED NOT NULL CHECK (guest_count > 0),
    note            VARCHAR(255) NULL,
    status          ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reservations_user  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_reservations_table FOREIGN KEY (table_id) REFERENCES cafe_tables(id) ON DELETE SET NULL,
    INDEX idx_reservations_time (table_id, reserved_at)
) ENGINE = InnoDB;

-- ---------------------------------------------------------------------
-- orders, order_items: đơn hàng
-- ---------------------------------------------------------------------
-- total_amount là dữ liệu tính được từ order_items, lưu sẵn để báo cáo nhanh;
-- backend phải cập nhật trong cùng giao dịch (transaction) với order_items.
CREATE TABLE orders (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NULL,
    reservation_id   INT UNSIGNED NULL,
    status           ENUM('pending', 'preparing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
    total_amount     DECIMAL(10, 2) NOT NULL DEFAULT 0 CHECK (total_amount >= 0),
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user        FOREIGN KEY (user_id)        REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_orders_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE SET NULL
) ENGINE = InnoDB;

-- order_items: bảng trung gian của quan hệ nhiều-nhiều orders <-> products.
-- unit_price lưu giá TẠI THỜI ĐIỂM MUA, nên khi đổi giá sản phẩm thì đơn cũ không bị đổi theo.
CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    product_id   INT UNSIGNED NOT NULL,
    quantity     SMALLINT UNSIGNED NOT NULL CHECK (quantity > 0),
    unit_price   DECIMAL(10, 2) NOT NULL CHECK (unit_price >= 0),
    CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY uq_order_product (order_id, product_id)
) ENGINE = InnoDB;

-- =====================================================================
-- DỮ LIỆU MẪU
-- Giá lấy theo giao diện hiện tại của website (đơn vị USD như trên trang menu).
-- =====================================================================

-- Mật khẩu chưa được mã hóa thật vì chưa có backend. Khi làm PHP, tạo lại bằng
-- password_hash('...', PASSWORD_DEFAULT) và cập nhật cột này.
INSERT INTO users (full_name, email, phone, password_hash, role) VALUES
('Quản trị SmartCoffee', 'admin@smartcoffee.test',    '0900000001', 'CHUA_MA_HOA_SE_TAO_O_GIAI_DOAN_BACKEND', 'admin'),
('Nhân viên Ca sáng',    'staff@smartcoffee.test',    '0900000002', 'CHUA_MA_HOA_SE_TAO_O_GIAI_DOAN_BACKEND', 'staff'),
('Khách hàng Demo',      'customer@smartcoffee.test', '0900000003', 'CHUA_MA_HOA_SE_TAO_O_GIAI_DOAN_BACKEND', 'customer');

INSERT INTO categories (name, slug) VALUES
('Cà phê nóng', 'ca-phe-nong'),   -- id 1
('Cà phê lạnh', 'ca-phe-lanh');   -- id 2

INSERT INTO products (category_id, name, slug, price, image) VALUES
(1, 'Cinnamon Espresso Shot',    'cinnamon-espresso-shot',    7.50, 'assets/images/6a43522708556f0ef14e35bd_Cinnamon Espresso Shot.webp'),
(1, 'Maple Oat Cappuccino',      'maple-oat-cappuccino',      7.50, 'assets/images/6a435209f115d45ee5cad5cd_Maple Oat Cappuccino.webp'),
(1, 'Mocha Hazelnut Bliss',      'mocha-hazelnut-bliss',      7.50, 'assets/images/6a4351e4cd87f156ed12c525_Mocha Hazelnut Bliss.webp'),
(1, 'Vanilla Bean Latte',        'vanilla-bean-latte',        7.50, 'assets/images/6a4351cf47f402af107b56e9_Vanilla Bean Latte.webp'),
(2, 'Caramel Cold Brew',         'caramel-cold-brew',         7.50, 'assets/images/6a4351c31766d2c11f1a96af_Caramel Cold Brew.webp'),
(2, 'Coconut Cream Iced Coffee', 'coconut-cream-iced-coffee', 7.50, 'assets/images/6a4352333a8e32806a77a6b7_Coconut Cream Iced Coffee.webp');

INSERT INTO cafe_tables (code, capacity, status) VALUES
('T01', 2, 'available'),
('T02', 2, 'available'),
('T03', 4, 'available'),
('T04', 4, 'available'),
('T05', 6, 'available'),
('T06', 8, 'maintenance');

INSERT INTO reservations (user_id, table_id, contact_name, contact_phone, reserved_at, guest_count, note, status) VALUES
(3,    3,    'Khách hàng Demo', '0900000003', '2026-11-20 09:00:00', 3, 'Bàn gần cửa sổ',     'confirmed'),
(NULL, 1,    'Nguyễn Văn An',   '0911111111', '2026-11-20 14:30:00', 2, NULL,                 'pending'),
(NULL, 5,    'Trần Thị Bình',   '0922222222', '2026-11-21 19:00:00', 5, 'Tổ chức sinh nhật',  'pending'),
(3,    NULL, 'Khách hàng Demo', '0900000003', '2026-11-10 08:00:00', 2, NULL,                 'cancelled');

INSERT INTO orders (user_id, reservation_id, status, total_amount, created_at) VALUES
(3,    1,    'pending',   22.50, '2026-11-13 10:00:00'),   -- đặt trước món cho lượt đặt bàn số 1
(NULL, NULL, 'completed', 15.00, '2026-11-12 15:20:00'),   -- khách mua mang đi
(3,    NULL, 'cancelled',  7.50, '2026-11-11 09:10:00');

INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
(1, 2, 2, 7.50), (1, 5, 1, 7.50),   -- 22.50
(2, 4, 1, 7.50), (2, 6, 1, 7.50),   -- 15.00
(3, 1, 1, 7.50);                    --  7.50

-- =====================================================================
-- TRUY VẤN KIỂM TRA (bỏ dấu -- ở đầu dòng để chạy thử)
-- =====================================================================

-- 1. Thực đơn đang bán, kèm tên danh mục
-- SELECT p.name, c.name AS category, p.price
-- FROM products p JOIN categories c ON c.id = p.category_id
-- WHERE p.is_active = TRUE ORDER BY c.id, p.name;

-- 2. Kiểm tra total_amount có khớp với tổng order_items không (kết quả rỗng là khớp)
-- SELECT o.id, o.total_amount, SUM(oi.quantity * oi.unit_price) AS tinh_lai
-- FROM orders o JOIN order_items oi ON oi.order_id = o.id
-- GROUP BY o.id, o.total_amount
-- HAVING o.total_amount <> SUM(oi.quantity * oi.unit_price);

-- 3. Lượt đặt bàn vượt sức chứa của bàn (kết quả rỗng là hợp lệ)
-- SELECT r.id, r.guest_count, t.code, t.capacity
-- FROM reservations r JOIN cafe_tables t ON t.id = r.table_id
-- WHERE r.guest_count > t.capacity;

-- 4. Món bán chạy (chỉ tính đơn đã hoàn tất)
-- SELECT p.name, SUM(oi.quantity) AS so_luong
-- FROM order_items oi
-- JOIN orders o   ON o.id = oi.order_id
-- JOIN products p ON p.id = oi.product_id
-- WHERE o.status = 'completed'
-- GROUP BY p.id, p.name ORDER BY so_luong DESC;
