<?php
require_once __DIR__ . '/includes/site_init.php';

if (!meja_aktif()) {
    header('Location: meja.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $db->prepare('SELECT * FROM pesanan WHERE id = ? AND meja_id = ? AND sesi_kode = ?');
$stmt->execute([$id, $_SESSION['meja']['meja_id'], $_SESSION['meja']['sesi']]);
$pesanan = $stmt->fetch();
if (!$pesanan) {
    set_flash('gagal', 'Pesanan tidak ditemukan.');
    header('Location: pesanan_saya.php');
    exit;
}

/* ---------- Batalkan (hanya saat masih menunggu) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal') {
    if ($pesanan['status'] === 'menunggu') {
        $db->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ?")->execute([$id]);
        $db->prepare("UPDATE pembayaran SET status = 'gagal' WHERE pesanan_id = ? AND status = 'belum_dibayar'")->execute([$id]);
        tambah_notifikasi($db, 'pesanan_batal', 'Pesanan ' . $pesanan['nomor_pesanan'] . ' dibatalkan pelanggan.', $id);
        set_flash('sukses', 'Pesanan dibatalkan.');
    } else {
        set_flash('gagal', 'Pesanan sudah diproses dan tidak bisa dibatalkan.');
    }
    header('Location: pesanan_lihat.php?id=' . $id);
    exit;
}

$stmt = $db->prepare('
    SELECT pi.*, m.nama menu, m.foto FROM pesanan_item pi
    JOIN menu m ON m.id = pi.menu_id WHERE pi.pesanan_id = ?');
$stmt->execute([$id]);
$item = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM pembayaran WHERE pesanan_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$id]);
$bayar = $stmt->fetch();

/* Progres status */
$tahap    = ['menunggu' => 0, 'diproses' => 1, 'siap' => 2, 'selesai' => 3];
$posisi   = $tahap[$pesanan['status']] ?? -1;
$batal    = $pesanan['status'] === 'dibatalkan';
$labelTahap = ['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'];

$pageTitle = 'Detail Pesanan';
$activeNav = 'pesanan';
require __DIR__ . '/includes/site_top.php';
?>

<a href="pesanan_saya.php" style="display:inline-flex;align-items:center;gap:6px;margin:16px 0 10px;font-weight:700;color:var(--primary);font-size:13.5px">
  <i class="bi bi-arrow-left"></i> Semua pesanan
</a>

<div class="kartu">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
    <div>
      <div style="font-weight:800;font-size:15px"><?= e($pesanan['nomor_pesanan']) ?></div>
      <div style="font-size:12.5px;color:var(--ink-muted)"><?= tanggal_id($pesanan['created_at'], true) ?></div>
    </div>
    <?= badge_status_pesanan($pesanan['status']) ?>
  </div>

  <?php if (!$batal): ?>
  <!-- Progres -->
  <div style="display:flex;margin-top:18px">
    <?php foreach ($labelTahap as $i => $lbl): $aktif = $i <= $posisi; ?>
      <div style="flex:1;text-align:center;position:relative">
        <?php if ($i > 0): ?>
          <div style="position:absolute;top:11px;left:-50%;width:100%;height:3px;background:<?= $i <= $posisi ? 'var(--primary)' : 'var(--border)' ?>"></div>
        <?php endif; ?>
        <div style="position:relative;width:24px;height:24px;margin:0 auto;border-radius:50%;
                    background:<?= $aktif ? 'var(--primary)' : 'var(--surface)' ?>;
                    border:2.5px solid <?= $aktif ? 'var(--primary)' : 'var(--border)' ?>;
                    color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px">
          <?= $aktif ? '<i class="bi bi-check-lg"></i>' : '' ?>
        </div>
        <div style="font-size:10.5px;font-weight:600;margin-top:5px;color:<?= $aktif ? 'var(--primary-dark)' : 'var(--ink-muted)' ?>"><?= $lbl ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:6px">Item Pesanan</div>
  <?php foreach ($item as $it): ?>
    <div class="item-keranjang">
      <?php if ($it['foto']): ?>
        <img class="thumb" src="uploads/menu/<?= e($it['foto']) ?>" alt="">
      <?php else: ?>
        <span class="thumb"><i class="bi bi-cup-hot"></i></span>
      <?php endif; ?>
      <div style="flex:1">
        <div style="font-weight:700;font-size:13.5px"><?= e($it['menu']) ?></div>
        <?php if (!empty($it['opsi'])): ?>
          <div style="font-size:11.5px;color:var(--ink-muted)"><?= e($it['opsi']) ?></div>
        <?php endif; ?>
        <div style="font-size:12.5px;color:var(--ink-muted)"><?= $it['jumlah'] ?> × <?= rupiah($it['harga']) ?></div>
      </div>
      <div style="font-weight:700"><?= rupiah($it['jumlah'] * $it['harga']) ?></div>
    </div>
  <?php endforeach; ?>
  <div style="display:flex;justify-content:space-between;padding-top:12px;font-weight:800;font-size:15.5px">
    <span>Total</span><span><?= rupiah($pesanan['total']) ?></span>
  </div>
  <?php if ($pesanan['catatan']): ?>
    <div style="margin-top:10px;font-size:13px;background:var(--bg);border-radius:10px;padding:10px 12px">
      <i class="bi bi-chat-left-text"></i> <?= e($pesanan['catatan']) ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($bayar): ?>
<div class="kartu">
  <div style="font-weight:700;margin-bottom:10px">Pembayaran</div>
  <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0">
    <span style="color:var(--ink-muted)">Metode</span>
    <span style="font-weight:700;text-transform:uppercase"><?= e($bayar['metode']) ?></span>
  </div>
  <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0;align-items:center">
    <span style="color:var(--ink-muted)">Status</span>
    <?= badge_status_bayar($bayar['status']) ?>
  </div>
  <?php if ($bayar['status'] === 'belum_dibayar' && !$batal): ?>
    <div class="pesan-info" style="background:var(--primary-soft);color:var(--primary-dark);margin-bottom:0">
      <i class="bi bi-info-circle"></i>
      <?= $bayar['metode'] === 'qris'
          ? 'Scan QRIS di kasir dan sebutkan nomor pesananmu untuk menyelesaikan pembayaran.'
          : 'Bayar tunai di kasir sambil menyebutkan nomor pesananmu.' ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($pesanan['status'] === 'menunggu'): ?>
  <form method="post" style="margin-top:14px" onsubmit="return confirm('Batalkan pesanan ini?')">
    <input type="hidden" name="aksi" value="batal">
    <button class="btn-garis btn-blok" style="border-color:#d03b3b;color:#b3403f">
      <i class="bi bi-x-circle"></i> Batalkan Pesanan
    </button>
  </form>
<?php endif; ?>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
