<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';
require_once __DIR__ . '/../../models/BidModel.php';
require_once __DIR__ . '/../../models/TransactionModel.php';

requireRole('seller');
AuctionModel::closeExpired();

$user     = getCurrentUser();
$auctions = AuctionModel::getSellerAnalytics($user['id']);

// Aggregate stats
$totalAuctions  = count($auctions);
$activeAuctions = count(array_filter($auctions, fn($a) => $a['status'] === 'active'));
$totalBids      = array_sum(array_column($auctions, 'total_bids'));
// Revenue = sum of current_price for all ended auctions that have a winner
$totalRevenue = 0;
foreach ($auctions as $a) {
    if ($a['status'] === 'ended' && !empty($a['winner_id'])) {
        $totalRevenue += (float)$a['current_price'];
    }
}

// Recent activity
$recentBids = Database::query(
    "SELECT b.*, a.title, u.username FROM bids b
     JOIN auctions a ON b.auction_id = a.id
     JOIN users u ON b.bidder_id = u.id
     WHERE a.seller_id = ?
     ORDER BY b.bid_time DESC LIMIT 8",
    [$user['id']]
)->fetchAll();

// Bid trend chart data (all auctions, last 20 bids)
$trendBids = Database::query(
    "SELECT b.amount, DATE_FORMAT(b.bid_time,'%d %b %H:%i') as label
     FROM bids b
     JOIN auctions a ON b.auction_id = a.id
     WHERE a.seller_id = ?
     ORDER BY b.bid_time DESC LIMIT 20",
    [$user['id']]
)->fetchAll();
$trendBids = array_reverse($trendBids);

$pageTitle = 'Seller Dashboard';
include __DIR__ . '/../shared/header.php';
?>

<style>
.quick-action {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 24px; background: var(--dark-3); border: 1px solid var(--border);
  border-radius: 14px; text-decoration: none; color: var(--text);
  transition: all 0.2s; text-align: center; gap: 10px; cursor: pointer;
}
.quick-action:hover { border-color: var(--gold); transform: translateY(-2px); }
.quick-action .icon { font-size: 2rem; }
.quick-action .label { font-weight: 600; font-size: 0.9rem; }
</style>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
  <div>
    <h1>Welcome back, <?= htmlspecialchars($user['name'] ?: $user['username']) ?> 👋</h1>
    <p>Your seller dashboard — manage auctions, track bids and performance</p>
  </div>
  <a href="create_auction.php" class="btn btn-gold">+ Create Auction</a>
</div>

<!-- Stats Row -->
<div class="grid-4" style="margin-bottom:28px;">
  <div class="stat-card">
    <div class="stat-label">Total Auctions</div>
    <div class="stat-value"><?= $totalAuctions ?></div>
    <div class="stat-sub"><?= $activeAuctions ?> currently live</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Total Bids Received</div>
    <div class="stat-value" style="color:var(--success);"><?= $totalBids ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Revenue Earned</div>
    <div class="stat-value" style="font-size:1.5rem;"><?= formatINR($totalRevenue) ?></div>
    <div class="stat-sub">from completed auctions</div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Active Now</div>
    <div class="stat-value" style="color:var(--warning);"><?= $activeAuctions ?></div>
    <div class="stat-sub">live auctions</div>
  </div>
</div>

<!-- Quick Actions -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;">
  <a href="create_auction.php" class="quick-action">
    <span class="icon">➕</span>
    <span class="label">New Auction</span>
  </a>
  <a href="my_auctions.php" class="quick-action">
    <span class="icon">📋</span>
    <span class="label">My Auctions</span>
  </a>
  <a href="analytics.php" class="quick-action">
    <span class="icon">📈</span>
    <span class="label">Analytics</span>
  </a>
  <a href="transactions.php" class="quick-action">
    <span class="icon">💼</span>
    <span class="label">Transactions</span>
  </a>
</div>

<div class="grid-2" style="gap:24px;">
  <!-- Bid Trend Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">📈 Recent Bid Trend</span>
      <span style="font-size:0.78rem;color:var(--text-muted);">Last 20 bids across all auctions</span>
    </div>
    <div class="card-body">
      <?php if (empty($trendBids)): ?>
        <div style="text-align:center;padding:40px 0;color:var(--text-muted);">No bids yet</div>
      <?php else: ?>
        <canvas id="trendChart" height="220"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Activity -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">🔔 Recent Bids</span>
    </div>
    <div style="max-height:340px;overflow-y:auto;">
      <?php if (empty($recentBids)): ?>
        <div style="padding:24px;text-align:center;color:var(--text-muted);">No bids yet on your auctions.</div>
      <?php else: ?>
        <?php foreach ($recentBids as $b): ?>
        <div style="padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-weight:600;font-size:0.88rem;"><?= htmlspecialchars($b['username']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars(substr($b['title'],0,32)) ?>...</div>
          </div>
          <div style="text-align:right;">
            <div style="font-weight:700;color:var(--gold);"><?= formatINR($b['amount']) ?></div>
            <div style="font-size:0.73rem;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($b['bid_time'])) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Auction Performance Table -->
<?php if (!empty($auctions)): ?>
<div class="card" style="margin-top:24px;">
  <div class="card-header">
    <span class="card-title">🏷 Auction Performance</span>
    <a href="my_auctions.php" style="font-size:0.82rem;color:var(--gold);text-decoration:none;">View all →</a>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Status</th>
          <th>Starting Price</th>
          <th>Highest Bid</th>
          <th>Total Bids</th>
          <th>Unique Bidders</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($auctions, 0, 6) as $a): ?>
        <tr>
          <td style="font-weight:600;max-width:220px;">
            <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($a['title']) ?></div>
          </td>
          <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
          <td><?= formatINR($a['starting_price']) ?></td>
          <td style="color:var(--gold);font-weight:600;"><?= $a['highest_bid'] ? formatINR($a['highest_bid']) : '—' ?></td>
          <td><?= $a['total_bids'] ?></td>
          <td><?= $a['unique_bidders'] ?></td>
          <td>
            <a href="analytics.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">Analytics</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
<?php if (!empty($trendBids)): ?>
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($trendBids, 'label')) ?>,
    datasets: [{
      label: 'Bid Amount (₹)',
      data: <?= json_encode(array_map(fn($b) => (float)$b['amount'], $trendBids)) ?>,
      borderColor: '#C9A84C',
      backgroundColor: 'rgba(201,168,76,0.08)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#C9A84C',
      pointRadius: 4,
      borderWidth: 2,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
      x: { ticks: { color: '#8A8480', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { ticks: { color: '#8A8480', callback: v => '₹' + v.toLocaleString('en-IN') }, grid: { color: 'rgba(255,255,255,0.06)' } }
    }
  }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>