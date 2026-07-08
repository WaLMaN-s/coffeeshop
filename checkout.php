<?php
require_once __DIR__ . '/includes/site_init.php';

if (!meja_aktif()) {
    header('Location: meja.php');
    exit;
}

$item  = isi_keranjang($db);
$total = array_sum(array_column($item, 'subtotal'));

if (!$item) {
    header('Location: keranjang.php');
    exit;
}

/* ---------- Buat pesanan ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metode  = $_POST['metode'] === 'qris' ? 'qris' : 'cash';
    $catatan = trim($_POST['catatan'] ?? '');

    $db->beginTransaction();
    try {
        $nomor = buat_nomor_pesanan($db);
        $db->prepare('INSERT INTO pesanan (nomor_pesanan, meja_id, nama_tamu, sesi_kode, total, status, catatan) VALUES (?,?,?,?,?,?,?)')
           ->execute([
               $nomor,
               $_SESSION['meja']['meja_id'],
               $_SESSION['meja']['nama'],
               $_SESSION['meja']['sesi'],
               $total, 'menunggu', $catatan ?: null,
           ]);
        $pesananId = (int) $db->lastInsertId();

        $stmtItem = $db->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, opsi, jumlah, harga) VALUES (?,?,?,?,?)');
        foreach ($item as $it) {
            $stmtItem->execute([$pesananId, $it['menu_id'], $it['opsi_label'] ?: null, $it['jumlah'], $it['harga_satuan']]);
        }

        $db->prepare('INSERT INTO pembayaran (pesanan_id, metode, jumlah, status) VALUES (?,?,?,?)')
           ->execute([$pesananId, $metode, $total, 'belum_dibayar']);

        tambah_notifikasi($db, 'pesanan_baru', 'Pesanan baru ' . $nomor . ' dari Meja ' . $_SESSION['meja']['nomor_meja'] . ' (' . $_SESSION['meja']['nama'] . ').', $pesananId);
        $db->commit();

        $_SESSION['keranjang'] = [];
        set_flash('sukses', 'Pesanan ' . $nomor . ' berhasil dibuat!');
        header('Location: pesanan_lihat.php?id=' . $pesananId);
        exit;
    } catch (Throwable $e) {
        $db->rollBack();
        set_flash('gagal', 'Pesanan gagal dibuat, coba lagi.');
        header('Location: checkout.php');
        exit;
    }
}

$pageTitle = 'Checkout';
$activeNav = 'keranjang';
require __DIR__ . '/includes/site_top.php';
?>

<div class="judul-bagian" style="margin-top:18px">Checkout</div>

<form method="post">
  <div class="kartu">
    <div style="font-weight:700;margin-bottom:10px">Ringkasan Pesanan</div>
    <?php foreach ($item as $it): ?>
      <div style="display:flex;justify-content:space-between;padding:7px 0;font-size:13.5px;border-bottom:1px solid var(--border)">
        <span>
          <?= $it['jumlah'] ?>× <?= e($it['nama']) ?>
          <?php if ($it['opsi_label']): ?>
            <span style="display:block;font-size:11.5px;color:var(--ink-muted)"><?= e($it['opsi_label']) ?></span>
          <?php endif; ?>
        </span>
        <span style="font-weight:600"><?= rupiah($it['subtotal']) ?></span>
      </div>
    <?php endforeach; ?>
    <div style="display:flex;justify-content:space-between;padding-top:12px;font-weight:800;font-size:15.5px">
      <span>Total</span><span><?= rupiah($total) ?></span>
    </div>
  </div>

  <div class="kartu">
    <div style="font-weight:700;margin-bottom:12px">Metode Pembayaran</div>
    <div class="pilih-metode">
      <label>
        <input type="radio" name="metode" value="cash" checked>
        <i class="bi bi-cash-coin" style="font-size:22px"></i>
        Cash
        <small>Bayar di kasir saat ambil</small>
      </label>
      <label>
        <input type="radio" name="metode" value="qris">
        <i class="bi bi-qr-code-scan" style="font-size:22px"></i>
        QRIS
        <small>Scan QR di kasir</small>
      </label>
    </div>
  </div>

  <div class="kartu">
    <div class="form-grup" style="margin:0">
      <label>Catatan (opsional)</label>
      <textarea name="catatan" class="input" rows="2" placeholder="Contoh: es sedikit, tanpa gula…"></textarea>
    </div>
  </div>

  <div style="margin-top:16px;display:flex;gap:10px">
    <a href="keranjang.php" class="btn-garis"><i class="bi bi-arrow-left"></i></a>
    <button type="submit" class="btn-utama btn-blok"><i class="bi bi-check2-circle"></i> Buat Pesanan</button>
  </div>
</form>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
