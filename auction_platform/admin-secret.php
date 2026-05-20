<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/controllers/AdminController.php';

// Already authenticated?
if (isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated']) {
    header('Location: ' . APP_URL . '/views/admin/admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    AdminController::handleLogin();
}

$flash = getFlash();
$csrf  = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Access — CrownAuction</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
  font-family: 'DM Sans', sans-serif;
  background: #080808;
  color: #F0EDE6;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}
.bg {
  position: fixed; inset: 0;
  background: radial-gradient(ellipse 60% 40% at 50% 50%, rgba(224,85,85,0.07) 0%, transparent 70%);
}
.bg::before {
  content: '';
  position: absolute; inset: 0;
  background-image: repeating-linear-gradient(0deg, transparent, transparent 49px, rgba(255,255,255,0.02) 50px),
                    repeating-linear-gradient(90deg, transparent, transparent 49px, rgba(255,255,255,0.02) 50px);
}
.panel {
  position: relative; z-index: 1;
  width: 360px;
  background: #111;
  border: 1px solid rgba(224,85,85,0.2);
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6), 0 0 60px rgba(224,85,85,0.05);
}
.lock { font-size: 2.5rem; text-align: center; margin-bottom: 16px; filter: drop-shadow(0 0 10px rgba(224,85,85,0.4)); }
h1 { text-align: center; font-size: 1.2rem; font-weight: 700; color: #E05555; margin-bottom: 4px; }
.sub { text-align: center; font-size: 0.8rem; color: #6A6460; margin-bottom: 28px; text-transform: uppercase; letter-spacing: 1.5px; }
.form-group { margin-bottom: 18px; }
.form-group label { display: block; font-size: 0.75rem; font-weight: 600; color: #6A6460; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 7px; }
.form-group input {
  width: 100%;
  background: #0D0D0D;
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 9px;
  color: #F0EDE6;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem;
  padding: 12px 14px;
  outline: none;
  transition: all 0.2s;
  letter-spacing: 2px;
}
.form-group input:focus { border-color: rgba(224,85,85,0.5); }
.btn {
  width: 100%;
  padding: 13px;
  background: linear-gradient(135deg, #E05555, #A03030);
  color: #fff;
  border: none;
  border-radius: 9px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  margin-top: 4px;
  transition: all 0.2s;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(224,85,85,0.3); }
.alert { padding: 11px 14px; border-radius: 9px; font-size: 0.86rem; margin-bottom: 18px; background: rgba(224,85,85,0.12); border: 1px solid rgba(224,85,85,0.25); color: #F48C8C; }
.warning-note { margin-top: 20px; font-size: 0.75rem; color: #4A4440; text-align: center; line-height: 1.5; }
</style>
</head>
<body>
<div class="bg"></div>
<div class="panel">
  <div class="lock">🔐</div>
  <h1>Admin Access</h1>
  <div class="sub">Restricted Area</div>

  <?php if ($flash): ?>
    <div class="alert"><?= htmlspecialchars($flash['message']) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
    <div class="form-group">
      <label>Secret Admin Key</label>
      <input type="password" name="admin_key" placeholder="••••••••••••••••" required autocomplete="off">
    </div>
    <button type="submit" class="btn">Authenticate</button>
  </form>

  <div class="warning-note">
    This page is not linked anywhere in the application.<br>
    Unauthorized access attempts are logged.
  </div>
</div>
</body>
</html>
