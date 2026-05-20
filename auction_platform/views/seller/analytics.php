<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';
require_once __DIR__ . '/../../models/BidModel.php';

requireRole('seller');

$user = getCurrentUser();
$auctionId = (int)($_GET['id'] ?? 0);

// Show auction selector if no ID given
$myAuctions = AuctionModel::getBySeller($user['id']);

$auction = null;
$bids    = [];
$trend   = [];
$stats   = [];

if ($auctionId) {
    $auction = AuctionModel::getById($auctionId);
    if (!$auction || (int)$auction['seller_id'] !== (int)$user['id']) {
        setFlash('error', 'Auction not found.');
        header('Location: analytics.php');
        exit;
    }
    $bids   = BidModel::getByAuction($auctionId);
    $trend  = AuctionModel::getBidTrend($auctionId);
    $amounts= array_column($bids, 'amount');
    $stats  = [
        'total'    => count($bids),
        'highest'  => !empty($amounts) ? max($amounts) : $auction['starting_price'],
        'avg'      => !empty($amounts) ? array_sum($amounts) / count($amounts) : 0,
        'unique'   => count(array_unique(array_column($bids, 'bidder_id'))),
        'gain'     => !empty($amounts) ? max($amounts) - $auction['starting_price'] : 0,
    ];
}

$pageTitle = 'Analytics' . ($auction ? ' — ' . $auction['title'] : '');
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
  <div>
    <h1>📈 Auction Analytics</h1>
    <p>Deep dive into your auction performance metrics</p>
  </div>
  <a href="dashboard.php" style="color:var(--gold);font-size:0.85rem;text-decoration:none;">← Dashboard</a>
</div>

