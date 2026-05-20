<?php
// api/get_auction.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/AuctionModel.php';
require_once __DIR__ . '/../models/BidModel.php';

// Auto-close expired auctions
AuctionModel::closeExpired();

$auctionId = (int)($_GET['id'] ?? 0);

if (!$auctionId) {
    echo json_encode(['success' => false, 'message' => 'Invalid auction ID.']);
    exit;
}

$auction = AuctionModel::getById($auctionId);
if (!$auction) {
    echo json_encode(['success' => false, 'message' => 'Auction not found.']);
    exit;
}

$bids    = BidModel::getByAuction($auctionId);
$timeInfo= timeRemaining($auction['end_time']);
$minNext = (float)$auction['current_price'] + (float)$auction['min_bid_increment'];

echo json_encode([
    'success'        => true,
    'auction_id'     => $auction['id'],
    'status'         => $auction['status'],
    'current_price'  => (float)$auction['current_price'],
    'current_price_fmt' => formatINR($auction['current_price']),
    'total_bids'     => $auction['total_bids'],
    'min_next_bid'   => $minNext,
    'min_next_fmt'   => formatINR($minNext),
    'time_remaining' => $timeInfo,
    'end_time'       => $auction['end_time'],
    'winner_id'      => $auction['winner_id'],
    'bid_history'    => array_map(fn($b) => [
        'username'   => $b['username'],
        'amount'     => (float)$b['amount'],
        'amount_fmt' => formatINR($b['amount']),
        'time'       => date('d M, h:i A', strtotime($b['bid_time'])),
        'is_winning' => (bool)$b['is_winning']
    ], $bids)
]);
