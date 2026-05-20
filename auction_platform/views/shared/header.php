<?php
// views/shared/header.php
// Usage: include with $pageTitle set

if (!isset($pageTitle)) $pageTitle = APP_NAME;
$user  = getCurrentUser();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
  --gold: #C9A84C;
  --gold-light: #E8C97A;
  --gold-dark: #9A7535;
  --dark: #0D0D0D;
  --dark-2: #141414;
  --dark-3: #1E1E1E;
  --surface: #252525;
  --border: rgba(255,255,255,0.08);
  --text: #F0EDE6;
  --text-muted: #8A8480;
  --danger: #E05555;
  --success: #4CAF80;
  --warning: #E0A020;
  --radius: 12px;
  --shadow: 0 4px 24px rgba(0,0,0,0.4);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--dark);
  color: var(--text);
  min-height: 100vh;
}

/* NAVBAR */
.navbar {
  background: var(--dark-2);
  border-bottom: 1px solid rgba(201,168,76,0.15);
  position: sticky; top: 0; z-index: 100;
  height: 64px;
}

.nav-inner {
  max-width: 1280px;
  margin: 0 auto;
  height: 100%;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.nav-logo {
  font-family: 'Playfair Display', serif;
  font-size: 1.4rem;
  font-weight: 900;
  color: var(--gold);
  text-decoration: none;
}
.nav-logo span { color: var(--text); }

.nav-links { display: flex; align-items: center; gap: 8px; }

.nav-link {
  color: var(--text-muted);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 8px 14px;
  border-radius: 8px;
  transition: all 0.2s;
}
.nav-link:hover, .nav-link.active { color: var(--text); background: var(--surface); }

.nav-user {
  display: flex; align-items: center; gap: 12px;
}

.user-chip {
  background: var(--dark-3);
  border: 1px solid var(--border);
  border-radius: 100px;
  padding: 6px 14px 6px 10px;
  display: flex; align-items: center; gap: 8px;
  font-size: 0.85rem;
}

.user-chip .avatar {
  width: 26px; height: 26px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700; color: var(--dark);
}

.role-badge {
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 100px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.role-buyer  { background: rgba(76,175,128,0.2); color: #6EC99D; }
.role-seller { background: rgba(201,168,76,0.2); color: var(--gold); }
.role-admin  { background: rgba(224,85,85,0.2);  color: #F48C8C; }

.btn-logout {
  background: none;
  border: 1px solid var(--border);
  border-radius: 8px;
  color: var(--text-muted);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.85rem;
  padding: 6px 12px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
}
.btn-logout:hover { border-color: var(--danger); color: var(--danger); }

/* MAIN LAYOUT */
.page-wrapper {
  max-width: 1280px;
  margin: 0 auto;
  padding: 32px 24px;
}

/* PAGE HEADER */
.page-header {
  margin-bottom: 32px;
}
.page-header h1 {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 6px;
}
.page-header p { color: var(--text-muted); font-size: 0.95rem; }

/* CARDS */
.card {
  background: var(--dark-3);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
}

.card-body { padding: 24px; }
.card-header {
  padding: 16px 24px;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.card-title { font-weight: 600; font-size: 1rem; }

/* BUTTONS */
.btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 10px 20px;
  border-radius: 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: all 0.2s;
  text-decoration: none;
}

.btn-gold {
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--dark);
}
.btn-gold:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(201,168,76,0.25); }

.btn-outline {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-muted);
}
.btn-outline:hover { border-color: var(--gold); color: var(--gold); }

.btn-danger {
  background: rgba(224,85,85,0.15);
  border: 1px solid rgba(224,85,85,0.3);
  color: #F48C8C;
}
.btn-danger:hover { background: rgba(224,85,85,0.25); }

.btn-sm { padding: 7px 14px; font-size: 0.82rem; border-radius: 8px; }

/* FORM ELEMENTS */
.form-group { margin-bottom: 20px; }
.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 8px;
}
.form-control {
  width: 100%;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem;
  padding: 12px 16px;
  transition: all 0.2s;
  outline: none;
}
.form-control:focus { border-color: var(--gold); background: rgba(201,168,76,0.04); }
.form-control::placeholder { color: var(--text-muted); }

select.form-control option { background: var(--dark-3); }

