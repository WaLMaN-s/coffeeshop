<?php
require_once __DIR__ . '/includes/init.php';

$daftar = $db->query("SELECT * FROM meja WHERE status='aktif' ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja")->fetchAll();

$pageTitle = 'Cetak QR Meja';
$active    = 'meja';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
  <a href="meja.php" class="fw-semibold" style="color:var(--primary)"><i class="bi bi-arrow-left"></i> Kembali</a>
  <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Cetak / Simpan PDF</button>
</div>

<div class="grid-cetak">
  <?php foreach ($daftar as $m):
    $file    = dirname(__DIR__) . '/uploads/qrcode/meja-' . $m['id'] . '.png';
    $adaFile = is_file($file);
    $url     = url_meja($m['kode']);
  ?>
    <div class="kartu-meja" data-id="<?= $m['id'] ?>" data-url="<?= e($url) ?>">
      <div class="km-toko"><?= e($namaToko) ?></div>
      <img class="km-qr" width="220" height="220" src="<?= $adaFile ? '../uploads/qrcode/meja-' . $m['id'] . '.png' : '' ?>" alt="QR Meja <?= e($m['nomor_meja']) ?>">
      <div class="km-meja">Meja <?= e($m['nomor_meja']) ?></div>
      <div class="km-ket">Scan untuk pesan dari HP kamu</div>
    </div>
  <?php endforeach; ?>
</div>

<style>
.grid-cetak {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 4px;
}
.kartu-meja {
  border: 1.5px dashed #c3c2b7; border-radius: 14px; padding: 18px 12px;
  text-align: center; background: #fff; break-inside: avoid;
}
.km-toko { font-weight: 700; font-size: 13px; color: var(--ink-muted); margin-bottom: 8px; }
.km-qr { display: block; margin: 0 auto 10px; }
.km-meja { font-weight: 800; font-size: 22px; }
.km-ket { font-size: 11.5px; color: var(--ink-muted); margin-top: 4px; }
@media print {
  .grid-cetak { grid-template-columns: repeat(2, 1fr); }
  .kartu-meja { border-color: #999; }
}
</style>

<script src="../assets/js/qrcode.min.js"></script>
<script>
document.querySelectorAll('.kartu-meja').forEach(cell => {
  const img = cell.querySelector('.km-qr');
  if (img.getAttribute('src')) return;
  const id  = cell.dataset.id;
  const url = cell.dataset.url;
  const tmp = document.createElement('div');
  tmp.style.display = 'none';
  document.body.appendChild(tmp);
  new QRCode(tmp, { text: url, width: 300, height: 300, correctLevel: QRCode.CorrectLevel.M });
  setTimeout(() => {
    const canvas = tmp.querySelector('canvas');
    if (!canvas) return;
    const dataUrl = canvas.toDataURL('image/png');
    img.src = dataUrl;
    fetch('api/simpan_qr.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id, data: dataUrl }).toString()
    });
    tmp.remove();
  }, 60);
});
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
