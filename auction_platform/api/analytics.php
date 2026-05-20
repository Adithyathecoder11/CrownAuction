<?php
// api/analytics.php

header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../models/AuctionModel.php';
require_once __DIR__ . '/../models/BidModel.php';

requireLogin();

$user      = getCurrentUser();
$auctionId = (int)($_GET['auction_id'] ?? 0);

if (!$auctionId) {
    echo json_encode(['success' => false, 'message' => 'Auction ID required.']);
    exit;
}

$auction = AuctionModel::getById($auctionId);
if (!$auction || (int)$auction['seller_id'] !== (int)$user['id']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$bids     = BidModel::getByAuction($auctionId);
$trend    = AuctionModel::getBidTrend($auctionId);

$amounts  = array_column($bids, 'amount');
$avgBid   = !empty($amounts) ? array_sum($amounts) / count($amounts) : 0;
$uniqueBidders = count(array_unique(array_column($bids, 'bidder_id')));

// Build chart data
$chartLabels = array_column($trend, 'time_label');
$chartData   = array_column($trend, 'amount');

echo json_encode([
    'success'        => true,
    'auction_title'  => $auction['title'],
    'total_bids'     => count($bids),
    'highest_bid'    => !empty($amounts) ? max($amounts) : $auction['starting_price'],
    'highest_bid_fmt'=> formatINR(!empty($amounts) ? max($amounts) : $auction['starting_price']),
    'avg_bid'        => round($avgBid, 2),
    'avg_bid_fmt'    => formatINR($avgBid),
    'unique_bidders' => $uniqueBidders,
    'starting_price' => (float)$auction['starting_price'],
    'chart_labels'   => $chartLabels,
    'chart_data'     => array_map('floatval', $chartData),
    'status'         => $auction['status'],
    'end_time'       => $auction['end_time'],
]);
