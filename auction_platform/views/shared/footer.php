<?php // views/shared/footer.php ?>
</main>

<footer style="border-top: 1px solid rgba(255,255,255,0.06); padding: 24px; text-align: center; color: var(--text-muted); font-size: 0.82rem; margin-top: 60px;">
  <div style="max-width:1280px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;">
    <span>👑 <strong style="color:var(--gold)">CrownAuction</strong> — Secure Online Auctions</span>
    <span>All bids in INR ₹ &nbsp;|&nbsp; Server time: <strong id="server-time"><?= date('d M Y, h:i:s A') ?></strong></span>
    <span>Academic Project — <?= date('Y') ?></span>
  </div>
</footer>

<script>
// Live server time in footer
(function() {
  let start = new Date('<?= date('Y-m-d H:i:s') ?>');
  setInterval(() => {
    start = new Date(start.getTime() + 1000);
    const el = document.getElementById('server-time');
    if (el) el.textContent = start.toLocaleString('en-IN', {
      day:'2-digit', month:'short', year:'numeric',
      hour:'2-digit', minute:'2-digit', second:'2-digit'
    });
  }, 1000);
})();
</script>
</body>
</html>
