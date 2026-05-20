<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/AuctionController.php';

requireRole('seller');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AuctionController::handleCreate();
}

$categories = Database::query("SELECT name FROM categories ORDER BY name")->fetchAll();
$csrf = csrfToken();
$pageTitle = 'Create Auction';
include __DIR__ . '/../shared/header.php';
?>

<style>
.create-layout { display:grid;grid-template-columns:1fr 340px;gap:28px;align-items:start; }
.duration-btn { background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text-muted);font-family:'DM Sans',sans-serif;font-size:0.82rem;padding:8px 14px;cursor:pointer;transition:all 0.2s; }
.duration-btn:hover,.duration-btn.selected { border-color:var(--gold);color:var(--gold);background:rgba(201,168,76,0.1); }
.preview-box { background:var(--surface);border:2px dashed var(--border);border-radius:12px;height:220px;display:flex;flex-direction:column;align-items:center;justify-content:center;cursor:pointer;transition:all 0.2s; }
.preview-box:hover { border-color:var(--gold); }
#img-preview { display:none;width:100%;height:220px;object-fit:cover;border-radius:10px; }
@media(max-width:900px){.create-layout{grid-template-columns:1fr;}}
</style>

<div style="margin-bottom:20px;">
  <a href="dashboard.php" style="color:var(--gold);text-decoration:none;font-size:0.85rem;">← Back to Dashboard</a>
</div>

<div class="page-header">
  <h1>Create New Auction</h1>
  <p>List your item for bidding. Auctions run for 30 minutes to 6 hours.</p>
</div>

<form method="POST" action="" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $csrf ?>">

<div class="create-layout">
  <!-- Left: Form -->
  <div>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><span class="card-title">📝 Item Information</span></div>
      <div class="card-body">
        <div class="form-group">
          <label>Auction Title *</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. Vintage Rolex Submariner 1969" required maxlength="200">
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="5" style="resize:vertical;" placeholder="Describe your item in detail — condition, history, features..."></textarea>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category" class="form-control">
            <option value="">Select Category</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['name'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:20px;">
      <div class="card-header"><span class="card-title">💰 Pricing Settings</span></div>
      <div class="card-body">
        <div class="grid-2" style="gap:16px;">
          <div class="form-group" style="margin-bottom:0;">
            <label>Starting Price (₹) *</label>
            <input type="number" name="starting_price" class="form-control" placeholder="500.00" min="1" step="0.01" required>
          </div>
          <div class="form-group" style="margin-bottom:0;">
            <label>Min Bid Increment (₹) *</label>
            <input type="number" name="min_bid_increment" class="form-control" placeholder="100.00" min="1" step="0.01" required value="100">
          </div>
        </div>
        <div style="margin-top:12px;padding:12px;background:rgba(201,168,76,0.07);border:1px solid rgba(201,168,76,0.15);border-radius:10px;font-size:0.82rem;color:var(--text-muted);">
          💡 Tip: A lower starting price attracts more bidders. The minimum increment prevents micro-bids.
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">⏱ Auction Duration</span></div>
      <div class="card-body">
        <div class="form-group">
          <label>Duration (30 min – 6 hours)</label>
          <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px;">
            <?php
            $durations = [
              30 => '30 min', 60 => '1 hour', 90 => '1.5 hrs', 120 => '2 hours',
              180 => '3 hours', 240 => '4 hours', 300 => '5 hours', 360 => '6 hours'
            ];
            foreach ($durations as $mins => $label): ?>
              <button type="button" class="duration-btn" onclick="setDuration(<?= $mins ?>, this)"><?= $label ?></button>
            <?php endforeach; ?>
          </div>
          <input type="number" name="duration_minutes" id="duration-input" class="form-control"
            min="<?= MIN_AUCTION_DURATION ?>" max="<?= MAX_AUCTION_DURATION ?>"
            placeholder="Enter minutes (30–360)" required>
        </div>
        <div style="padding:12px;background:rgba(224,85,85,0.08);border:1px solid rgba(224,85,85,0.2);border-radius:10px;font-size:0.82rem;color:#F48C8C;">
          ⚠️ Duration is strictly 30–360 minutes. Once live, the auction cannot be extended.
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Image + Preview -->
  <div>
    <div class="card" style="margin-bottom:20px;position:sticky;top:88px;">
      <div class="card-header"><span class="card-title">🖼 Item Image</span></div>
      <div class="card-body">
        <div class="preview-box" onclick="document.getElementById('img-file').click();" id="preview-box">
          <img id="img-preview" alt="Preview">
          <div id="upload-placeholder" style="text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:10px;">📷</div>
            <div style="font-weight:600;margin-bottom:4px;">Click to upload image</div>
            <div style="font-size:0.78rem;color:var(--text-muted);">JPG, PNG, GIF, WEBP — Max 5MB</div>
          </div>
        </div>
        <input type="file" name="image" id="img-file" accept="image/*" style="display:none;" onchange="previewImage(this)">
        <button type="button" onclick="document.getElementById('img-file').click();" class="btn btn-outline" style="width:100%;justify-content:center;margin-top:12px;">
          Choose File
        </button>
      </div>
    </div>

    <!-- Summary Preview -->
    <div class="card">
      <div class="card-header"><span class="card-title">👁 Listing Preview</span></div>
      <div class="card-body">
        <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
          <div style="background:var(--surface);padding:12px;border-bottom:1px solid var(--border);">
            <div style="font-weight:600;font-size:0.9rem;" id="prev-title">Your auction title</div>
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">by you</div>
          </div>
          <div style="padding:14px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
              <div>
                <div style="font-size:0.72rem;color:var(--text-muted);">Starting Price</div>
                <div style="font-weight:700;color:var(--gold);" id="prev-price">₹0.00</div>
              </div>
              <div style="text-align:right;">
                <div style="font-size:0.72rem;color:var(--text-muted);">Duration</div>
                <div style="font-weight:600;" id="prev-duration">—</div>
              </div>
            </div>
            <div style="background:rgba(76,175,128,0.1);border-radius:6px;padding:6px 10px;font-size:0.78rem;color:#6EC99D;text-align:center;">
              🔴 Goes live immediately upon creation
            </div>
          </div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn btn-gold" style="width:100%;justify-content:center;font-size:1rem;padding:16px;margin-top:16px;">
      🚀 Launch Auction
    </button>
  </div>
</div>
</form>

<script>
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('img-preview');
      const ph  = document.getElementById('upload-placeholder');
      img.src = e.target.result;
      img.style.display = 'block';
      ph.style.display  = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function setDuration(mins, btn) {
  document.querySelectorAll('.duration-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('duration-input').value = mins;
  updatePreview();
}

function updatePreview() {
  const title    = document.querySelector('[name=title]').value || 'Your auction title';
  const price    = parseFloat(document.querySelector('[name=starting_price]').value) || 0;
  const duration = parseInt(document.getElementById('duration-input').value) || 0;

  document.getElementById('prev-title').textContent = title;
  document.getElementById('prev-price').textContent = '₹' + price.toLocaleString('en-IN', { minimumFractionDigits: 2 });

  if (duration) {
    const h = Math.floor(duration / 60);
    const m = duration % 60;
    document.getElementById('prev-duration').textContent = h > 0 ? `${h}h ${m > 0 ? m + 'm' : ''}` : `${m}m`;
  }
}

document.querySelector('[name=title]').addEventListener('input', updatePreview);
document.querySelector('[name=starting_price]').addEventListener('input', updatePreview);
document.getElementById('duration-input').addEventListener('input', updatePreview);
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
