<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';

requireRole('seller');
AuctionModel::closeExpired();

$user     = getCurrentUser();
$auctions = AuctionModel::getBySeller($user['id']);

$pageTitle = 'My Auctions';
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:flex-start;">
  <div>
    <h1>My Auctions</h1>
    <p><?= count($auctions) ?> total listings</p>
  </div>
  <a href="create_auction.php" class="btn btn-gold">+ New Auction</a>
</div>

<?php if (empty($auctions)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <div style="font-size:3rem;margin-bottom:16px;">🏷</div>
  <h3 style="color:var(--text-muted);">No auctions yet</h3>
  <p style="color:var(--text-muted);margin-top:8px;margin-bottom:24px;">Create your first auction to start selling.</p>
  <a href="create_auction.php" class="btn btn-gold">Create Auction</a>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th>Status</th>
          <th>Starting</th>
          <th>Current Bid</th>
          <th>Total Bids</th>
          <th>Winner</th>
          <th>End Time</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($auctions as $a):
          $time = timeRemaining($a['end_time']);
        ?>
        <tr>
          <td>
            <div style="font-weight:600;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
              <?= htmlspecialchars($a['title']) ?>
            </div>
            <?php if ($a['category']): ?>
            <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($a['category']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
            <?php if ($a['is_overridden']): ?>
              <div style="font-size:0.72rem;color:var(--danger);margin-top:2px;">Admin Override</div>
            <?php endif; ?>
          </td>
          <td><?= formatINR($a['starting_price']) ?></td>
          <td style="font-weight:700;color:var(--gold);">
            <?= $a['total_bids'] > 0 ? formatINR($a['current_price']) : '—' ?>
          </td>
          <td style="text-align:center;"><?= $a['total_bids'] ?></td>
          <td style="font-size:0.85rem;">
            <?php if ($a['status'] === 'ended' && $a['winner_id']): ?>
              <span style="color:var(--gold);">🏆 <?= htmlspecialchars($a['winner_name']) ?></span>
            <?php elseif ($a['status'] === 'active'): ?>
              <span style="color:var(--text-muted);">Ongoing</span>
            <?php else: ?>
              <span style="color:var(--text-muted);">—</span>
            <?php endif; ?>
          </td>
          <td style="font-size:0.82rem;">
            <?php if ($a['status'] === 'active'): ?>
              <span class="countdown-cell" data-end="<?= $a['end_time'] ?>" style="color:var(--warning);">
                <?= $time['text'] ?>
              </span>
            <?php else: ?>
              <span style="color:var(--text-muted);"><?= date('d M Y', strtotime($a['end_time'])) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:6px;">
              <a href="analytics.php?id=<?= $a['id'] ?>" class="btn btn-outline btn-sm">📈</a>
              <?php if ($a['status'] === 'ended' && $a['winner_id']): ?>
                <?php
                  $tx = Database::query("SELECT id FROM transactions WHERE auction_id = ?", [$a['id']])->fetch();
                  if ($tx): ?>
                  <a href="../buyer/transaction_detail.php?id=<?= $tx['id'] ?>" class="btn btn-gold btn-sm">💬</a>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<script>
function updateCountdowns() {
  document.querySelectorAll('.countdown-cell').forEach(cell => {
    const end  = new Date(cell.dataset.end.replace(' ', 'T'));
    const diff = end - new Date();
    if (diff <= 0) { cell.textContent = 'Ended'; cell.style.color='var(--danger)'; return; }
    const h = Math.floor(diff/3600000);
    const m = Math.floor((diff%3600000)/60000);
    const s = Math.floor((diff%60000)/1000);
    cell.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if (diff < 300000) cell.style.color='var(--danger)';
  });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
