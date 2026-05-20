<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';

requireRole('buyer');
AuctionModel::closeExpired();

$user = getCurrentUser();

$myBids = Database::query(
    "SELECT b.*, a.title, a.current_price, a.status as auction_status, a.end_time, a.image_path, a.winner_id,
            MAX(b2.amount) as top_bid
     FROM bids b
     JOIN auctions a ON b.auction_id = a.id
     LEFT JOIN bids b2 ON b2.auction_id = a.id
     WHERE b.bidder_id = ?
     GROUP BY b.id
     ORDER BY b.bid_time DESC",
    [$user['id']]
)->fetchAll();

// Dedupe by auction, keep latest bid per auction
$seen = [];
$deduped = [];
foreach ($myBids as $row) {
    if (!isset($seen[$row['auction_id']])) {
        $seen[$row['auction_id']] = true;
        $deduped[] = $row;
    }
}

$pageTitle = 'My Bids';
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
  <h1>My Bids</h1>
  <p>All auctions you have participated in</p>
</div>

<?php if (empty($deduped)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <div style="font-size:3rem;margin-bottom:16px;">🔨</div>
  <h3 style="color:var(--text-muted);">No bids yet</h3>
  <p style="color:var(--text-muted);margin-top:8px;margin-bottom:24px;">Find something worth bidding on!</p>
  <a href="browse.php" class="btn btn-gold">Browse Auctions</a>
</div>
<?php else: ?>

<!-- Summary Stats -->
<div class="grid-4" style="margin-bottom:28px;">
  <?php
    $won    = count(array_filter($deduped, fn($b) => $b['winner_id'] == $user['id'] && $b['auction_status'] === 'ended'));
    $active = count(array_filter($deduped, fn($b) => $b['auction_status'] === 'active'));
    $leading = count(array_filter($deduped, fn($b) => $b['auction_status'] === 'active' && $b['amount'] == $b['top_bid']));
    $total  = count($deduped);
  ?>
  <div class="stat-card">
    <div class="stat-label">Total Participated</div>
    <div class="stat-value"><?= $total ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Currently Active</div>
    <div class="stat-value" style="color:var(--success);"><?= $active ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Currently Leading</div>
    <div class="stat-value" style="color:var(--warning);"><?= $leading ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Auctions Won</div>
    <div class="stat-value" style="color:var(--gold);"><?= $won ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Bid History</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Your Bid</th>
          <th>Current Price</th>
          <th>Status</th>
          <th>Ends</th>
          <th>Result</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($deduped as $b):
          $isWinning = ($b['auction_status'] === 'active' && (float)$b['amount'] >= (float)$b['top_bid']);
          $hasWon    = ($b['winner_id'] == $user['id'] && $b['auction_status'] === 'ended');
          $hasLost   = ($b['winner_id'] != $user['id'] && $b['auction_status'] === 'ended' && $b['winner_id']);
        ?>
        <tr>
          <td>
            <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($b['title']) ?></div>
          </td>
          <td style="font-weight:600;color:var(--gold);"><?= formatINR($b['amount']) ?></td>
          <td><?= formatINR($b['current_price']) ?></td>
          <td>
            <span class="badge badge-<?= $b['auction_status'] ?>"><?= ucfirst($b['auction_status']) ?></span>
          </td>
          <td style="font-size:0.83rem;color:var(--text-muted);">
            <?= date('d M Y, h:i A', strtotime($b['end_time'])) ?>
          </td>
          <td>
            <?php if ($hasWon): ?>
              <span style="color:var(--gold);font-weight:700;">🏆 Won!</span>
            <?php elseif ($hasLost): ?>
              <span style="color:var(--text-muted);">Outbid</span>
            <?php elseif ($isWinning): ?>
              <span style="color:var(--success);font-weight:600;">👑 Leading</span>
            <?php elseif ($b['auction_status'] === 'active'): ?>
              <span style="color:var(--danger);">⬇ Outbid</span>
            <?php else: ?>
              <span style="color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="auction_detail.php?id=<?= $b['auction_id'] ?>" class="btn btn-outline btn-sm">View</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../shared/footer.php'; ?>
