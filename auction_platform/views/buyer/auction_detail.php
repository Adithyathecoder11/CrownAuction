<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/AuctionModel.php';
require_once __DIR__ . '/../../models/BidModel.php';
require_once __DIR__ . '/../../models/TransactionModel.php';

requireRole('buyer');

AuctionModel::closeExpired();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: browse.php'); exit; }

$auction = AuctionModel::getById($id);
if (!$auction) { setFlash('error','Auction not found.'); header('Location: browse.php'); exit; }

$bids     = BidModel::getByAuction($id);
$time     = timeRemaining($auction['end_time']);
$user     = getCurrentUser();
$minNext  = (float)$auction['current_price'] + (float)$auction['min_bid_increment'];
$csrf     = csrfToken();

// If auction ended and there's a winner, create transaction
$transaction = null;
if ($auction['status'] === 'ended' && $auction['winner_id']) {
    TransactionModel::createFromAuction($id);
    $transaction = TransactionModel::getByAuction($id);
}

$pageTitle = htmlspecialchars($auction['title']);
include __DIR__ . '/../shared/header.php';
?>

<style>
.auction-layout { display: grid; grid-template-columns: 1fr 420px; gap: 28px; align-items: start; }
.sticky-sidebar { position: sticky; top: 88px; }
.bid-history-item { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--border); }
.bid-history-item:last-child { border-bottom: none; }
.winner-badge { background: linear-gradient(135deg, rgba(201,168,76,0.2), rgba(201,168,76,0.05)); border: 1px solid rgba(201,168,76,0.4); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
.bid-input-group { display: flex; gap: 10px; }
.bid-input-group input { flex: 1; }
#bid-status { margin-top: 12px; display: none; }
.auction-image { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 12px; background: var(--surface); display: flex; align-items: center; justify-content: center; font-size: 6rem; }
.timer-display { font-family: 'Playfair Display', serif; font-size: 2.2rem; font-weight: 700; letter-spacing: 2px; }
@media(max-width:900px) { .auction-layout { grid-template-columns: 1fr; } .sticky-sidebar { position: static; } }
</style>

<!-- Breadcrumb -->
<div style="margin-bottom:20px;font-size:0.85rem;color:var(--text-muted);">
  <a href="browse.php" style="color:var(--gold);text-decoration:none;">← Back to Auctions</a>
  <span style="margin:0 8px;">/</span>
  <?= htmlspecialchars($auction['title']) ?>
</div>

<div class="auction-layout">

  <!-- LEFT COLUMN -->
  <div>
    <!-- Image -->
    <div style="margin-bottom:24px;border-radius:14px;overflow:hidden;background:var(--surface);border:1px solid var(--border);">
      <?php
        $imgFile = UPLOAD_PATH . $auction['image_path'];
        $imgUrl  = (file_exists($imgFile) && $auction['image_path'] !== 'default_item.jpg')
                  ? UPLOAD_URL . $auction['image_path'] : null;
      ?>
      <?php if ($imgUrl): ?>
        <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($auction['title']) ?>" style="width:100%;max-height:460px;object-fit:cover;display:block;">
      <?php else: ?>
        <div style="height:300px;display:flex;align-items:center;justify-content:center;font-size:6rem;">🏷️</div>
      <?php endif; ?>
    </div>

    <!-- Details -->
    <div class="card" style="margin-bottom:24px;">
      <div class="card-header">
        <span class="card-title">Item Details</span>
        <?php if ($auction['category']): ?>
          <span class="badge badge-active"><?= htmlspecialchars($auction['category']) ?></span>
        <?php endif; ?>
      </div>
      <div class="card-body">
        <h1 style="font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700;margin-bottom:12px;"><?= htmlspecialchars($auction['title']) ?></h1>
        <p style="color:var(--text-muted);line-height:1.8;margin-bottom:20px;"><?= nl2br(htmlspecialchars($auction['description'])) ?></p>
        <div class="grid-2" style="gap:16px;">
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Seller</div>
            <div style="font-weight:600;">👤 <?= htmlspecialchars($auction['seller_fullname'] ?: $auction['seller_name']) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Starting Price</div>
            <div style="font-weight:600;"><?= formatINR($auction['starting_price']) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Min Increment</div>
            <div style="font-weight:600;"><?= formatINR($auction['min_bid_increment']) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Auction Ends</div>
            <div style="font-weight:600;"><?= date('d M Y, h:i A', strtotime($auction['end_time'])) ?></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bid History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">📋 Bid History</span>
        <span style="font-size:0.82rem;color:var(--text-muted);" id="bid-count-label"><?= count($bids) ?> bids</span>
      </div>
      <div class="card-body" style="padding:0;">
        <div id="bid-history-list" style="padding: 0 24px; max-height: 400px; overflow-y: auto;">
          <?php if (empty($bids)): ?>
            <p style="padding:24px;text-align:center;color:var(--text-muted);">No bids yet. Be the first!</p>
          <?php else: ?>
            <?php foreach ($bids as $b): ?>
            <div class="bid-history-item">
              <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:var(--dark);">
                  <?= strtoupper(substr($b['username'], 0, 1)) ?>
                </div>
                <div>
                  <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($b['username']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($b['bid_time'])) ?></div>
                </div>
              </div>
              <div style="text-align:right;">
                <div style="font-weight:700;color:<?= $b['is_winning'] ? 'var(--gold)' : 'var(--text)' ?>;">
                  <?= formatINR($b['amount']) ?>
                </div>
                <?php if ($b['is_winning']): ?>
                  <div style="font-size:0.72rem;color:var(--gold);">👑 Leading</div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT SIDEBAR -->
  <div class="sticky-sidebar">

    <?php if ($auction['status'] === 'ended'): ?>
    <!-- AUCTION ENDED -->
    <div class="card" style="margin-bottom:20px;border-color:rgba(201,168,76,0.3);">
      <div class="card-body" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:12px;">🏆</div>
        <h3 style="font-family:'Playfair Display',serif;font-size:1.3rem;margin-bottom:6px;">Auction Ended</h3>
        <?php if ($auction['winner_id']): ?>
          <div class="winner-badge" style="text-align:left;">
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Winner</div>
            <div style="font-weight:700;font-size:1.1rem;color:var(--gold);">
              <?= htmlspecialchars(!empty($bids) ? $bids[0]['username'] : '—') ?>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">Winning Bid: <strong style="color:var(--text);"><?= formatINR($auction['current_price']) ?></strong></div>
          </div>
          <?php if ((int)$auction['winner_id'] === (int)$user['id'] && $transaction): ?>
            <a href="transaction_detail.php?id=<?= $transaction['id'] ?>" class="btn btn-gold" style="width:100%;justify-content:center;">
              🎉 View Transaction & Contact Seller
            </a>
          <?php endif; ?>
        <?php else: ?>
          <p style="color:var(--text-muted);">No bids were placed.</p>
        <?php endif; ?>
      </div>
    </div>

    <?php else: ?>
    <!-- LIVE AUCTION -->
    <!-- Timer Card -->
    <div class="card" style="margin-bottom:16px;border-color:rgba(201,168,76,0.2);">
      <div class="card-body" style="text-align:center;">
        <div style="font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Time Remaining</div>
        <div class="timer-display" id="main-timer" style="color:<?= $time['total_seconds'] < 300 ? 'var(--danger)' : 'var(--gold)' ?>;">
          <?= $time['expired'] ? '00:00:00' : $time['text'] ?>
        </div>
        <div style="height:4px;background:var(--surface);border-radius:2px;margin-top:12px;overflow:hidden;">
          <div id="timer-progress" style="height:100%;background:linear-gradient(90deg,var(--gold),var(--gold-dark));border-radius:2px;transition:width 1s linear;width:<?= min(100, max(0, 100 - ($time['total_seconds'] / ($auction['duration_minutes'] * 60)) * 100)) ?>%;"></div>
        </div>
      </div>
    </div>

    <!-- Current Price -->
    <div class="card" style="margin-bottom:16px;">
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <div>
            <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Current Price</div>
            <div id="current-price" style="font-family:'Playfair Display',serif;font-size:2rem;font-weight:700;color:var(--gold);">
              <?= formatINR($auction['current_price']) ?>
            </div>
          </div>
          <div style="text-align:right;">
            <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:4px;">Total Bids</div>
            <div id="total-bids" style="font-size:1.5rem;font-weight:700;"><?= $auction['total_bids'] ?></div>
          </div>
        </div>

        <!-- Bid Form -->
        <?php if ((int)$auction['seller_id'] !== (int)$user['id']): ?>
        <form id="bid-form">
          <input type="hidden" name="auction_id" value="<?= $auction['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
          <div style="margin-bottom:12px;">
            <label style="display:block;font-size:0.78rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:8px;">Your Bid (₹)</label>
            <div class="bid-input-group">
              <input type="number" name="amount" id="bid-amount" class="form-control"
                min="<?= $minNext ?>" step="<?= $auction['min_bid_increment'] ?>"
                placeholder="Min: <?= number_format($minNext, 2) ?>"
                value="<?= number_format($minNext, 2, '.', '') ?>">
            </div>
          </div>
          <div style="display:flex;gap:8px;margin-bottom:12px;">
            <button type="button" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;"
              onclick="document.getElementById('bid-amount').value = <?= number_format($minNext, 2, '.', '') ?>">
              Min
            </button>
            <button type="button" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;"
              onclick="document.getElementById('bid-amount').value = <?= number_format($minNext + (float)$auction['min_bid_increment'], 2, '.', '') ?>">
              Min+1
            </button>
            <button type="button" class="btn btn-outline btn-sm" style="flex:1;justify-content:center;"
              onclick="document.getElementById('bid-amount').value = <?= number_format($minNext + ((float)$auction['min_bid_increment'] * 5), 2, '.', '') ?>">
              Min+5
            </button>
          </div>
          <button type="submit" id="bid-btn" class="btn btn-gold" style="width:100%;justify-content:center;font-size:1rem;padding:14px;">
            ⚡ Place Bid
          </button>
          <div id="bid-status" class="alert"></div>
        </form>
        <?php else: ?>
          <div class="alert alert-warning" style="text-align:center;">You cannot bid on your own auction.</div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Anti-Fraud Notice -->
    <div style="background:rgba(224,160,32,0.08);border:1px solid rgba(224,160,32,0.2);border-radius:10px;padding:14px;font-size:0.8rem;color:var(--text-muted);">
      <div style="font-weight:600;color:var(--warning);margin-bottom:6px;">⚠️ Anti-Fraud Protection Active</div>
      Max <?= MAX_BIDS_PER_MINUTE ?> bids/minute. Unusual activity is flagged automatically. All bids are final.
    </div>
    <?php endif; ?>

  </div><!-- end sidebar -->
</div>

<script>
const AUCTION_ID   = <?= $auction['id'] ?>;
const AUCTION_END  = new Date('<?= str_replace(' ','T',$auction['end_time']) ?>');
const DURATION_SEC = <?= $auction['duration_minutes'] * 60 ?>;
const API_URL      = '<?= APP_URL ?>/api/get_auction.php';
const BID_URL      = '<?= APP_URL ?>/api/place_bid.php';

// ─── Countdown Timer ─────────────────────────────────────────
const timerEl    = document.getElementById('main-timer');
const progressEl = document.getElementById('timer-progress');

function updateTimer() {
  if (!timerEl) return;
  const now  = new Date();
  const diff = AUCTION_END - now;
  if (diff <= 0) {
    timerEl.textContent = '00:00:00';
    timerEl.style.color = 'var(--danger)';
    if (progressEl) progressEl.style.width = '100%';
    location.reload();
    return;
  }
  const h = Math.floor(diff / 3600000);
  const m = Math.floor((diff % 3600000) / 60000);
  const s = Math.floor((diff % 60000) / 1000);
  timerEl.textContent = `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
  if (diff < 300000) timerEl.style.color = 'var(--danger)';
  else if (diff < 1800000) timerEl.style.color = 'var(--warning)';

  const elapsed = DURATION_SEC - (diff / 1000);
  const pct = Math.min(100, (elapsed / DURATION_SEC) * 100);
  if (progressEl) progressEl.style.width = pct + '%';
}
setInterval(updateTimer, 1000);
updateTimer();

// ─── Live Poll (every 5 seconds) ─────────────────────────────
let lastBidCount = <?= count($bids) ?>;

function pollAuction() {
  fetch(`${API_URL}?id=${AUCTION_ID}`)
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;

      // Update price
      const priceEl = document.getElementById('current-price');
      if (priceEl) priceEl.textContent = data.current_price_fmt;

      // Update bid count
      const bidsEl = document.getElementById('total-bids');
      if (bidsEl) bidsEl.textContent = data.total_bids;

      // Update bid input min
      const input = document.getElementById('bid-amount');
      if (input) {
        input.min = data.min_next_bid;
        if (parseFloat(input.value) < data.min_next_bid) {
          input.value = data.min_next_bid.toFixed(2);
        }
      }

      // Refresh bid history if changed
      if (data.bid_history.length !== lastBidCount) {
        lastBidCount = data.bid_history.length;
        refreshBidHistory(data.bid_history, data.total_bids);
      }

      // Auction ended?
      if (data.status === 'ended') location.reload();
    })
    .catch(() => {});
}
setInterval(pollAuction, 5000);

function refreshBidHistory(bids, total) {
  const list  = document.getElementById('bid-history-list');
  const count = document.getElementById('bid-count-label');
  if (count) count.textContent = total + ' bids';
  if (!list) return;
  if (bids.length === 0) { list.innerHTML = '<p style="padding:24px;text-align:center;color:var(--text-muted);">No bids yet.</p>'; return; }

  list.innerHTML = bids.map(b => `
    <div class="bid-history-item">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:0.8rem;font-weight:700;color:var(--dark);">
          ${b.username.charAt(0).toUpperCase()}
        </div>
        <div>
          <div style="font-weight:600;font-size:0.9rem;">${b.username}</div>
          <div style="font-size:0.75rem;color:var(--text-muted);">${b.time}</div>
        </div>
      </div>
      <div style="text-align:right;">
        <div style="font-weight:700;color:${b.is_winning ? 'var(--gold)' : 'var(--text)'};">${b.amount_fmt}</div>
        ${b.is_winning ? '<div style="font-size:0.72rem;color:var(--gold);">👑 Leading</div>' : ''}
      </div>
    </div>
  `).join('');
}

// ─── Bid Form Submission ──────────────────────────────────────
const bidForm   = document.getElementById('bid-form');
const bidBtn    = document.getElementById('bid-btn');
const bidStatus = document.getElementById('bid-status');

if (bidForm) {
  bidForm.addEventListener('submit', async e => {
    e.preventDefault();
    bidBtn.disabled  = true;
    bidBtn.textContent = 'Placing...';
    bidStatus.style.display = 'none';

    const fd = new FormData(bidForm);
    try {
      const res  = await fetch(BID_URL, { method:'POST', body: fd });
      const data = await res.json();

      bidStatus.style.display = 'block';
      if (data.success) {
        bidStatus.className  = 'alert alert-success';
        bidStatus.textContent = '✅ ' + data.message;
        // Update UI immediately
        document.getElementById('current-price').textContent = data.new_price_fmt;
        document.getElementById('total-bids').textContent    = data.total_bids;
        document.getElementById('bid-amount').value          = data.min_next_bid.toFixed(2);
        document.getElementById('bid-amount').min            = data.min_next_bid;
        if (data.bid_history) refreshBidHistory(data.bid_history, data.total_bids);
      } else {
        bidStatus.className  = 'alert alert-error';
        bidStatus.textContent = '❌ ' + data.message;
        if (data.flagged) bidStatus.textContent += ' ⚠️ Activity flagged.';
      }
    } catch {
      bidStatus.style.display  = 'block';
      bidStatus.className      = 'alert alert-error';
      bidStatus.textContent    = '❌ Network error. Please retry.';
    } finally {
      bidBtn.disabled  = false;
      bidBtn.textContent = '⚡ Place Bid';
    }
  });
}
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
