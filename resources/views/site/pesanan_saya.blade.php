@include('partials.site_top')

<div class="judul-bagian" style="margin-top:18px">Pesanan Saya</div>

<?php if (!$daftar): ?>
  <div class="kosong">
    <i class="bi bi-receipt"></i>
    Belum ada pesanan.<br><br>
    <a href="index.php" class="btn-utama"><i class="bi bi-cup-hot"></i> Pesan Sekarang</a>
  </div>
<?php else: ?>
  <?php foreach ($daftar as $p): ?>
    <a href="pesanan_lihat.php?id=<?= $p['id'] ?>" class="kartu" style="display:block;margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:8px">
        <span style="font-weight:800;font-size:13.5px;color:var(--primary-dark)"><?= e($p['nomor_pesanan']) ?></span>
        <?= badge_status_pesanan($p['status']) ?>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:flex-end">
        <div style="font-size:12.5px;color:var(--ink-muted)">
          <?= (int) $p['jumlah_item'] ?> item · <?= strtoupper(e($p['metode'] ?? '-')) ?>
          · <?= $p['status_bayar'] ? label_status_bayar($p['status_bayar']) : '-' ?><br>
          <?= tanggal_id($p['created_at'], true) ?>
        </div>
        <div style="font-weight:800;font-size:15px"><?= rupiah($p['total']) ?></div>
      </div>
    </a>
  <?php endforeach; ?>
<?php endif; ?>

<script>
// Real-time: daftar ikut berubah saat kasir memproses pesanan.
(function () {
  let sidikAwal = null;
  async function cekDaftar() {
    if (document.hidden) return;
    try {
      const res  = await fetch('pesanan_status.php', { cache: 'no-store' });
      const data = await res.json();
      if (!data.ok) return;
      if (sidikAwal === null) { sidikAwal = data.sidik; return; }
      if (data.sidik !== sidikAwal) location.reload();
    } catch (e) { /* diam saat offline */ }
  }
  cekDaftar();
  setInterval(cekDaftar, 5000);
})();
</script>

@include('partials.site_bottom')
