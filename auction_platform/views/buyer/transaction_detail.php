<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../models/TransactionModel.php';

requireLogin();
$user = getCurrentUser();

$txId = (int)($_GET['id'] ?? 0);
if (!$txId) { header('Location: transactions.php'); exit; }

$tx = TransactionModel::getById($txId);
if (!$tx || ($tx['buyer_id'] != $user['id'] && $tx['seller_id'] != $user['id'])) {
    setFlash('error', 'Transaction not found or access denied.');
    header('Location: transactions.php');
    exit;
}

$messages = TransactionModel::getMessages($txId);
TransactionModel::markMessagesRead($txId, $user['id']);

$isBuyer = ($user['id'] == $tx['buyer_id']);
$other   = $isBuyer ? ['name' => $tx['seller_name'], 'username' => $tx['seller_username'], 'email' => $tx['seller_email'], 'phone' => $tx['seller_phone']]
                    : ['name' => $tx['buyer_name'],  'username' => $tx['buyer_username'],  'email' => $tx['buyer_email'],  'phone' => $tx['buyer_phone']];

$csrf = csrfToken();
$pageTitle = 'Transaction — ' . $tx['auction_title'];
include __DIR__ . '/../shared/header.php';
?>

<style>
.tx-layout { display: grid; grid-template-columns: 1fr 380px; gap: 24px; align-items: start; }
.chat-container { background: var(--dark); border-radius: 10px; height: 360px; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.msg-bubble { max-width: 75%; }
.msg-bubble.mine { align-self: flex-end; }
.msg-bubble.theirs { align-self: flex-start; }
.msg-text {
  padding: 10px 14px;
  border-radius: 14px;
  font-size: 0.9rem;
  line-height: 1.5;
}
.msg-bubble.mine .msg-text { background: linear-gradient(135deg, var(--gold-dark), var(--gold)); color: var(--dark); border-bottom-right-radius: 4px; }
.msg-bubble.theirs .msg-text { background: var(--surface); color: var(--text); border-bottom-left-radius: 4px; }
.msg-meta { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }
.msg-bubble.mine .msg-meta { text-align: right; }
@media(max-width:900px) { .tx-layout { grid-template-columns:1fr; } }
</style>

<div style="margin-bottom:20px;">
  <a href="transactions.php" style="color:var(--gold);text-decoration:none;font-size:0.85rem;">← Back to Transactions</a>
</div>

<div class="tx-layout">

  <!-- LEFT -->
  <div>
    <!-- Transaction Summary -->
    <div class="card" style="margin-bottom:20px;border-color:rgba(201,168,76,0.25);">
      <div class="card-header">
        <span class="card-title">🏆 Transaction Details</span>
        <span class="badge badge-<?= $tx['payment_status'] === 'paid' ? 'active' : 'pending' ?>"><?= ucfirst($tx['payment_status']) ?></span>
      </div>
      <div class="card-body">
        <h2 style="font-family:'Playfair Display',serif;font-size:1.4rem;margin-bottom:16px;">
          <?= htmlspecialchars($tx['auction_title']) ?>
        </h2>
        <div class="grid-2" style="gap:14px;margin-bottom:20px;">
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Final Amount</div>
            <div style="font-family:'Playfair Display',serif;font-size:1.6rem;font-weight:700;color:var(--gold);"><?= formatINR($tx['final_amount']) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Transaction Date</div>
            <div style="font-weight:600;"><?= date('d M Y, h:i A', strtotime($tx['created_at'])) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Payment Status</div>
            <div><span class="badge badge-<?= $tx['payment_status'] === 'paid' ? 'active' : 'pending' ?>"><?= ucfirst($tx['payment_status']) ?></span></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:14px;">
            <div style="font-size:0.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:4px;">Delivery Status</div>
            <div><span class="badge badge-<?= $tx['delivery_status'] === 'completed' ? 'active' : 'pending' ?>"><?= ucfirst($tx['delivery_status']) ?></span></div>
          </div>
        </div>

        <?php if (!$isBuyer): ?>
        <!-- Seller can update statuses -->
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <button onclick="updateStatus('payment','paid')" class="btn btn-gold btn-sm">✅ Mark Payment Received</button>
          <button onclick="updateStatus('delivery','shipped')" class="btn btn-outline btn-sm">📦 Mark Shipped</button>
          <button onclick="updateStatus('delivery','completed')" class="btn btn-outline btn-sm">✔ Mark Completed</button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Contact Info -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <span class="card-title">👤 <?= $isBuyer ? 'Seller' : 'Buyer' ?> Contact</span>
      </div>
      <div class="card-body">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:16px;">
          <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--gold-dark));display:flex;align-items:center;justify-content:center;font-size:1.3rem;font-weight:700;color:var(--dark);">
            <?= strtoupper(substr($other['username'], 0, 1)) ?>
          </div>
          <div>
            <div style="font-weight:700;font-size:1rem;"><?= htmlspecialchars($other['name'] ?: $other['username']) ?></div>
            <div style="font-size:0.82rem;color:var(--text-muted);">@<?= htmlspecialchars($other['username']) ?></div>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <div style="background:var(--surface);border-radius:10px;padding:12px;">
            <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:4px;">📧 Email</div>
            <div style="font-size:0.88rem;font-weight:500;"><?= htmlspecialchars($other['email']) ?></div>
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:12px;">
            <div style="font-size:0.72rem;color:var(--text-muted);margin-bottom:4px;">📱 Phone</div>
            <div style="font-size:0.88rem;font-weight:500;"><?= htmlspecialchars($other['phone'] ?: 'Not provided') ?></div>
          </div>
        </div>
        <div style="margin-top:12px;padding:12px;background:rgba(201,168,76,0.08);border:1px solid rgba(201,168,76,0.2);border-radius:10px;font-size:0.82rem;color:var(--text-muted);">
          💡 Please coordinate payment and delivery details via the message system below.
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT — MESSAGES -->
  <div>
    <div class="card" style="position:sticky;top:88px;">
      <div class="card-header">
        <span class="card-title">💬 Messages</span>
        <span style="font-size:0.78rem;color:var(--text-muted);" id="msg-count"><?= count($messages) ?> messages</span>
      </div>
      <div class="card-body" style="padding:12px;">
        <div class="chat-container" id="chat-box">
          <?php if (empty($messages)): ?>
            <div style="text-align:center;color:var(--text-muted);font-size:0.88rem;margin:auto;">
              No messages yet. Start the conversation!
            </div>
          <?php else: ?>
            <?php foreach ($messages as $m): $mine = ($m['sender_id'] == $user['id']); ?>
            <div class="msg-bubble <?= $mine ? 'mine' : 'theirs' ?>">
              <div class="msg-text"><?= nl2br(htmlspecialchars($m['message'])) ?></div>
              <div class="msg-meta"><?= $mine ? 'You' : htmlspecialchars($m['username']) ?> · <?= date('d M, h:i A', strtotime($m['sent_at'])) ?></div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div style="margin-top:12px;display:flex;gap:8px;">
          <input type="text" id="msg-input" class="form-control" placeholder="Type a message..." autocomplete="off">
          <button onclick="sendMessage()" class="btn btn-gold" style="white-space:nowrap;">Send</button>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
