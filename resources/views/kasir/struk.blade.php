<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Struk <?= e($pesanan['nomor_pesanan']) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Courier New', monospace; font-size: 12.5px; color: #111; margin: 0; padding: 14px; background: #f2f2f2; }
  .struk { max-width: 300px; margin: 0 auto; background: #fff; padding: 16px; }
  .center { text-align: center; }
  .toko-nama { font-weight: 700; font-size: 15px; }
  .garis { border-top: 1px dashed #666; margin: 8px 0; }
  .baris { display: flex; justify-content: space-between; gap: 8px; }
  .item-nama { font-weight: 700; }
  .item-opsi { font-size: 11px; color: #555; padding-left: 8px; }
  .total { font-weight: 700; font-size: 14px; }
  .no-print { text-align: center; margin: 16px auto; max-width: 300px; }
  .no-print button { font-family: inherit; padding: 8px 18px; border-radius: 8px; border: 1px solid #333; background: #222; color: #fff; cursor: pointer; }
  @media print {
    body { background: #fff; padding: 0; }
    .struk { max-width: none; padding: 0; }
    .no-print { display: none; }
    @page { margin: 6mm; }
  }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">🖨 Cetak Struk</button></div>
<div class="struk">
  <div class="center">
    <div class="toko-nama"><?= e($namaToko) ?></div>
    <?php if (!empty($pengaturan['alamat'])): ?><div><?= e($pengaturan['alamat']) ?></div><?php endif; ?>
    <?php if (!empty($pengaturan['whatsapp'])): ?><div><?= e($pengaturan['whatsapp']) ?></div><?php endif; ?>
  </div>
  <div class="garis"></div>
  <div class="center" style="font-size:20px;font-weight:700;margin:4px 0">ANTRIAN #<?= no_antrian($pesanan['nomor_pesanan']) ?></div>
  <div class="garis"></div>
  <div class="baris"><span><?= e($pesanan['nomor_pesanan']) ?></span><span><?= tanggal_id($pesanan['created_at'], true) ?></span></div>
  <div class="baris"><span>Pelanggan</span><span><?= e($pesanan['pelanggan']) ?></span></div>
  <?php if ($pesanan['nomor_meja']): ?><div class="baris"><span>Meja</span><span><?= e($pesanan['nomor_meja']) ?></span></div><?php endif; ?>
  <?php if ($pesanan['no_hp']): ?><div class="baris"><span>No. HP</span><span><?= e($pesanan['no_hp']) ?></span></div><?php endif; ?>
  <div class="garis"></div>
  <?php foreach ($item as $it): ?>
    <div class="item-nama"><?= (int) $it['jumlah'] ?>x <?= e($it['menu']) ?></div>
    <?php if ($it['opsi']): ?><div class="item-opsi"><?= e($it['opsi']) ?></div><?php endif; ?>
    <div class="baris"><span></span><span><?= rupiah($it['harga'] * $it['jumlah']) ?></span></div>
  <?php endforeach; ?>
  <div class="garis"></div>
  <div class="baris total"><span>TOTAL</span><span><?= rupiah($pesanan['total']) ?></span></div>
  <?php if ($bayar): ?>
    <div class="baris"><span>Metode</span><span class="text-uppercase"><?= strtoupper(e($bayar['metode'])) ?></span></div>
    <div class="baris"><span>Status Bayar</span><span><?= e(label_status_bayar($bayar['status'])) ?></span></div>
  <?php endif; ?>
  <?php if ($pesanan['catatan']): ?>
    <div class="garis"></div>
    <div>Catatan: <?= e($pesanan['catatan']) ?></div>
  <?php endif; ?>
  <?php if (!empty($pengaturan['wifi_ssid'])): ?>
    <div class="garis"></div>
    <div class="center">
      <div>WiFi: <b><?= e($pengaturan['wifi_ssid']) ?></b></div>
      <?php if (!empty($pengaturan['wifi_password'])): ?>
        <div>Pass: <b><?= e($pengaturan['wifi_password']) ?></b></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="garis"></div>
  <div class="center">Terima kasih sudah mampir ☕</div>
</div>
</body>
</html>
