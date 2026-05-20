<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../models/UserModel.php';
require_once __DIR__ . '/../../models/AuctionModel.php';
require_once __DIR__ . '/../../models/BidModel.php';

requireAdmin();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'ban_user')         AdminController::handleBanUser();
    if ($act === 'unban_user')       AdminController::handleUnbanUser();
    if ($act === 'override_auction') AdminController::handleOverrideAuction();
}

AuctionModel::closeExpired();

$dash     = AdminController::getDashboardData();
$users    = UserModel::getAll(100, 0);
$auctions = AuctionModel::getAdminAll();
$suspBids = BidModel::getSuspiciousBids();
$fraudFlags = BidModel::getAllFraudFlags();
$logs     = AdminController::getLogs();

$csrf = csrfToken();

// Current tab
$tab = $_GET['tab'] ?? 'overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel — CrownAuction</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --red: #E05555; --red-dim: rgba(224,85,85,0.15);
  --gold: #C9A84C; --dark: #0A0A0A; --dark-2: #111;
  --dark-3: #1A1A1A; --surface: #222; --border: rgba(255,255,255,0.07);
  --text: #F0EDE6; --text-muted: #8A8480;
  --success: #4CAF80; --warning: #E0A020;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'DM Sans', sans-serif; background: var(--dark); color: var(--text); min-height: 100vh; }

/* Admin Layout */
.admin-layout { display: flex; min-height: 100vh; }
.sidebar {
  width: 220px; background: var(--dark-2); border-right: 1px solid rgba(224,85,85,0.15);
  display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto;
}
.sidebar-logo { padding: 24px 20px; border-bottom: 1px solid var(--border); }
.sidebar-logo .title { font-family: 'Playfair Display', serif; font-size: 1.1rem; color: var(--red); }
.sidebar-logo .sub { font-size: 0.72rem; color: var(--text-muted); margin-top: 2px; text-transform: uppercase; letter-spacing: 1px; }
.nav-item {
  display: flex; align-items: center; gap: 10px;
  padding: 12px 20px; color: var(--text-muted);
  text-decoration: none; font-size: 0.88rem; font-weight: 500;
  transition: all 0.15s; border-left: 3px solid transparent;
}
.nav-item:hover { color: var(--text); background: rgba(255,255,255,0.03); }
.nav-item.active { color: var(--red); border-left-color: var(--red); background: var(--red-dim); }
.nav-item .badge-num {
  margin-left: auto; background: var(--red); color: #fff;
  font-size: 0.7rem; font-weight: 700; border-radius: 100px; padding: 1px 7px;
}
.logout-btn {
  margin-top: auto; padding: 16px 20px;
  border-top: 1px solid var(--border);
}
.btn-logout {
  width: 100%; padding: 9px; background: none;
  border: 1px solid rgba(224,85,85,0.3); border-radius: 8px;
  color: var(--red); font-family: 'DM Sans', sans-serif; font-size: 0.85rem;
  cursor: pointer; transition: all 0.2s;
}
.btn-logout:hover { background: var(--red-dim); }

/* Main Content */
.main { flex: 1; overflow-x: hidden; }
.topbar {
  background: var(--dark-2); border-bottom: 1px solid var(--border);
  padding: 16px 28px; display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 50;
}
.topbar h1 { font-size: 1.1rem; font-weight: 700; }
.topbar .time { font-size: 0.82rem; color: var(--text-muted); }
.content { padding: 28px; }

/* Cards, stats etc */
.card { background: var(--dark-3); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; }
.card-header { padding: 14px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.card-title { font-weight: 600; font-size: 0.95rem; }
.card-body { padding: 20px; }

.stat-card { background: var(--dark-3); border: 1px solid var(--border); border-radius: 12px; padding: 18px 22px; }
.stat-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; font-weight: 600; }
.stat-value { font-family: 'Playfair Display', serif; font-size: 1.9rem; font-weight: 700; color: var(--gold); }
.grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

table { width: 100%; border-collapse: collapse; }
th, td { padding: 11px 16px; text-align: left; font-size: 0.85rem; }
th { color: var(--text-muted); font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.6px; border-bottom: 1px solid var(--border); }
td { border-bottom: 1px solid rgba(255,255,255,0.03); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,0.02); }

