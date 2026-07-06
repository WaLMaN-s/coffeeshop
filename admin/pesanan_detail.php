<?php
require_once __DIR__ . '/includes/init.php';

$id = (int) ($_GET['id'] ?? 0);

/* ---------- Ubah status ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_status') {
    $baru = $_POST['status'] ?? '';
    if (in_array($baru, ['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'], true)) {
        $db->prepare('UPDATE pesanan SET status = ? WHERE id = ?')->execute([$baru, $id]);
        if ($baru === 'dibatalkan') {
            $no = $db->prepare('SELECT nomor_pesanan FROM pesanan WHERE id = ?');
            $no->execute([$id]);
            tambah_notifikasi($db, 'pesanan_batal', 'Pesanan ' . $no->fetchColumn() . ' dibatalkan.', $id);
        }
        set_flash('sukses', 'Status pesanan diubah menjadi "' . label_status_pesanan($baru) . '".');
    }
    header('Location: pesanan_detail.php?id=' . $id);
    exit;
}

/* ---------- Data ---------- */
$stmt = $db->prepare("
    SELECT p.*, pl.nama pelanggan, pl.email, pl.no_hp
    FROM pesanan p JOIN pelanggan pl ON pl.id = p.pelanggan_id
    WHERE p.id = ?");
$stmt->execute([$id]);
$pesanan = $stmt->fetch();
if (!$pesanan) {
    set_flash('gagal', 'Pesanan tidak ditemukan.');
    header('Location: pesanan.php');
    exit;
}

$stmt = $db->prepare("
    SELECT pi.*, m.nama menu, m.foto
    FROM pesanan_item pi JOIN menu m ON m.id = pi.menu_id
    WHERE pi.pesanan_id = ?");
$stmt->execute([$id]);
$item = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM pembayaran WHERE pesanan_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$id]);
$bayar = $stmt->fetch();

$pageTitle = 'Detail Pesanan';
$active    = 'pesanan';
require __DIR__ . '/includes/layout_top.php';
?>

<a href="pesanan.php" class="d-inline-flex align-items-center gap-1 mb-3 fw-semibold" style="color:var(--primary)">
  <i class="bi bi-arrow-left"></i> Kembali ke daftar pesanan
</a>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-k mb-3">
      <div class="card-head">
        <span><?= e($pesanan['nomor_pesanan']) ?></span>
        <?= badge_status_pesanan($pesanan['status']) ?>
      </div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($item as $it): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($it['foto']): ?>
                    <img src="../uploads/menu/<?= e($it['foto']) ?>" class="foto-menu" alt="">
                  <?php else: ?>
                    <span class="foto-placeholder"><i class="bi bi-cup-hot"></i></span>
                  <?php endif; ?>
                  <span class="fw-semibold"><?= e($it['menu']) ?></span>
                </div>
              </td>
              <td class="angka"><?= rupiah($it['harga']) ?></td>
              <td class="angka"><?= (int) $it['jumlah'] ?></td>
              <td class="angka text-end fw-semibold"><?= rupiah($it['harga'] * $it['jumlah']) ?></td>
            </tr>
          <?php endforeach; ?>
            <tr>
              <td colspan="3" class="text-end fw-bold">Total</td>
              <td class="text-end fw-bold angka" style="font-size:16px"><?= rupiah($pesanan['total']) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php if ($pesanan['catatan']): ?>
        <div class="card-body-k pt-0">
          <span class="text-secondary" style="font-size:13px">Catatan:</span>
          <div><?= e($pesanan['catatan']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-k mb-3">
      <div class="card-head">Pelanggan</div>
      <div class="card-body-k">
        <div class="fw-bold mb-1" style="font-size:15px"><?= e($pesanan['pelanggan']) ?></div>
        <div class="text-secondary" style="font-size:13.5px">
          <div><i class="bi bi-envelope me-2"></i><?= e($pesanan['email'] ?: '-') ?></div>
          <div><i class="bi bi-telephone me-2"></i><?= e($pesanan['no_hp'] ?: '-') ?></div>
          <div><i class="bi bi-clock me-2"></i><?= tanggal_id($pesanan['created_at'], true) ?></div>
        </div>
      </div>
    </div>

    <div class="card-k mb-3">
      <div class="card-head">Pembayaran</div>
      <div class="card-body-k">
        <?php if ($bayar): ?>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Metode</span>
            <span class="fw-semibold text-uppercase"><?= e($bayar['metode']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Jumlah</span>
            <span class="fw-semibold angka"><?= rupiah($bayar['jumlah']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Status</span>
            <?= badge_status_bayar($bayar['status']) ?>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-secondary">Tanggal Bayar</span>
            <span><?= $bayar['tanggal_bayar'] ? tanggal_id($bayar['tanggal_bayar'], true) : '-' ?></span>
          </div>
        <?php else: ?>
          <p class="text-secondary mb-0">Belum ada data pembayaran.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-k">
      <div class="card-head">Ubah Status Pesanan</div>
      <div class="card-body-k">
        <form method="post">
          <input type="hidden" name="aksi" value="ubah_status">
          <select name="status" class="form-select mb-3">
            <?php foreach (['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'] as $s): ?>
              <option value="<?= $s ?>" <?= $pesanan['status'] === $s ? 'selected' : '' ?>><?= label_status_pesanan($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary w-100">Simpan Status</button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
