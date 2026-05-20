-- ============================================================
-- AUCTION PLATFORM - COMPLETE DATABASE SCHEMA
-- ============================================================

CREATE DATABASE IF NOT EXISTS auction_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auction_platform;

-- ============================================================
-- USERS TABLE
-- ============================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('buyer', 'seller', 'admin') NOT NULL DEFAULT 'buyer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    profile_image VARCHAR(255) DEFAULT 'default.png',
    is_banned TINYINT(1) DEFAULT 0,
    ban_reason TEXT,
    is_verified TINYINT(1) DEFAULT 1,
    fraud_score INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_email (email),
    INDEX idx_banned (is_banned)
) ENGINE=InnoDB;

-- ============================================================
-- AUCTIONS TABLE
-- ============================================================
CREATE TABLE auctions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    seller_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    image_path VARCHAR(255),
    starting_price DECIMAL(12,2) NOT NULL,
    current_price DECIMAL(12,2) NOT NULL,
    min_bid_increment DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    reserve_price DECIMAL(12,2) DEFAULT NULL,
    duration_minutes INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('pending','active','ended','cancelled','sold') DEFAULT 'pending',
    winner_id INT UNSIGNED DEFAULT NULL,
    total_bids INT DEFAULT 0,
    is_overridden TINYINT(1) DEFAULT 0,
    override_note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_seller (seller_id),
    INDEX idx_end_time (end_time)
) ENGINE=InnoDB;

-- ============================================================
-- BIDS TABLE
-- ============================================================
CREATE TABLE bids (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auction_id INT UNSIGNED NOT NULL,
    bidder_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    is_winning TINYINT(1) DEFAULT 0,
    is_flagged TINYINT(1) DEFAULT 0,
    flag_reason VARCHAR(255),
    ip_address VARCHAR(45),
    bid_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
    FOREIGN KEY (bidder_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_auction (auction_id),
    INDEX idx_bidder (bidder_id),
    INDEX idx_winning (is_winning),
    INDEX idx_bid_time (bid_time)
) ENGINE=InnoDB;

-- ============================================================
-- TRANSACTIONS TABLE
-- ============================================================
CREATE TABLE transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    auction_id INT UNSIGNED NOT NULL UNIQUE,
    buyer_id INT UNSIGNED NOT NULL,
    seller_id INT UNSIGNED NOT NULL,
    final_amount DECIMAL(12,2) NOT NULL,
    payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
    delivery_status ENUM('pending','shipped','delivered','completed') DEFAULT 'pending',
    payment_method VARCHAR(100),
    payment_reference VARCHAR(200),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auction_id) REFERENCES auctions(id),
    FOREIGN KEY (buyer_id) REFERENCES users(id),
    FOREIGN KEY (seller_id) REFERENCES users(id),
    INDEX idx_buyer (buyer_id),
    INDEX idx_seller (seller_id)
) ENGINE=InnoDB;

-- ============================================================
-- MESSAGES TABLE (Buyer-Seller Communication)
-- ============================================================
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    INDEX idx_transaction (transaction_id),
    INDEX idx_sender (sender_id)
) ENGINE=InnoDB;

-- ============================================================
-- ADMIN LOGS TABLE
-- ============================================================
CREATE TABLE admin_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    target_type ENUM('user','auction','bid','system') NOT NULL,
    target_id INT UNSIGNED,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB;

-- ============================================================
-- FRAUD FLAGS TABLE
-- ============================================================
CREATE TABLE fraud_flags (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    auction_id INT UNSIGNED,
    bid_id INT UNSIGNED,
    flag_type ENUM('rapid_bidding','abnormal_jump','self_bid','spam','suspicious') NOT NULL,
    description TEXT,
    severity ENUM('low','medium','high') DEFAULT 'low',
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_by INT UNSIGNED,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_resolved (is_resolved),
    INDEX idx_severity (severity)
) ENGINE=InnoDB;

-- ============================================================
-- AUCTION CATEGORIES TABLE
-- ============================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- WATCHLIST TABLE
-- ============================================================
CREATE TABLE watchlist (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    auction_id INT UNSIGNED NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_watch (user_id, auction_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- RATE LIMIT TABLE (For Anti-Fraud)
-- ============================================================
CREATE TABLE bid_rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    auction_id INT UNSIGNED NOT NULL,
    bid_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_rate (user_id, auction_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SAMPLE DATA
-- ============================================================

-- Categories
INSERT INTO categories (name, icon) VALUES
('Electronics', 'laptop'),
('Collectibles', 'star'),
('Art & Crafts', 'palette'),
('Jewelry', 'gem'),
('Vehicles', 'car'),
('Fashion', 'shirt'),
('Books', 'book'),
('Home & Garden', 'home');

-- Admin user (password: Admin@Secret123)
INSERT INTO users (username, email, password_hash, role, full_name) VALUES
('admin', 'admin@auction.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Admin');

-- Sample seller (password: Seller@123)
INSERT INTO users (username, email, password_hash, role, full_name, phone) VALUES
('seller_demo', 'seller@demo.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'Rajesh Kumar', '9876543210');

-- Sample buyer (password: Buyer@123)
INSERT INTO users (username, email, password_hash, role, full_name, phone) VALUES
('buyer_demo', 'buyer@demo.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Priya Sharma', '9123456789');

-- Sample active auction
INSERT INTO auctions (seller_id, title, description, category, image_path, starting_price, current_price, min_bid_increment, duration_minutes, start_time, end_time, status) VALUES
(2, 'Vintage Leica M3 Camera', 'Rare 1954 Leica M3 Double Stroke in excellent working condition. Original leather case included. Serial number 700XXX. A collectors dream piece.', 'Collectibles', 'default_item.jpg', 15000.00, 15000.00, 500.00, 120, NOW(), DATE_ADD(NOW(), INTERVAL 120 MINUTE), 'active'),
(2, 'Apple MacBook Pro 2023 M2', 'Barely used MacBook Pro 16 inch M2 Pro chip, 32GB RAM, 1TB SSD. Original box and accessories included. Warranty valid till Dec 2025.', 'Electronics', 'default_item.jpg', 120000.00, 120000.00, 2000.00, 180, NOW(), DATE_ADD(NOW(), INTERVAL 180 MINUTE), 'active'),
(2, 'Traditional Kanjivaram Silk Saree', 'Authentic Kanjivaram pure silk saree in royal blue with gold zari work. Certificate of authenticity provided. Never worn.', 'Fashion', 'default_item.jpg', 8000.00, 8000.00, 200.00, 90, NOW(), DATE_ADD(NOW(), INTERVAL 90 MINUTE), 'active');

-- Sample bids
INSERT INTO bids (auction_id, bidder_id, amount, is_winning, ip_address) VALUES
(1, 3, 15500.00, 0, '127.0.0.1'),
(1, 3, 16000.00, 0, '127.0.0.1'),
(1, 3, 17000.00, 1, '127.0.0.1'),
(2, 3, 122000.00, 0, '127.0.0.1'),
(2, 3, 125000.00, 1, '127.0.0.1');

UPDATE auctions SET current_price = 17000.00, total_bids = 3, winner_id = 3 WHERE id = 1;
UPDATE auctions SET current_price = 125000.00, total_bids = 2, winner_id = 3 WHERE id = 2;
