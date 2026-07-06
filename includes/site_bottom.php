</div><!-- /.wrap -->

<nav class="nav-bawah">
  <?php foreach ($navLinks as $key => [$href, $icon, $label]): ?>
    <a href="<?= $href ?>" class="<?= $activeNav === $key ? 'aktif' : '' ?>">
      <i class="bi <?= $icon ?><?= $activeNav === $key ? '-fill' : '' ?>"></i>
      <?= $label ?>
      <?php if ($key === 'keranjang'): ?>
        <span class="badge-keranjang" id="badgeKrj" <?= $jmlKrj ? '' : 'style="display:none"' ?>><?= $jmlKrj ?: '' ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="toast-lk" id="toastKedai"></div>

<script>
function tampilkanToast(pesan) {
  const t = document.getElementById('toastKedai');
  t.textContent = pesan;
  t.classList.add('tampil');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('tampil'), 1800);
}
function perbaruiBadge(jumlah) {
  for (const id of ['badgeKrj', 'badgeKrjTop']) {
    const b = document.getElementById(id);
    if (!b) continue;
    b.textContent = jumlah || '';
    b.style.display = jumlah ? '' : 'none';
  }
}
// Tombol + di kartu menu (AJAX)
document.addEventListener('click', async (ev) => {
  const btn = ev.target.closest('[data-tambah]');
  if (!btn) return;
  ev.preventDefault();
  const res = await fetch('keranjang.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'fetch' },
    body: 'aksi=tambah&menu_id=' + btn.dataset.tambah
  });
  const data = await res.json();
  if (data.ok) { perbaruiBadge(data.jumlah); tampilkanToast(data.pesan); }
});
</script>
</body>
</html>
