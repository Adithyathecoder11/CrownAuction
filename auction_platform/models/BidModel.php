<?php
// models/BidModel.php

require_once __DIR__ . '/../config/database.php';

class BidModel {

    public static function getByAuction(int $auctionId): array {
        return Database::query(
            "SELECT b.*, u.username, u.full_name
             FROM bids b
             JOIN users u ON b.bidder_id = u.id
             WHERE b.auction_id = ?
             ORDER BY b.amount DESC",
            [$auctionId]
        )->fetchAll();
    }

    public static function getHighestBid(int $auctionId): ?array {
        $stmt = Database::query(
            "SELECT b.*, u.username FROM bids b JOIN users u ON b.bidder_id = u.id WHERE b.auction_id = ? ORDER BY b.amount DESC LIMIT 1",
            [$auctionId]
        );
        return $stmt->fetch() ?: null;
    }

    public static function getUserBidCount(int $userId, int $auctionId): int {
        return (int)Database::query(
            "SELECT COUNT(*) FROM bids WHERE bidder_id = ? AND auction_id = ?",
            [$userId, $auctionId]
        )->fetchColumn();
    }

    public static function getRecentBidCount(int $userId, int $auctionId, int $seconds = 60): int {
        return (int)Database::query(
            "SELECT COUNT(*) FROM bids WHERE bidder_id = ? AND auction_id = ? AND bid_time >= DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$userId, $auctionId, $seconds]
        )->fetchColumn();
    }

    public static function getLastBid(int $userId, int $auctionId): ?array {
        $stmt = Database::query(
            "SELECT * FROM bids WHERE bidder_id = ? AND auction_id = ? ORDER BY bid_time DESC LIMIT 1",
            [$userId, $auctionId]
        );
        return $stmt->fetch() ?: null;
    }

    /**
     * Full anti-fraud validation before placing a bid
     */
    public static function validate(array $bid, array $auction): array {
        $errors = [];

        // 1. Self-bidding check
        if ($bid['bidder_id'] === (int)$auction['seller_id']) {
            $errors[] = ['type' => 'self_bid', 'message' => 'Sellers cannot bid on their own auctions.'];
        }

        // 2. Auction status check
        if ($auction['status'] !== 'active') {
            $errors[] = ['type' => 'invalid_auction', 'message' => 'This auction is not active.'];
        }

        // 3. Auction time check
        if (strtotime($auction['end_time']) <= time()) {
            $errors[] = ['type' => 'expired', 'message' => 'This auction has ended.'];
        }

        // 4. Minimum bid check
        $minRequired = (float)$auction['current_price'] + (float)$auction['min_bid_increment'];
        if ((float)$bid['amount'] < $minRequired) {
            $errors[] = ['type' => 'low_bid', 'message' => "Minimum bid is " . formatINR($minRequired)];
        }

        // 5. Rapid bidding check
        $recentBids = self::getRecentBidCount($bid['bidder_id'], $bid['auction_id'], 60);
        if ($recentBids >= MAX_BIDS_PER_MINUTE) {
            $errors[] = ['type' => 'rapid_bidding', 'message' => 'Too many bids placed recently. Please wait.'];
            self::flagFraud($bid['bidder_id'], $bid['auction_id'], null, 'rapid_bidding', 'Exceeded bid rate limit', 'medium');
        }

        // 6. Abnormal bid jump
        $jumpPercent = ((float)$bid['amount'] / (float)$auction['current_price']) * 100 - 100;
        if ($jumpPercent > FRAUD_BID_JUMP_PERCENT) {
            self::flagFraud($bid['bidder_id'], $bid['auction_id'], null, 'abnormal_jump', "Bid jumped {$jumpPercent}% above current price", 'high');
        }

        return $errors;
    }

