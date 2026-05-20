<?php
// models/AuctionModel.php

require_once __DIR__ . '/../config/database.php';

class AuctionModel {

    public static function getAll(array $filters = [], int $limit = 12, int $offset = 0): array {
        $where = ["a.status = 'active'"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = "a.category = ?";
            $params[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(a.title LIKE ? OR a.description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['min_price'])) {
            $where[] = "a.current_price >= ?";
            $params[] = $filters['min_price'];
        }
        if (!empty($filters['max_price'])) {
            $where[] = "a.current_price <= ?";
            $params[] = $filters['max_price'];
        }

        $whereClause = implode(' AND ', $where);
        $params[] = $limit;
        $params[] = $offset;

        return Database::query(
            "SELECT a.*, u.username as seller_name, u.full_name as seller_fullname
             FROM auctions a
             JOIN users u ON a.seller_id = u.id
             WHERE $whereClause
             ORDER BY a.end_time ASC
             LIMIT ? OFFSET ?",
            $params
        )->fetchAll();
    }

    public static function getById(int $id): ?array {
        $stmt = Database::query(
            "SELECT a.*, u.username as seller_name, u.full_name as seller_fullname, u.email as seller_email
             FROM auctions a
             JOIN users u ON a.seller_id = u.id
             WHERE a.id = ? LIMIT 1",
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public static function getBySeller(int $sellerId): array {
        return Database::query(
            "SELECT a.*, COALESCE(w.username, 'No winner yet') as winner_name
             FROM auctions a
             LEFT JOIN users w ON a.winner_id = w.id
             WHERE a.seller_id = ?
             ORDER BY a.created_at DESC",
            [$sellerId]
        )->fetchAll();
    }

    public static function create(array $data): int {
        Database::query(
            "INSERT INTO auctions (seller_id, title, description, category, image_path, starting_price, current_price, min_bid_increment, duration_minutes, start_time, end_time, status)
             VALUES (?,?,?,?,?,?,?,?,?,NOW(),DATE_ADD(NOW(), INTERVAL ? MINUTE),'active')",
            [
                $data['seller_id'],
                $data['title'],
                $data['description'],
                $data['category'],
                $data['image_path'],
                $data['starting_price'],
                $data['starting_price'],
                $data['min_bid_increment'],
                $data['duration_minutes'],
                $data['duration_minutes']
            ]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public static function updatePrice(int $auctionId, float $newPrice, int $winnerId): void {
        Database::query(
            "UPDATE auctions SET current_price = ?, winner_id = ?, total_bids = total_bids + 1 WHERE id = ?",
            [$newPrice, $winnerId, $auctionId]
        );
    }

    public static function closeExpired(): int {
        $result = Database::query(
            "UPDATE auctions SET status = 'ended' WHERE status = 'active' AND end_time <= NOW()"
        );
        return $result->rowCount();
    }

    public static function getTotalCount(array $filters = []): int {
        $where = ["status = 'active'"];
        $params = [];

        if (!empty($filters['category'])) {
            $where[] = "category = ?";
            $params[] = $filters['category'];
        }
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE ? OR description LIKE ?)";
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        return (int)Database::query("SELECT COUNT(*) FROM auctions WHERE $whereClause", $params)->fetchColumn();
    }

    public static function getSellerAnalytics(int $sellerId): array {
        return Database::query(
            "SELECT 
                a.id, a.title, a.status, a.starting_price, a.current_price, a.total_bids, a.end_time,
                a.winner_id,
                COUNT(DISTINCT b.bidder_id) as unique_bidders,
                AVG(b.amount) as avg_bid,
                MAX(b.amount) as highest_bid
             FROM auctions a
             LEFT JOIN bids b ON a.id = b.auction_id
             WHERE a.seller_id = ?
             GROUP BY a.id
             ORDER BY a.created_at DESC",
            [$sellerId]
        )->fetchAll();
    }

    public static function getAdminAll(): array {
        return Database::query(
            "SELECT a.*, u.username as seller_name 
             FROM auctions a 
             JOIN users u ON a.seller_id = u.id 
             ORDER BY a.created_at DESC 
             LIMIT 100"
        )->fetchAll();
    }

    public static function override(int $auctionId, string $status, string $note): void {
        Database::query(
            "UPDATE auctions SET status = ?, is_overridden = 1, override_note = ? WHERE id = ?",
            [$status, $note, $auctionId]
        );
    }

    public static function getBidTrend(int $auctionId): array {
        return Database::query(
            "SELECT amount, DATE_FORMAT(bid_time, '%H:%i') as time_label, bidder_id
             FROM bids 
             WHERE auction_id = ? 
             ORDER BY bid_time ASC",
            [$auctionId]
        )->fetchAll();
    }
}