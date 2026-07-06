<?php
require_once __DIR__ . '/includes/site_init.php';

if (!pelanggan_masuk()) {
    set_flash('gagal', 'Masuk dulu untuk melihat pesananmu.');
    header('Location: masuk.php?lanjut=pesanan_saya.php');
    exit;
}

$stmt = $db->prepare("
    SELECT p.*,
           (SELECT COUNT(*) FROM pesanan_item pi WHERE pi.pesanan_id = p.id) jumlah_item,
           (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar,
           (SELECT b.metode FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) metode
    FROM pesanan p WHERE p.pelanggan_id = ?
    ORDER BY p.created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['pelanggan_id']]);
$daftar = $stmt->fetchAll();

$pageTitle = 'Pesanan Saya';
$activeNav = 'pesanan';
require __DIR__ . '/includes/site_top.php';
?>

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

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
