<?php
// models/TransactionModel.php

require_once __DIR__ . '/../config/database.php';

class TransactionModel {

    public static function createFromAuction(int $auctionId): ?int {
        $auction = Database::query("SELECT * FROM auctions WHERE id = ? AND winner_id IS NOT NULL", [$auctionId])->fetch();
        if (!$auction) return null;

        // Check if transaction already exists
        $existing = Database::query("SELECT id FROM transactions WHERE auction_id = ?", [$auctionId])->fetch();
        if ($existing) return $existing['id'];

        Database::query(
            "INSERT INTO transactions (auction_id, buyer_id, seller_id, final_amount) VALUES (?,?,?,?)",
            [$auctionId, $auction['winner_id'], $auction['seller_id'], $auction['current_price']]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public static function getByAuction(int $auctionId): ?array {
        $stmt = Database::query(
            "SELECT t.*, 
                    b.username as buyer_username, b.full_name as buyer_name, b.email as buyer_email, b.phone as buyer_phone,
                    s.username as seller_username, s.full_name as seller_name, s.email as seller_email, s.phone as seller_phone,
                    a.title as auction_title
             FROM transactions t
             JOIN users b ON t.buyer_id = b.id
             JOIN users s ON t.seller_id = s.id
             JOIN auctions a ON t.auction_id = a.id
             WHERE t.auction_id = ?",
            [$auctionId]
        );
        return $stmt->fetch() ?: null;
    }

    public static function getById(int $id): ?array {
        $stmt = Database::query(
            "SELECT t.*,
                    b.username as buyer_username, b.full_name as buyer_name, b.email as buyer_email, b.phone as buyer_phone,
                    s.username as seller_username, s.full_name as seller_name, s.email as seller_email, s.phone as seller_phone,
                    a.title as auction_title, a.image_path
             FROM transactions t
             JOIN users b ON t.buyer_id = b.id
             JOIN users s ON t.seller_id = s.id
             JOIN auctions a ON t.auction_id = a.id
             WHERE t.id = ?",
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public static function getUserTransactions(int $userId): array {
        return Database::query(
            "SELECT t.*, a.title, a.image_path,
                    CASE WHEN t.buyer_id = ? THEN 'buyer' ELSE 'seller' END as my_role,
                    CASE WHEN t.buyer_id = ? THEN s.username ELSE b.username END as other_username
             FROM transactions t
             JOIN auctions a ON t.auction_id = a.id
             JOIN users b ON t.buyer_id = b.id
             JOIN users s ON t.seller_id = s.id
             WHERE t.buyer_id = ? OR t.seller_id = ?
             ORDER BY t.created_at DESC",
            [$userId, $userId, $userId, $userId]
        )->fetchAll();
    }

    public static function updatePaymentStatus(int $txId, string $status): void {
        Database::query("UPDATE transactions SET payment_status = ? WHERE id = ?", [$status, $txId]);
    }

    public static function updateDeliveryStatus(int $txId, string $status): void {
        Database::query("UPDATE transactions SET delivery_status = ? WHERE id = ?", [$status, $txId]);
    }

    // Messages
    public static function sendMessage(int $txId, int $senderId, int $receiverId, string $message): int {
        Database::query(
            "INSERT INTO messages (transaction_id, sender_id, receiver_id, message) VALUES (?,?,?,?)",
            [$txId, $senderId, $receiverId, $message]
        );
        return (int)Database::getInstance()->lastInsertId();
    }

    public static function getMessages(int $txId): array {
        return Database::query(
            "SELECT m.*, u.username, u.full_name
             FROM messages m
             JOIN users u ON m.sender_id = u.id
             WHERE m.transaction_id = ?
             ORDER BY m.sent_at ASC",
            [$txId]
        )->fetchAll();
    }

    public static function markMessagesRead(int $txId, int $userId): void {
        Database::query(
            "UPDATE messages SET is_read = 1 WHERE transaction_id = ? AND receiver_id = ?",
            [$txId, $userId]
        );
    }
}
