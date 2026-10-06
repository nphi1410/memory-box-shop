CREATE DATABASE IF NOT EXISTS memory_box CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE memory_box;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS custom_boxes;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    address TEXT NULL,
    avatar_url VARCHAR(500) NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    category ENUM('box', 'gift') NOT NULL,
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    description TEXT NULL,
    image_url VARCHAR(500) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_product (user_id, product_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE custom_cart_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    box_shape VARCHAR(40) NOT NULL,
    box_color VARCHAR(20) NOT NULL,
    gift_items JSON NULL,
    message VARCHAR(500) NULL,
    estimated_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE custom_boxes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    box_shape VARCHAR(40) NOT NULL,
    box_color VARCHAR(20) NOT NULL,
    gift_items JSON NULL,
    message VARCHAR(500) NULL,
    estimated_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_custom_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    order_code VARCHAR(40) NOT NULL UNIQUE,
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('placed','confirmed','completed','cancelled') NOT NULL DEFAULT 'placed',
    order_type ENUM('product','cart','custom') NOT NULL DEFAULT 'product',
    custom_box_id INT UNSIGNED NULL,
    custom_note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_order_custom_box FOREIGN KEY (custom_box_id) REFERENCES custom_boxes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    item_name VARCHAR(180) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    meta_json JSON NULL,
    CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_feedback_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

INSERT INTO users (full_name, email, phone, address, password_hash) VALUES
('Demo Memory Box', 'demo@memorybox.local', '0900000000', 'Hà Nội', '$2y$12$6q/nfQcnj03XcL4XgCMIZeKp1/55G2bTEwqzlNl37DoBcDpKGym3a');

INSERT INTO products (name, category, price, description, image_url) VALUES
('Hộp Hồng Dịu Dàng', 'box', 119000, 'Hộp vuông pastel, ruy băng mềm và thiệp mini.', 'assets/images/box-pink.svg'),
('Hộp Trái Tim Sweetie', 'box', 149000, 'Hộp trái tim tông đỏ hồng dành cho dịp đặc biệt.', 'assets/images/box-heart.svg'),
('Hộp Lavender Dream', 'box', 139000, 'Hộp quà tím lavender phong cách nhẹ nhàng.', 'assets/images/box-lavender.svg'),
('Hộp Kraft Mộc Mạc', 'box', 99000, 'Hộp kraft tối giản, gần gũi và ấm áp.', 'assets/images/box-kraft.svg'),
('Nến thơm Vanilla Cloud', 'gift', 79000, 'Nến thơm thủ công hương vanilla dịu nhẹ.', 'assets/images/candle.svg'),
('Scrapbook Mini', 'gift', 89000, 'Album ảnh mini để lưu lại những khoảnh khắc đáng nhớ.', 'assets/images/scrapbook.svg'),
('Thiệp Viết Tay', 'gift', 29000, 'Thiệp giấy mỹ thuật với lời nhắn viết tay theo yêu cầu.', 'assets/images/card.svg'),
('Hoa khô Mini', 'gift', 59000, 'Bó hoa khô nhỏ xinh để trang trí hộp quà.', 'assets/images/flower.svg');
