<?php
// config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'auction_platform');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('ADMIN_SECRET_KEY', 'CrownAuction_Admin_2024_SecretKey_XYZ');
define('ADMIN_SECRET_PATH', 'admin-secret');

define('APP_NAME', 'CrownAuction');
define('APP_URL', 'http://localhost/auction_platform');
define('UPLOAD_PATH', __DIR__ . '/../public/images/uploads/');
define('UPLOAD_URL', APP_URL . '/public/images/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

define('MIN_AUCTION_DURATION', 30);    // minutes
define('MAX_AUCTION_DURATION', 360);   // minutes (6 hours)
define('MAX_BIDS_PER_MINUTE', 3);
define('FRAUD_BID_JUMP_PERCENT', 200); // Flag if bid > 200% of current price

define('TIMEZONE', 'Asia/Kolkata');
date_default_timezone_set(TIMEZONE);

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Persistent connections: reuse DB connections across PHP requests
                // instead of opening a new one for every user — reduces overhead
                // significantly under concurrent load.
                PDO::ATTR_PERSISTENT         => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+05:30'"
            ];
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false);
                if ($isApi) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Service temporarily unavailable. Please try again.']);
                } else {
                    echo '<div style="font-family:sans-serif;padding:40px;text-align:center;background:#0d0d0d;color:#f0ede6;min-height:100vh;">
                        <h2 style="color:#C9A84C;">&#128274; CrownAuction</h2>
                        <p>Service temporarily unavailable. Please try again in a moment.</p>
                    </div>';
                }
                exit;
            }
        }
        return self::$instance;
    }

    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}