<!-- Auction Selector -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-body" style="padding:16px;">
    <form method="GET" style="display:flex;gap:12px;align-items:flex-end;">
      <div style="flex:1;">
        <label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Select Auction</label>
        <select name="id" class="form-control" onchange="this.form.submit()">
          <option value="">— Choose an auction —</option>
          <?php foreach ($myAuctions as $a): ?>
            <option value="<?= $a['id'] ?>" <?= $auctionId == $a['id'] ? 'selected' : '' ?>>
              [<?= ucfirst($a['status']) ?>] <?= htmlspecialchars($a['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$auction): ?>
<div class="card" style="text-align:center;padding:60px;">
  <div style="font-size:3rem;margin-bottom:16px;">📊</div>
  <h3 style="color:var(--text-muted);">Select an auction above to view analytics</h3>
</div>

<?php else: ?>

<!-- KPI Row -->
<div class="grid-4" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-label">Total Bids</div>
    <div class="stat-value"><?= $stats['total'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Highest Bid</div>
    <div class="stat-value" style="font-size:1.4rem;"><?= formatINR($stats['highest']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Average Bid</div>
    <div class="stat-value" style="font-size:1.4rem;"><?= formatINR($stats['avg']) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Unique Bidders</div>
    <div class="stat-value"><?= $stats['unique'] ?></div>
  </div>
</div>

<!-- Extra KPIs -->
<div class="grid-2" style="gap:20px;margin-bottom:24px;">
  <div class="stat-card" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="stat-label">Price Gain</div>
      <div class="stat-value" style="color:var(--success);font-size:1.4rem;"><?= formatINR($stats['gain']) ?></div>
      <div class="stat-sub">above starting price of <?= formatINR($auction['starting_price']) ?></div>
    </div>
    <div style="font-size:3rem;">📈</div>
  </div>
  <div class="stat-card" style="display:flex;align-items:center;justify-content:space-between;">
    <div>
      <div class="stat-label">Auction Status</div>
      <div style="margin-top:8px;"><span class="badge badge-<?= $auction['status'] ?>" style="font-size:0.9rem;padding:6px 14px;"><?= ucfirst($auction['status']) ?></span></div>
      <div class="stat-sub" style="margin-top:6px;">Ends: <?= date('d M Y, h:i A', strtotime($auction['end_time'])) ?></div>
    </div>
    <div style="font-size:3rem;">🏷</div>
  </div>
</div>

<div class="grid-2" style="gap:24px;margin-bottom:24px;">
  <!-- Bid Trend Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Bid Trend Over Time</span>
    </div>
    <div class="card-body">
      <?php if (empty($trend)): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">No bids yet for this auction.</div>
      <?php else: ?>
        <canvas id="bidTrendChart" height="250"></canvas>
      <?php endif; ?>
    </div>
  </div>

  <!-- Bid Distribution -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Bid Amount Distribution</span>
    </div>
    <div class="card-body">
      <?php if (count($bids) < 2): ?>
        <div style="text-align:center;padding:40px;color:var(--text-muted);">Need at least 2 bids for distribution chart.</div>
      <?php else: ?>
        <canvas id="bidDistChart" height="250"></canvas>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Bidder Leaderboard -->
<?php if (!empty($bids)):
  $bidderMap = [];
  foreach ($bids as $b) {
      $uid = $b['bidder_id'];
      if (!isset($bidderMap[$uid])) $bidderMap[$uid] = ['username' => $b['username'], 'bids' => 0, 'highest' => 0];
      $bidderMap[$uid]['bids']++;
      if ((float)$b['amount'] > $bidderMap[$uid]['highest']) $bidderMap[$uid]['highest'] = (float)$b['amount'];
  }
  usort($bidderMap, fn($a, $b) => $b['highest'] <=> $a['highest']);
?>
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <span class="card-title">🏆 Bidder Leaderboard</span>
    <span style="font-size:0.78rem;color:var(--text-muted);"><?= count($bidderMap) ?> unique bidders</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>#</th><th>Bidder</th><th>Total Bids Placed</th><th>Highest Bid</th></tr>
      </thead>
      <tbody>
        <?php foreach (array_values($bidderMap) as $idx => $b): ?>
        <tr>
          <td><?php if ($idx === 0): ?>👑<?php else: ?><?= $idx + 1 ?><?php endif; ?></td>
          <td style="font-weight:600;"><?= htmlspecialchars($b['username']) ?></td>
          <td><?= $b['bids'] ?></td>
          <td style="font-weight:700;color:var(--gold);"><?= formatINR($b['highest']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Full Bid History -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Full Bid Log</span>
    <span style="font-size:0.78rem;color:var(--text-muted);"><?= count($bids) ?> entries</span>
  </div>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr><th>Bidder</th><th>Amount</th><th>Time</th><th>Flag</th></tr>
      </thead>
      <tbody>
        <?php foreach ($bids as $b): ?>
        <tr>
          <td><?= htmlspecialchars($b['username']) ?></td>
          <td style="font-weight:600;color:<?= $b['is_winning'] ? 'var(--gold)' : 'var(--text)' ?>;">
            <?= formatINR($b['amount']) ?>
            <?php if ($b['is_winning']): ?> 👑<?php endif; ?>
          </td>
          <td style="font-size:0.83rem;color:var(--text-muted);"><?= date('d M Y, h:i:s A', strtotime($b['bid_time'])) ?></td>
          <td>
            <?php if ($b['is_flagged']): ?>
              <span class="badge badge-flagged">⚠ Flagged</span>
            <?php else: ?>
              <span style="color:var(--success);font-size:0.8rem;">✓ Clean</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; // auction selected ?>

<script>
<?php if ($auction && !empty($trend)): ?>
const trendCtx = document.getElementById('bidTrendChart').getContext('2d');
new Chart(trendCtx, {
  type: 'line',
  data: {
    labels: <?= json_encode(array_column($trend, 'time_label')) ?>,
    datasets: [{
      label: 'Bid Amount (₹)',
      data: <?= json_encode(array_map(fn($t) => (float)$t['amount'], $trend)) ?>,
      borderColor: '#C9A84C',
      backgroundColor: 'rgba(201,168,76,0.1)',
      fill: true,
      tension: 0.4,
      pointRadius: 5,
      pointBackgroundColor: '#C9A84C',
      borderWidth: 2,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => '₹' + ctx.raw.toLocaleString('en-IN') } } },
    scales: {
      x: { ticks: { color: '#8A8480', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.04)' } },
      y: { ticks: { color: '#8A8480', callback: v => '₹' + v.toLocaleString('en-IN') }, grid: { color: 'rgba(255,255,255,0.05)' } }
    }
  }
});
<?php endif; ?>

<?php if ($auction && count($bids) >= 2): ?>
const distCtx = document.getElementById('bidDistChart').getContext('2d');
const bidAmounts = <?= json_encode(array_map(fn($b) => (float)$b['amount'], array_reverse($bids))) ?>;
const bidLabels  = bidAmounts.map((_, i) => 'Bid ' + (i + 1));
new Chart(distCtx, {
  type: 'bar',
  data: {
    labels: bidLabels,
    datasets: [{
      label: 'Bid Amount',
      data: bidAmounts,
      backgroundColor: bidAmounts.map((_, i) => i === bidAmounts.length - 1 ? 'rgba(201,168,76,0.8)' : 'rgba(201,168,76,0.3)'),
      borderColor: '#C9A84C',
      borderWidth: 1,
      borderRadius: 4,
    }]
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => '₹' + ctx.raw.toLocaleString('en-IN') } } },
    scales: {
      x: { ticks: { color: '#8A8480', font: { size: 10 } }, grid: { display: false } },
      y: { ticks: { color: '#8A8480', callback: v => '₹' + v.toLocaleString('en-IN') }, grid: { color: 'rgba(255,255,255,0.05)' } }
    }
  }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