const TX_ID     = <?= $txId ?>;
const CSRF      = '<?= $csrf ?>';
const USER_ID   = <?= $user['id'] ?>;
const MSG_URL   = '<?= APP_URL ?>/api/messages.php';
const STATUS_URL= '<?= APP_URL ?>/api/update_status.php';
let lastMsgCount = <?= count($messages) ?>;

function scrollToBottom() {
  const box = document.getElementById('chat-box');
  box.scrollTop = box.scrollHeight;
}
scrollToBottom();

async function sendMessage() {
  const input = document.getElementById('msg-input');
  const text  = input.value.trim();
  if (!text) return;

  input.disabled = true;
  const fd = new FormData();
  fd.append('action', 'send');
  fd.append('transaction_id', TX_ID);
  fd.append('message', text);
  fd.append('csrf_token', CSRF);

  try {
    const res  = await fetch(MSG_URL + '?action=send', { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      input.value = '';
      appendMessage({ text, username:'You', time: data.sent_at, is_mine: true });
      lastMsgCount++;
      document.getElementById('msg-count').textContent = lastMsgCount + ' messages';
    }
  } catch(e) {}
  input.disabled = false;
  input.focus();
}

document.getElementById('msg-input').addEventListener('keypress', e => {
  if (e.key === 'Enter') sendMessage();
});

function appendMessage({ text, username, time, is_mine }) {
  const box = document.getElementById('chat-box');
  const div = document.createElement('div');
  div.className = 'msg-bubble ' + (is_mine ? 'mine' : 'theirs');
  div.innerHTML = `<div class="msg-text">${text.replace(/\n/g,'<br>')}</div><div class="msg-meta">${username} · ${time}</div>`;
  box.appendChild(div);
  scrollToBottom();
}

// Poll for new messages every 5 seconds
setInterval(async () => {
  try {
    const res  = await fetch(`${MSG_URL}?action=fetch&transaction_id=${TX_ID}`);
    const data = await res.json();
    if (data.success && data.messages.length > lastMsgCount) {
      lastMsgCount = data.messages.length;
      document.getElementById('msg-count').textContent = lastMsgCount + ' messages';
      const box = document.getElementById('chat-box');
      box.innerHTML = '';
      data.messages.forEach(m => appendMessage({
        text: m.text, username: m.is_mine ? 'You' : m.sender,
        time: m.time, is_mine: m.is_mine
      }));
    }
  } catch(e) {}
}, 5000);

async function updateStatus(type, value) {
  const fd = new FormData();
  fd.append('transaction_id', TX_ID);
  fd.append('type', type);
  fd.append('value', value);
  fd.append('csrf_token', CSRF);
  try {
    const res  = await fetch('<?= APP_URL ?>/api/update_status.php', { method:'POST', body: fd });
    const data = await res.json();
    if (data.success) location.reload();
  } catch(e) {}
}
</script>

<?php include __DIR__ . '/../shared/footer.php'; ?>
