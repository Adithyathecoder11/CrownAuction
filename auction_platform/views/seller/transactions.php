<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/TransactionModel.php';

requireRole('seller');
$user = getCurrentUser();

$txns = TransactionModel::getUserTransactions($user['id']);

$pageTitle = 'My Transactions';
include __DIR__ . '/../shared/header.php';
?>

<div class="page-header">
  <h1>My Transactions</h1>
  <p>Completed auctions — contact buyers and manage deliveries</p>
</div>

<?php if (empty($txns)): ?>
<div class="card" style="text-align:center;padding:60px;">
  <div style="font-size:3rem;margin-bottom:16px;">💼</div>
  <h3 style="color:var(--text-muted);">No transactions yet</h3>
  <p style="color:var(--text-muted);margin-top:8px;">When your auctions end with a winner, transactions appear here.</p>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>Auction</th>
          <th>Buyer</th>
          <th>Amount</th>
          <th>Payment</th>
          <th>Delivery</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($txns as $t): ?>
        <tr>
          <td style="font-weight:600;"><?= htmlspecialchars($t['title']) ?></td>
          <td style="color:var(--text-muted);">@<?= htmlspecialchars($t['other_username']) ?></td>
          <td style="font-weight:700;color:var(--gold);"><?= formatINR($t['final_amount']) ?></td>
          <td><span class="badge badge-<?= $t['payment_status'] === 'paid' ? 'active' : 'pending' ?>"><?= ucfirst($t['payment_status']) ?></span></td>
          <td><span class="badge badge-<?= $t['delivery_status'] === 'completed' ? 'active' : 'pending' ?>"><?= ucfirst($t['delivery_status']) ?></span></td>
          <td style="font-size:0.83rem;color:var(--text-muted);"><?= date('d M Y', strtotime($t['created_at'])) ?></td>
          <td><a href="../buyer/transaction_detail.php?id=<?= $t['id'] ?>" class="btn btn-gold btn-sm">Open →</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../shared/footer.php'; ?>
