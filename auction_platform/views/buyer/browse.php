<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';

requireRole('buyer');

// Auto-close expired
AuctionModel::closeExpired();

$perPage = 9;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$filters = [
    'category'  => $_GET['category'] ?? '',
    'search'    => $_GET['search'] ?? '',
    'min_price' => $_GET['min_price'] ?? '',
    'max_price' => $_GET['max_price'] ?? '',
];

$auctions = AuctionModel::getAll($filters, $perPage, $offset);
$total    = AuctionModel::getTotalCount($filters);
$pages    = ceil($total / $perPage);

$categories = Database::query("SELECT * FROM categories ORDER BY name")->fetchAll();

$pageTitle = 'Browse Auctions';
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
  <h1>Live Auctions</h1>
  <p>Real-time bidding — all prices in Indian Rupees (₹)</p>
</div>

<!-- FILTERS -->
<div class="card" style="margin-bottom:28px;">
  <div class="card-body" style="padding:20px;">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
      <div style="flex:2;min-width:180px;">
        <label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Search auctions..." value="<?= htmlspecialchars($filters['search']) ?>">
      </div>
      <div style="flex:1;min-width:140px;">
        <label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Category</label>
        <select name="category" class="form-control">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['name'] ?>" <?= $filters['category'] === $cat['name'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="flex:1;min-width:120px;">
        <label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Min Price (₹)</label>
        <input type="number" name="min_price" class="form-control" placeholder="0" value="<?= htmlspecialchars($filters['min_price']) ?>">
      </div>
      <div style="flex:1;min-width:120px;">
        <label style="display:block;font-size:0.78rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:0.8px;margin-bottom:6px;">Max Price (₹)</label>
        <input type="number" name="max_price" class="form-control" placeholder="Any" value="<?= htmlspecialchars($filters['max_price']) ?>">
      </div>
      <div style="display:flex;gap:8px;">
        <button type="submit" class="btn btn-gold">Search</button>
        <a href="browse.php" class="btn btn-outline">Clear</a>
      </div>
    </form>
  </div>
</div>

<!-- AUCTION GRID -->
<?php if (empty($auctions)): ?>
  <div class="card" style="text-align:center;padding:60px;">
    <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
    <h3 style="color:var(--text-muted);">No active auctions found</h3>
    <p style="color:var(--text-muted);margin-top:8px;">Try adjusting your filters or check back later.</p>
  </div>
<?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px;margin-bottom:32px;">
    <?php foreach ($auctions as $a):
      $time = timeRemaining($a['end_time']);
      $minNext = (float)$a['current_price'] + (float)$a['min_bid_increment'];
    ?>
    <div class="auction-card" data-id="<?= $a['id'] ?>" style="background:var(--dark-3);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:all 0.2s;cursor:pointer;"
      onmouseover="this.style.borderColor='rgba(201,168,76,0.4)';this.style.transform='translateY(-2px)'"
      onmouseout="this.style.borderColor='var(--border)';this.style.transform='translateY(0)'">
      <!-- Image -->
      <div style="position:relative;background:var(--surface);height:200px;overflow:hidden;">
        <?php
          $imgFile = UPLOAD_PATH . $a['image_path'];
          $imgUrl  = (file_exists($imgFile) && $a['image_path'] !== 'default_item.jpg')
                    ? UPLOAD_URL . $a['image_path']
                    : '';
        ?>
        <?php if ($imgUrl): ?>
          <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($a['title']) ?>"
            style="width:100%;height:100%;object-fit:cover;">
        <?php else: ?>
          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:4rem;">🏷️</div>
        <?php endif; ?>
        <div style="position:absolute;top:12px;right:12px;">
          <span class="badge badge-active">🔴 Live</span>
        </div>
        <?php if ($a['category']): ?>
        <div style="position:absolute;bottom:12px;left:12px;">
          <span style="background:rgba(0,0,0,0.7);color:var(--text-muted);font-size:0.75rem;padding:4px 10px;border-radius:100px;">
            <?= htmlspecialchars($a['category']) ?>
          </span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Content -->
      <div style="padding:20px;">
        <h3 style="font-size:1rem;font-weight:600;margin-bottom:6px;line-height:1.4;">
          <?= htmlspecialchars($a['title']) ?>
        </h3>
        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:16px;">
          by <?= htmlspecialchars($a['seller_name']) ?>
        </p>

        <!-- Price Row -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
          <div>
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;">Current Bid</div>
            <div class="current-price" style="font-family:'Playfair Display',serif;font-size:1.4rem;font-weight:700;color:var(--gold);">
              <?= formatINR($a['current_price']) ?>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.6px;">Total Bids</div>
            <div style="font-size:1.1rem;font-weight:700;"><?= $a['total_bids'] ?></div>
          </div>
        </div>

        <!-- Timer -->
        <div class="countdown-block" data-end="<?= $a['end_time'] ?>"
          style="background:var(--surface);border-radius:8px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between;">
          <span style="font-size:0.78rem;color:var(--text-muted);">⏰ Ends in</span>
          <span class="countdown-display" style="font-weight:700;font-size:0.95rem;color:<?= $time['expired'] ? 'var(--danger)' : 'var(--text)' ?>;">
            <?= $time['expired'] ? 'Ended' : $time['text'] ?>
          </span>
        </div>

        <a href="<?= APP_URL ?>/views/buyer/auction_detail.php?id=<?= $a['id'] ?>"
          class="btn btn-gold" style="width:100%;justify-content:center;text-align:center;">
          Place Bid — Min <?= formatINR($minNext) ?>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- PAGINATION -->
  <?php if ($pages > 1): ?>
  <div class="pagination">
    <?php if ($page > 1): ?>
      <a href="?page=<?= $page-1 ?>&<?= http_build_query(array_filter($filters)) ?>" class="page-btn">← Prev</a>
    <?php endif; ?>
    <?php for ($i = max(1, $page-2); $i <= min($pages, $page+2); $i++): ?>
      <a href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"
        class="page-btn <?= $i === $page ? 'current' : '' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $pages): ?>
      <a href="?page=<?= $page+1 ?>&<?= http_build_query(array_filter($filters)) ?>" class="page-btn">Next →</a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>

<script>
// Countdown timers
function updateCountdowns() {
  document.querySelectorAll('.countdown-block').forEach(block => {
    const end     = new Date(block.dataset.end.replace(' ', 'T'));
    const now     = new Date();
    const diff    = end - now;
    const display = block.querySelector('.countdown-display');
    if (diff <= 0) {
      display.textContent = 'Ended';
      display.style.color = 'var(--danger)';
    } else {
      const h = Math.floor(diff / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      display.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      if (diff < 300000) display.style.color = 'var(--danger)'; // red if < 5min
      else if (diff < 1800000) display.style.color = 'var(--warning)'; // orange < 30min
    }
  });
}
setInterval(updateCountdowns, 1000);
updateCountdowns();
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
