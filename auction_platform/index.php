<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Handle form submissions
$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login']))    AuthController::handleLogin();
    if (isset($_POST['register'])) AuthController::handleRegister();
}

// Redirect if already logged in
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'seller') header('Location: ' . APP_URL . '/views/seller/dashboard.php');
    else                            header('Location: ' . APP_URL . '/views/buyer/browse.php');
    exit;
}

$flash  = getFlash();
$csrf   = csrfToken();
$showRegister = ($action === 'register');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CrownAuction — Premium Online Auctions</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --gold: #C9A84C;
  --gold-light: #E8C97A;
  --gold-dark: #9A7535;
  --dark: #0D0D0D;
  --dark-2: #1A1A1A;
  --dark-3: #252525;
  --surface: #1E1E1E;
  --text: #F0EDE6;
  --text-muted: #8A8480;
  --danger: #E05555;
  --success: #4CAF80;
  --radius: 12px;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--dark);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* Background */
.bg-pattern {
  position: fixed; inset: 0; z-index: 0;
  background: 
    radial-gradient(ellipse 80% 50% at 20% 40%, rgba(201,168,76,0.08) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 80%, rgba(201,168,76,0.05) 0%, transparent 60%),
    linear-gradient(180deg, #0D0D0D 0%, #111108 100%);
}

.bg-pattern::after {
  content: '';
  position: absolute; inset: 0;
  background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23C9A84C' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Layout */
.container { position: relative; z-index: 1; min-height: 100vh; display: flex; }

/* Left — Hero */
.hero {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 60px;
  background: linear-gradient(135deg, rgba(201,168,76,0.06), transparent);
  border-right: 1px solid rgba(201,168,76,0.1);
}

.logo {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 900;
  color: var(--gold);
  letter-spacing: -0.5px;
  margin-bottom: 60px;
  display: flex; align-items: center; gap: 10px;
}

.logo span { color: var(--text); }

.hero-tag {
  display: inline-block;
  background: rgba(201,168,76,0.15);
  border: 1px solid rgba(201,168,76,0.3);
  color: var(--gold);
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 2px;
  text-transform: uppercase;
  padding: 6px 14px;
  border-radius: 100px;
  margin-bottom: 20px;
}

.hero h1 {
  font-family: 'Playfair Display', serif;
  font-size: 3.8rem;
  font-weight: 900;
  line-height: 1.1;
  margin-bottom: 20px;
  color: var(--text);
}

.hero h1 em { color: var(--gold); font-style: normal; }

.hero p {
  font-size: 1.1rem;
  color: var(--text-muted);
  line-height: 1.7;
  max-width: 440px;
  margin-bottom: 40px;
}

.stats-row {
  display: flex;
  gap: 40px;
}

.stat { }
.stat .num {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 700;
  color: var(--gold);
}
.stat .label {
  font-size: 0.8rem;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Right — Form Panel */
.form-panel {
  width: 480px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
}

.auth-card {
  width: 100%;
  max-width: 400px;
  background: var(--surface);
  border: 1px solid rgba(201,168,76,0.15);
  border-radius: 20px;
  padding: 40px;
}

.auth-tabs {
  display: flex;
  gap: 4px;
  background: var(--dark-3);
  border-radius: 10px;
  padding: 4px;
  margin-bottom: 32px;
}

.tab {
  flex: 1;
  text-align: center;
  padding: 10px;
  font-size: 0.9rem;
  font-weight: 600;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  color: var(--text-muted);
}

.tab.active {
  background: var(--gold);
  color: var(--dark);
}

.form-group { margin-bottom: 18px; }

.form-group label {
  display: block;
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 8px;
}

.form-group input,
.form-group select {
  width: 100%;
  background: var(--dark-3);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem;
  padding: 12px 16px;
  transition: all 0.2s;
  outline: none;
}

.form-group input:focus,
.form-group select:focus {
  border-color: var(--gold);
  background: rgba(201,168,76,0.05);
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.btn-primary {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, var(--gold), var(--gold-dark));
  color: var(--dark);
  border: none;
  border-radius: 10px;
  font-family: 'DM Sans', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  letter-spacing: 0.3px;
  transition: all 0.2s;
  margin-top: 8px;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 24px rgba(201,168,76,0.3);
}

.alert {
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 0.88rem;
  margin-bottom: 20px;
  font-weight: 500;
}

.alert-error   { background: rgba(224,85,85,0.15); border: 1px solid rgba(224,85,85,0.3); color: #F48C8C; }
.alert-success { background: rgba(76,175,128,0.15); border: 1px solid rgba(76,175,128,0.3); color: #6EC99D; }

.role-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 0; }

.role-option { display: none; }
.role-label {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 14px 8px;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--text-muted);
  gap: 6px;
}

.role-label .icon { font-size: 1.3rem; }

.role-option:checked + .role-label {
  border-color: var(--gold);
  background: rgba(201,168,76,0.1);
  color: var(--gold);
}

/* Mobile */
@media (max-width: 900px) {
  .hero { display: none; }
  .form-panel { width: 100%; }
  .container { justify-content: center; }
}
</style>
</head>
<body>
<div class="bg-pattern"></div>
<div class="container">

  <!-- HERO -->
  <div class="hero">
    <div class="logo">👑 Crown <span>Auction</span></div>
    <div class="hero-tag">India's Premier Auction Platform</div>
    <h1>Bid. Win.<br><em>Own the Rare.</em></h1>
    <p>A secure, real-time auction experience where collectors, sellers and buyers come together. Every item has a story — yours starts here.</p>
    <div class="stats-row">
      <div class="stat">
        <div class="num">₹12L+</div>
        <div class="label">Total Sales</div>
      </div>
      <div class="stat">
        <div class="num">1,000+</div>
        <div class="label">Active Bids</div>
      </div>
      <div class="stat">
        <div class="num">200+</div>
        <div class="label">Happy Users</div>
      </div>
    </div>
  </div>

  <!-- FORM PANEL -->
  <div class="form-panel">
    <div class="auth-card">

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><?= sanitize($flash['message']) ?></div>
      <?php endif; ?>

      <div class="auth-tabs">
        <a href="<?= APP_URL ?>/index.php" class="tab <?= !$showRegister ? 'active' : '' ?>">Sign In</a>
        <a href="<?= APP_URL ?>/index.php?action=register" class="tab <?= $showRegister ? 'active' : '' ?>">Register</a>
      </div>

      <?php if (!$showRegister): ?>
      <!-- LOGIN -->
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required>
        </div>
        <button type="submit" name="login" class="btn-primary">Sign In to CrownAuction</button>
      </form>

      <?php else: ?>
      <!-- REGISTER -->
      <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <div class="form-row">
          <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" placeholder="bidder_pro" required>
          </div>
          <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" placeholder="Priya Sharma" required>
          </div>
        </div>
        <div class="form-group">
          <label>Email Address</label>
          <input type="email" name="email" placeholder="you@example.com" required>
        </div>
        <div class="form-group">
          <label>Phone (optional)</label>
          <input type="tel" name="phone" placeholder="+91 9876543210">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Min 8 chars" required>
          </div>
          <div class="form-group">
            <label>Confirm</label>
            <input type="password" name="confirm_password" placeholder="Repeat" required>
          </div>
        </div>
        <div class="form-group">
          <label>I want to</label>
          <div class="role-cards">
            <input type="radio" class="role-option" name="role" value="buyer" id="r-buyer" checked>
            <label for="r-buyer" class="role-label"><span class="icon">🛒</span>Buy Items</label>
            <input type="radio" class="role-option" name="role" value="seller" id="r-seller">
            <label for="r-seller" class="role-label"><span class="icon">🏷</span>Sell Items</label>
          </div>
        </div>
        <button type="submit" name="register" class="btn-primary">Create My Account</button>
      </form>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
