<?php
// controllers/AdminController.php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AuctionModel.php';
require_once __DIR__ . '/../models/BidModel.php';

class AdminController {

    public static function handleLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

        $key = $_POST['admin_key'] ?? '';
        if (!hash_equals(ADMIN_SECRET_KEY, $key)) {
            setFlash('error', 'Invalid admin key.');
            header('Location: ' . APP_URL . '/' . ADMIN_SECRET_PATH . '.php');
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['admin_authenticated'] = true;
        $_SESSION['admin_login_time']    = time();
        $_SESSION['admin_ip']            = $_SERVER['REMOTE_ADDR'];

        self::logAction(null, 'admin_login', 'system', null, 'Admin logged in');

        header('Location: ' . APP_URL . '/views/admin/admin_dashboard.php');
        exit;
    }

    public static function handleBanUser(): void {
        requireAdmin();

        $userId = (int)($_POST['user_id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Policy violation');

        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Invalid user.']);
            exit;
        }

        UserModel::ban($userId, $reason);
        self::logAction(null, 'ban_user', 'user', $userId, $reason);

        echo json_encode(['success' => true, 'message' => 'User banned.']);
        exit;
    }

    public static function handleUnbanUser(): void {
        requireAdmin();

        $userId = (int)($_POST['user_id'] ?? 0);
        UserModel::unban($userId);
        self::logAction(null, 'unban_user', 'user', $userId, 'User unbanned');

        echo json_encode(['success' => true, 'message' => 'User unbanned.']);
        exit;
    }

    public static function handleOverrideAuction(): void {
        requireAdmin();

        $auctionId = (int)($_POST['auction_id'] ?? 0);
        $status    = $_POST['status'] ?? 'cancelled';
        $note      = trim($_POST['note'] ?? '');

        if (!in_array($status, ['active', 'ended', 'cancelled'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid status.']);
            exit;
        }

        AuctionModel::override($auctionId, $status, $note);
        self::logAction(null, 'override_auction', 'auction', $auctionId, "Status changed to $status: $note");

        echo json_encode(['success' => true, 'message' => 'Auction overridden.']);
        exit;
    }

    public static function getDashboardData(): array {
        return [
            'total_users'    => UserModel::getTotalCount(),
            'total_auctions' => (int)Database::query("SELECT COUNT(*) FROM auctions")->fetchColumn(),
            'active_auctions'=> (int)Database::query("SELECT COUNT(*) FROM auctions WHERE status='active'")->fetchColumn(),
            'total_bids'     => (int)Database::query("SELECT COUNT(*) FROM bids")->fetchColumn(),
            'bid_stats'      => BidModel::getAdminStats(),
            'recent_users'   => Database::query("SELECT id, username, email, role, created_at, is_banned FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(),
        ];
    }

    private static function logAction(?int $adminId, string $action, string $targetType, ?int $targetId, string $details): void {
        Database::query(
            "INSERT INTO admin_logs (admin_id, action, target_type, target_id, details, ip_address) VALUES (?,?,?,?,?,?)",
            [$adminId, $action, $targetType, $targetId, $details, $_SERVER['REMOTE_ADDR'] ?? '']
        );
    }

    public static function getLogs(): array {
        return Database::query(
            "SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT 50"
        )->fetchAll();
    }
}
