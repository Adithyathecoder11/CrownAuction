<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/TransactionModel.php';

requireRole('buyer');
$user = getCurrentUser();

$txns = TransactionModel::getUserTransactions($user['id']);

$pageTitle = 'My Transactions';
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
  <h1>My Transactions</h1>
  <p>Auctions you have won — contact seller and complete payment</p>
</div>

<?php if (empty($txns)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <div style="font-size:3rem;margin-bottom:16px;">📦</div>
  <h3 style="color:var(--text-muted);">No transactions yet</h3>
  <p style="color:var(--text-muted);margin-top:8px;">Win an auction to see your transactions here.</p>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Auction</th>
          <th>Amount Paid</th>
          <th>Payment</th>
          <th>Delivery</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($txns as $t): ?>
        <tr>
          <td>
            <div style="font-weight:600;"><?= htmlspecialchars($t['title']) ?></div>
            <div style="font-size:0.78rem;color:var(--text-muted);">with <?= htmlspecialchars($t['other_username']) ?></div>
          </td>
          <td style="font-weight:700;color:var(--gold);"><?= formatINR($t['final_amount']) ?></td>
          <td><span class="badge badge-<?= $t['payment_status'] === 'paid' ? 'active' : 'pending' ?>"><?= ucfirst($t['payment_status']) ?></span></td>
          <td><span class="badge badge-<?= $t['delivery_status'] === 'completed' ? 'active' : 'pending' ?>"><?= ucfirst($t['delivery_status']) ?></span></td>
          <td style="font-size:0.83rem;color:var(--text-muted);"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
          <td><a href="transaction_detail.php?id=<?= $t['id'] ?>" class="btn btn-gold btn-sm">Open →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../shared/footer.php'; ?>