    public static function place(array $bid): int {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // ── LOCK the auction row so no other request can read/write
            // it simultaneously. This prevents race conditions when two
            // users bid at exactly the same moment.
            $auction = $pdo->prepare(
                "SELECT id, current_price, min_bid_increment, status, end_time
                 FROM auctions WHERE id = ? AND status = 'active' FOR UPDATE"
            );
            $auction->execute([$bid['auction_id']]);
            $locked = $auction->fetch(PDO::FETCH_ASSOC);

            // If auction disappeared, ended, or was overridden between
            // validate() and place() — abort cleanly.
            if (!$locked) {
                $pdo->rollBack();
                throw new RuntimeException('Auction is no longer active.');
            }

            // Re-check end time under the lock (clock may have ticked over)
            if (strtotime($locked['end_time']) <= time()) {
                $pdo->rollBack();
                throw new RuntimeException('Auction has ended.');
            }

            // Re-check minimum bid under the lock (price may have changed
            // since validate() read it — another user may have bid first)
            $minRequired = (float)$locked['current_price'] + (float)$locked['min_bid_increment'];
            if ((float)$bid['amount'] < $minRequired) {
                $pdo->rollBack();
                throw new RuntimeException(
                    'Your bid of ' . number_format($bid['amount'], 2) .
                    ' is now too low. Minimum is ' . number_format($minRequired, 2) .
                    '. Please refresh and try again.'
                );
            }

            // Mark previous winning bids as not winning
            Database::query(
                "UPDATE bids SET is_winning = 0 WHERE auction_id = ?",
                [$bid['auction_id']]
            );

            // Insert new bid
            Database::query(
                "INSERT INTO bids (auction_id, bidder_id, amount, is_winning, ip_address, is_flagged, flag_reason)
                 VALUES (?,?,?,1,?,?,?)",
                [
                    $bid['auction_id'],
                    $bid['bidder_id'],
                    $bid['amount'],
                    $bid['ip_address'] ?? '0.0.0.0',
                    $bid['is_flagged'] ?? 0,
                    $bid['flag_reason'] ?? null
                ]
            );
            $bidId = (int)$pdo->lastInsertId();

            // Update auction price and winner atomically
            Database::query(
                "UPDATE auctions SET current_price = ?, winner_id = ?, total_bids = total_bids + 1 WHERE id = ?",
                [$bid['amount'], $bid['bidder_id'], $bid['auction_id']]
            );

            $pdo->commit();
            return $bidId;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function flagFraud(int $userId, ?int $auctionId, ?int $bidId, string $type, string $desc, string $severity = 'low'): void {
        Database::query(
            "INSERT INTO fraud_flags (user_id, auction_id, bid_id, flag_type, description, severity) VALUES (?,?,?,?,?,?)",
            [$userId, $auctionId, $bidId, $type, $desc, $severity]
        );
        // Increment user fraud score
        $points = ['low' => 1, 'medium' => 3, 'high' => 5][$severity] ?? 1;
        Database::query("UPDATE users SET fraud_score = fraud_score + ? WHERE id = ?", [$points, $userId]);
    }

    public static function getSuspiciousBids(): array {
        return Database::query(
            "SELECT b.*, u.username, u.email, a.title as auction_title
             FROM bids b
             JOIN users u ON b.bidder_id = u.id
             JOIN auctions a ON b.auction_id = a.id
             WHERE b.is_flagged = 1
             ORDER BY b.bid_time DESC"
        )->fetchAll();
    }

    public static function getAllFraudFlags(): array {
        return Database::query(
            "SELECT ff.*, u.username, u.email, u.fraud_score
             FROM fraud_flags ff
             JOIN users u ON ff.user_id = u.id
             ORDER BY ff.created_at DESC"
        )->fetchAll();
    }

    public static function getAdminStats(): array {
        $total    = Database::query("SELECT COUNT(*) FROM bids")->fetchColumn();
        $flagged  = Database::query("SELECT COUNT(*) FROM bids WHERE is_flagged = 1")->fetchColumn();
        $flags    = Database::query("SELECT COUNT(*) FROM fraud_flags WHERE is_resolved = 0")->fetchColumn();
        return ['total' => $total, 'flagged' => $flagged, 'open_flags' => $flags];
    }
}