/* ALERTS */
.alert {
  padding: 14px 18px;
  border-radius: 10px;
  font-size: 0.9rem;
  margin-bottom: 20px;
  font-weight: 500;
}
.alert-error   { background: rgba(224,85,85,0.12); border: 1px solid rgba(224,85,85,0.25); color: #F48C8C; }
.alert-success { background: rgba(76,175,128,0.12); border: 1px solid rgba(76,175,128,0.25); color: #6EC99D; }
.alert-warning { background: rgba(224,160,32,0.12); border: 1px solid rgba(224,160,32,0.25); color: #E0C060; }

/* TABLES */
.table-wrapper { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px 16px; text-align: left; font-size: 0.88rem; }
th { color: var(--text-muted); font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.6px; border-bottom: 1px solid var(--border); }
td { border-bottom: 1px solid rgba(255,255,255,0.04); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,255,255,0.02); }

/* STATUS BADGES */
.badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 100px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.badge-active   { background: rgba(76,175,128,0.2);  color: #6EC99D; }
.badge-ended    { background: rgba(138,132,128,0.2);  color: var(--text-muted); }
.badge-cancelled{ background: rgba(224,85,85,0.2);   color: #F48C8C; }
.badge-sold     { background: rgba(201,168,76,0.2);  color: var(--gold); }
.badge-pending  { background: rgba(224,160,32,0.2);  color: #E0C060; }
.badge-flagged  { background: rgba(224,85,85,0.2);   color: #F48C8C; }

/* GRID */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
.grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 20px; }

/* STAT CARDS */
.stat-card {
  background: var(--dark-3);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px 24px;
}
.stat-card .stat-label {
  font-size: 0.78rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
  font-weight: 600;
  margin-bottom: 8px;
}
.stat-card .stat-value {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--gold);
}
.stat-card .stat-sub { font-size: 0.82rem; color: var(--text-muted); margin-top: 4px; }

/* PAGINATION */
.pagination { display: flex; gap: 8px; justify-content: center; margin-top: 32px; }
.page-btn {
  padding: 8px 14px;
  border-radius: 8px;
  border: 1px solid var(--border);
  background: var(--dark-3);
  color: var(--text-muted);
  font-size: 0.88rem;
  cursor: pointer;
  text-decoration: none;
  transition: all 0.2s;
}
.page-btn:hover, .page-btn.current { border-color: var(--gold); color: var(--gold); }

@media (max-width: 768px) {
  .grid-3, .grid-4 { grid-template-columns: 1fr 1fr; }
  .grid-2 { grid-template-columns: 1fr; }
  .page-wrapper { padding: 20px 16px; }
}
</style>
</head>
<body>

<nav class="navbar">
  <div class="nav-inner">
    <a href="<?= APP_URL ?>" class="nav-logo">👑 Crown<span>Auction</span></a>

    <div class="nav-links">
      <?php if ($user): ?>
        <?php if ($user['role'] === 'buyer'): ?>
          <a href="<?= APP_URL ?>/views/buyer/browse.php" class="nav-link">Browse Auctions</a>
          <a href="<?= APP_URL ?>/views/buyer/my_bids.php" class="nav-link">My Bids</a>
          <a href="<?= APP_URL ?>/views/buyer/transactions.php" class="nav-link">Transactions</a>
        <?php elseif ($user['role'] === 'seller'): ?>
          <a href="<?= APP_URL ?>/views/seller/dashboard.php" class="nav-link">Dashboard</a>
          <a href="<?= APP_URL ?>/views/seller/create_auction.php" class="nav-link">+ New Auction</a>
          <a href="<?= APP_URL ?>/views/seller/my_auctions.php" class="nav-link">My Auctions</a>
          <a href="<?= APP_URL ?>/views/seller/transactions.php" class="nav-link">Transactions</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <div class="nav-user">
      <?php if ($user): ?>
        <div class="user-chip">
          <div class="avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
          <span><?= htmlspecialchars($user['username']) ?></span>
          <span class="role-badge role-<?= $user['role'] ?>"><?= $user['role'] ?></span>
        </div>
        <a href="<?= APP_URL ?>/logout.php" class="btn-logout">Logout</a>
      <?php else: ?>
        <a href="<?= APP_URL ?>/index.php" class="btn-logout">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="page-wrapper">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['message']) ?></div>
<?php endif; ?>