.badge { display: inline-block; padding: 3px 9px; border-radius: 100px; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; }
.badge-active   { background: rgba(76,175,128,0.2);  color: #6EC99D; }
.badge-ended    { background: rgba(138,132,128,0.2);  color: var(--text-muted); }
.badge-seller   { background: rgba(201,168,76,0.2);  color: var(--gold); }
.badge-buyer    { background: rgba(76,175,128,0.2);  color: #6EC99D; }
.badge-admin    { background: rgba(224,85,85,0.2);   color: #F48C8C; }
.badge-banned   { background: rgba(224,85,85,0.3);   color: #F48C8C; }
.badge-high     { background: rgba(224,85,85,0.2);   color: #F48C8C; }
.badge-medium   { background: rgba(224,160,32,0.2);  color: #E0C060; }
.badge-low      { background: rgba(76,175,128,0.2);  color: #6EC99D; }
.badge-flagged  { background: rgba(224,85,85,0.2);   color: #F48C8C; }

.btn { display: inline-flex; align-items: center; padding: 7px 14px; border-radius: 8px; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
.btn-red     { background: rgba(224,85,85,0.15); border: 1px solid rgba(224,85,85,0.3); color: #F48C8C; }
.btn-red:hover { background: rgba(224,85,85,0.25); }
.btn-green   { background: rgba(76,175,128,0.15); border: 1px solid rgba(76,175,128,0.3); color: #6EC99D; }
.btn-gold    { background: linear-gradient(135deg, var(--gold), #9A7535); color: var(--dark); }
.btn-outline { background: none; border: 1px solid var(--border); color: var(--text-muted); }
.btn-outline:hover { border-color: var(--gold); color: var(--gold); }
.alert { padding: 13px 16px; border-radius: 10px; font-size: 0.87rem; margin-bottom: 18px; }
.alert-error   { background: rgba(224,85,85,0.12); border: 1px solid rgba(224,85,85,0.25); color: #F48C8C; }
.alert-success { background: rgba(76,175,128,0.12); border: 1px solid rgba(76,175,128,0.25); color: #6EC99D; }

.table-wrap { overflow-x: auto; }
.tab-content { display: none; }
.tab-content.active { display: block; }
</style>
</head>
<body>

<div class="admin-layout">
  <!-- Sidebar -->
  <div class="sidebar">
    <div class="sidebar-logo">
      <div class="title">👑 CrownAuction</div>
      <div class="sub">Admin Control Panel</div>
    </div>

    <a href="?tab=overview"   class="nav-item <?= $tab==='overview'  ?'active':'' ?>">📊 Overview</a>
    <a href="?tab=users"      class="nav-item <?= $tab==='users'     ?'active':'' ?>">👥 Users
      <?php $banned = count(array_filter($users, fn($u) => $u['is_banned'])); ?>
      <?php if ($banned): ?><span class="badge-num"><?= $banned ?></span><?php endif; ?>
    </a>
    <a href="?tab=auctions"   class="nav-item <?= $tab==='auctions'  ?'active':'' ?>">🏷 Auctions</a>
    <a href="?tab=fraud"      class="nav-item <?= $tab==='fraud'     ?'active':'' ?>">⚠ Fraud
      <?php $openFlags = count(array_filter($fraudFlags, fn($f) => !$f['is_resolved'])); ?>
      <?php if ($openFlags): ?><span class="badge-num"><?= $openFlags ?></span><?php endif; ?>
    </a>
    <a href="?tab=bids"       class="nav-item <?= $tab==='bids'      ?'active':'' ?>">🔨 Suspicious Bids</a>
    <a href="?tab=logs"       class="nav-item <?= $tab==='logs'      ?'active':'' ?>">📋 Audit Log</a>

    <div class="logout-btn">
      <button class="btn-logout" onclick="if(confirm('Log out of admin?')) window.location='<?= APP_URL ?>/admin_logout.php'">
        ← Log Out
      </button>
    </div>
  </div>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <h1>
        <?= ['overview'=>'📊 Overview','users'=>'👥 User Management','auctions'=>'🏷 Auction Management','fraud'=>'⚠ Fraud Flags','bids'=>'🔨 Suspicious Bids','logs'=>'📋 Audit Log'][$tab] ?? 'Dashboard' ?>
      </h1>
      <span class="time" id="admin-time"><?= date('d M Y, h:i:s A') ?></span>
    </div>

    <div class="content">

    <!-- ════ OVERVIEW ════ -->
    <div class="tab-content <?= $tab==='overview'?'active':'' ?>">
      <div class="grid-4" style="margin-bottom:24px;">
        <div class="stat-card">
          <div class="stat-label">Total Users</div>
          <div class="stat-value"><?= $dash['total_users'] ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Auctions</div>
          <div class="stat-value"><?= $dash['total_auctions'] ?></div>
          <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;"><?= $dash['active_auctions'] ?> active</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Bids</div>
          <div class="stat-value"><?= $dash['bid_stats']['total'] ?></div>
          <div style="font-size:0.78rem;color:#F48C8C;margin-top:4px;"><?= $dash['bid_stats']['flagged'] ?> flagged</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Open Fraud Flags</div>
          <div class="stat-value" style="color:var(--red);"><?= $dash['bid_stats']['open_flags'] ?></div>
        </div>
      </div>

      <div class="grid-2">
        <!-- Recent Users -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Registrations</span>
            <a href="?tab=users" style="font-size:0.78rem;color:var(--gold);text-decoration:none;">View all</a>
          </div>
          <table>
            <thead><tr><th>User</th><th>Role</th><th>Joined</th></tr></thead>
            <tbody>
              <?php foreach ($dash['recent_users'] as $u): ?>
              <tr>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d M', strtotime($u['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Recent Logs -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Recent Activity</span>
            <a href="?tab=logs" style="font-size:0.78rem;color:var(--gold);text-decoration:none;">Full log</a>
          </div>
          <table>
            <thead><tr><th>Action</th><th>Target</th><th>Time</th></tr></thead>
            <tbody>
              <?php foreach (array_slice($logs, 0, 6) as $l): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($l['action']) ?></td>
                <td style="color:var(--text-muted);"><?= $l['target_type'] ?> #<?= $l['target_id'] ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($l['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════ USERS ════ -->
    <div class="tab-content <?= $tab==='users'?'active':'' ?>">
      <div id="action-msg"></div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>User</th><th>Role</th><th>Fraud Score</th><th>Status</th><th>Joined</th><th>Last Login</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): if ($u['role'] === 'admin') continue; ?>
              <tr id="user-row-<?= $u['id'] ?>">
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($u['username']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($u['email']) ?></div>
                </td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                <td>
                  <span style="color:<?= $u['fraud_score'] >= 10 ? 'var(--red)' : ($u['fraud_score'] >= 5 ? 'var(--warning)' : 'var(--success)') ?>;font-weight:600;">
                    <?= $u['fraud_score'] ?>
                  </span>
                </td>
                <td>
                  <?php if ($u['is_banned']): ?>
                    <span class="badge badge-banned">Banned</span>
                  <?php else: ?>
                    <span class="badge badge-active">Active</span>
                  <?php endif; ?>
                </td>
                <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td style="font-size:0.8rem;color:var(--text-muted);">
                  <?= $u['last_login'] ? date('d M, h:i A', strtotime($u['last_login'])) : 'Never' ?>
                </td>
                <td>
                  <?php if (!$u['is_banned']): ?>
                    <button class="btn btn-red" onclick="banUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>')">Ban</button>
                  <?php else: ?>
                    <button class="btn btn-green" onclick="unbanUser(<?= $u['id'] ?>)">Unban</button>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════ AUCTIONS ════ -->
    <div class="tab-content <?= $tab==='auctions'?'active':'' ?>">
      <div id="auction-msg"></div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Title</th><th>Seller</th><th>Status</th><th>Current Price</th><th>Bids</th><th>Ends</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($auctions as $a): ?>
              <tr id="auction-row-<?= $a['id'] ?>">
                <td>
                  <div style="font-weight:600;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <?= htmlspecialchars($a['title']) ?>
                  </div>
                  <?php if ($a['is_overridden']): ?><div style="font-size:0.72rem;color:var(--red);">⚠ Overridden</div><?php endif; ?>
                </td>
                <td style="color:var(--text-muted);"><?= htmlspecialchars($a['seller_name']) ?></td>
                <td><span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span></td>
                <td style="font-weight:600;color:var(--gold);"><?= formatINR($a['current_price']) ?></td>
                <td><?= $a['total_bids'] ?></td>
                <td style="font-size:0.8rem;color:var(--text-muted);"><?= date('d M Y, h:i A', strtotime($a['end_time'])) ?></td>
                <td>
                  <button class="btn btn-outline" style="font-size:0.78rem;" onclick="overrideAuction(<?= $a['id'] ?>)">Override</button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════ FRAUD FLAGS ════ -->
    <div class="tab-content <?= $tab==='fraud'?'active':'' ?>">
      <div class="card">
        <div class="card-header">
          <span class="card-title">⚠ Fraud Flags</span>
          <span style="font-size:0.78rem;color:var(--text-muted);"><?= count($fraudFlags) ?> total flags</span>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>User</th><th>Type</th><th>Severity</th><th>Description</th><th>Fraud Score</th><th>Resolved</th><th>Time</th></tr>
            </thead>
            <tbody>
              <?php foreach ($fraudFlags as $f): ?>
              <tr>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($f['username']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($f['email']) ?></div>
                </td>
                <td><code style="font-size:0.78rem;background:var(--surface);padding:2px 7px;border-radius:5px;"><?= htmlspecialchars($f['flag_type']) ?></code></td>
                <td><span class="badge badge-<?= $f['severity'] ?>"><?= ucfirst($f['severity']) ?></span></td>
                <td style="font-size:0.82rem;color:var(--text-muted);max-width:200px;"><?= htmlspecialchars($f['description']) ?></td>
                <td style="color:<?= $f['fraud_score'] >= 10 ? 'var(--red)' : 'var(--warning)' ?>;font-weight:700;"><?= $f['fraud_score'] ?></td>
                <td><?= $f['is_resolved'] ? '<span style="color:var(--success);">✓</span>' : '<span style="color:var(--text-muted);">Open</span>' ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M, h:i', strtotime($f['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ════ SUSPICIOUS BIDS ════ -->
    <div class="tab-content <?= $tab==='bids'?'active':'' ?>">
      <div class="card">
        <div class="card-header">
          <span class="card-title">🔨 Flagged Bids</span>
          <span style="font-size:0.78rem;color:var(--text-muted);"><?= count($suspBids) ?> flagged bids</span>
        </div>
        <?php if (empty($suspBids)): ?>
          <div class="card-body" style="text-align:center;padding:40px;color:var(--text-muted);">✅ No suspicious bids detected</div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Bidder</th><th>Auction</th><th>Amount</th><th>Flag Reason</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
              <?php foreach ($suspBids as $b): ?>
              <tr>
                <td>
                  <div style="font-weight:600;"><?= htmlspecialchars($b['username']) ?></div>
                  <div style="font-size:0.75rem;color:var(--text-muted);"><?= htmlspecialchars($b['email']) ?></div>
                </td>
                <td style="font-size:0.83rem;"><?= htmlspecialchars(substr($b['auction_title'],0,35)) ?>...</td>
                <td style="color:var(--red);font-weight:700;"><?= formatINR($b['amount']) ?></td>
                <td><span class="badge badge-flagged"><?= htmlspecialchars($b['flag_reason'] ?? 'Flagged') ?></span></td>
                <td style="font-family:monospace;font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($b['ip_address']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M, h:i A', strtotime($b['bid_time'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ════ LOGS ════ -->
    <div class="tab-content <?= $tab==='logs'?'active':'' ?>">
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>Action</th><th>Target</th><th>Details</th><th>IP</th><th>Time</th></tr>
            </thead>
            <tbody>
              <?php foreach ($logs as $l): ?>
              <tr>
                <td style="font-weight:600;"><?= htmlspecialchars($l['action']) ?></td>
                <td><span class="badge badge-active"><?= $l['target_type'] ?></span> #<?= $l['target_id'] ?></td>
                <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars(substr($l['details'],0,60)) ?></td>
                <td style="font-family:monospace;font-size:0.78rem;color:var(--text-muted);"><?= htmlspecialchars($l['ip_address']) ?></td>
                <td style="font-size:0.78rem;color:var(--text-muted);"><?= date('d M Y, h:i A', strtotime($l['created_at'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    </div><!-- content -->
  </div><!-- main -->
</div><!-- admin-layout -->

<!-- Override Modal -->
<div id="override-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.7);z-index:999;align-items:center;justify-content:center;">
  <div style="background:var(--dark-3);border:1px solid var(--border);border-radius:14px;padding:28px;width:380px;">
    <h3 style="margin-bottom:18px;">⚙ Override Auction</h3>
    <div style="margin-bottom:14px;">
      <label style="display:block;font-size:0.78rem;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.8px;">New Status</label>
      <select id="override-status" style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:10px 12px;font-family:'DM Sans',sans-serif;outline:none;">
        <option value="active">Active</option>
        <option value="ended">Ended</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>
    <div style="margin-bottom:18px;">
      <label style="display:block;font-size:0.78rem;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:0.8px;">Reason / Note</label>
      <input type="text" id="override-note" style="width:100%;background:var(--surface);border:1px solid var(--border);border-radius:8px;color:var(--text);padding:10px 12px;font-family:'DM Sans',sans-serif;outline:none;" placeholder="Reason for override">
    </div>
    <div style="display:flex;gap:10px;">
      <button onclick="submitOverride()" class="btn btn-red" style="flex:1;justify-content:center;">Apply Override</button>
      <button onclick="document.getElementById('override-modal').style.display='none'" class="btn btn-outline" style="flex:1;justify-content:center;">Cancel</button>
    </div>
  </div>
</div>

<script>
// Live clock
setInterval(() => {
  const el = document.getElementById('admin-time');
  if (el) el.textContent = new Date().toLocaleString('en-IN', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit' });
}, 1000);

let currentAuctionId = null;

async function banUser(id, name) {
  const reason = prompt(`Reason for banning ${name}:`, 'Policy violation');
  if (!reason) return;
  const fd = new FormData();
  fd.append('action', 'ban_user');
  fd.append('user_id', id);
  fd.append('reason', reason);
  const res  = await fetch(window.location.href, { method: 'POST', body: fd });
  const data = await res.json();
  showMsg('action-msg', data.success, data.message);
  if (data.success) {
    const row = document.getElementById('user-row-' + id);
    if (row) row.querySelector('button').outerHTML = `<button class="btn btn-green" onclick="unbanUser(${id})">Unban</button>`;
  }
}

async function unbanUser(id) {
  const fd = new FormData();
  fd.append('action', 'unban_user');
  fd.append('user_id', id);
  const res  = await fetch(window.location.href, { method: 'POST', body: fd });
  const data = await res.json();
  showMsg('action-msg', data.success, data.message);
  if (data.success) {
    const row = document.getElementById('user-row-' + id);
    if (row) row.querySelector('button').outerHTML = `<button class="btn btn-red" onclick="banUser(${id}, 'user')">Ban</button>`;
  }
}

function overrideAuction(id) {
  currentAuctionId = id;
  document.getElementById('override-modal').style.display = 'flex';
}

async function submitOverride() {
  const status = document.getElementById('override-status').value;
  const note   = document.getElementById('override-note').value;
  const fd = new FormData();
  fd.append('action', 'override_auction');
  fd.append('auction_id', currentAuctionId);
  fd.append('status', status);
  fd.append('note', note);
  const res  = await fetch(window.location.href, { method: 'POST', body: fd });
  const data = await res.json();
  document.getElementById('override-modal').style.display = 'none';
  showMsg('auction-msg', data.success, data.message);
}

function showMsg(id, success, msg) {
  const el = document.getElementById(id);
  if (!el) return;
  el.innerHTML = `<div class="alert alert-${success?'success':'error'}">${msg}</div>`;
  setTimeout(() => el.innerHTML = '', 4000);
}
</script>
</body>
</html>
