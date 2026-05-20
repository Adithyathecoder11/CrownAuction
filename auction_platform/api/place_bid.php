<?php
// api/place_bid.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/AuctionModel.php';
require_once __DIR__ . '/../models/BidModel.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login to place a bid.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Close expired auctions first
AuctionModel::closeExpired();

$user       = getCurrentUser();
$auctionId  = (int)($_POST['auction_id'] ?? 0);
$amount     = (float)($_POST['amount'] ?? 0);
$token      = $_POST['csrf_token'] ?? '';

if (!verifyCsrf($token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
    exit;
}

if (!$auctionId || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bid data.']);
    exit;
}

$auction = AuctionModel::getById($auctionId);
if (!$auction) {
    echo json_encode(['success' => false, 'message' => 'Auction not found.']);
    exit;
}

// Fraud validation
$bid = [
    'bidder_id'  => $user['id'],
    'auction_id' => $auctionId,
    'amount'     => $amount,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
];

$errors = BidModel::validate($bid, $auction);

if (!empty($errors)) {
    $fraudTypes = array_column($errors, 'type');
    $messages   = array_column($errors, 'message');

    // Flag fraud types (except simple validation errors)
    $fraudErrors = array_filter($fraudTypes, fn($t) => in_array($t, ['rapid_bidding', 'self_bid', 'abnormal_jump']));
    $isFlagged   = !empty($fraudErrors);

    echo json_encode([
        'success'  => false,
        'message'  => implode(' ', $messages),
        'flagged'  => $isFlagged,
        'error_types' => $fraudTypes
    ]);
    exit;
}

// Check if this is flagged (abnormal jump - allow but flag)
$isAbnormal = ((float)$amount / (float)$auction['current_price']) * 100 - 100 > FRAUD_BID_JUMP_PERCENT;
$bid['is_flagged']   = $isAbnormal ? 1 : 0;
$bid['flag_reason']  = $isAbnormal ? 'Abnormal bid jump' : null;

try {
    $bidId = BidModel::place($bid);

    // Get updated auction and bid list
    $updatedAuction = AuctionModel::getById($auctionId);
    $bids           = BidModel::getByAuction($auctionId);

    echo json_encode([
        'success'       => true,
        'message'       => 'Bid placed successfully!',
        'bid_id'        => $bidId,
        'new_price'     => $updatedAuction['current_price'],
        'new_price_fmt' => formatINR($updatedAuction['current_price']),
        'total_bids'    => $updatedAuction['total_bids'],
        'min_next_bid'  => $updatedAuction['current_price'] + $updatedAuction['min_bid_increment'],
        'bid_history'   => array_map(fn($b) => [
            'username'  => $b['username'],
            'amount'    => formatINR($b['amount']),
            'time'      => date('d M, h:i A', strtotime($b['bid_time'])),
            'is_winning'=> (bool)$b['is_winning']
        ], $bids)
    ]);
} catch (RuntimeException $e) {
    // Clean business-logic errors (race condition, auction ended mid-bid, etc.)
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to place bid. Please try again.']);
}