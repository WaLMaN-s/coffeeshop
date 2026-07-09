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

/* ---------- Verifikasi pembayaran cepat ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'verifikasi_bayar') {
    $bayarId = (int) $_POST['bayar_id'];
    $db->prepare("UPDATE pembayaran SET status = 'sudah_dibayar', tanggal_bayar = NOW() WHERE id = ?")->execute([$bayarId]);
    $info = $db->prepare('SELECT nomor_pesanan FROM pesanan WHERE id = ?');
    $info->execute([$id]);
    tambah_notifikasi($db, 'pembayaran', 'Pembayaran pesanan ' . $info->fetchColumn() . ' berhasil diverifikasi.', $id);
    set_flash('sukses', 'Pembayaran diverifikasi.');
    header('Location: pesanan_detail.php?id=' . $id);
    exit;
}

/* ---------- Data ---------- */
$stmt = $db->prepare("
    SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, pl.no_hp, m.nomor_meja
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
    LEFT JOIN meja m ON m.id = p.meja_id
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

$riwayat = [];
if ($pesanan['pelanggan_id']) {
    $stmt = $db->prepare("
        SELECT p.id, p.nomor_pesanan, p.status, p.total, p.created_at, m.nomor_meja
        FROM pesanan p LEFT JOIN meja m ON m.id = p.meja_id
        WHERE p.pelanggan_id = ? AND p.id <> ?
        ORDER BY p.created_at DESC LIMIT 5");
    $stmt->execute([$pesanan['pelanggan_id'], $id]);
    $riwayat = $stmt->fetchAll();
}

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
        <span>
          <span class="fw-bold" style="font-size:18px;color:var(--primary)">Antrian #<?= no_antrian($pesanan['nomor_pesanan']) ?></span>
          <span class="text-secondary d-block" style="font-size:12.5px;font-weight:600"><?= e($pesanan['nomor_pesanan']) ?></span>
        </span>
        <div class="d-flex align-items-center gap-2">
          <?= badge_status_pesanan($pesanan['status']) ?>
          <a href="struk.php?id=<?= $pesanan['id'] ?>" target="_blank" class="btn btn-sm btn-primary">
            <i class="bi bi-printer me-1"></i>Cetak Struk
          </a>
        </div>
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
                  <span>
                    <span class="fw-semibold"><?= e($it['menu']) ?></span>
                    <?php if (!empty($it['opsi'])): ?>
                      <span class="d-block text-secondary" style="font-size:12px"><?= e($it['opsi']) ?></span>
                    <?php endif; ?>
                  </span>
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
          <?php if ($pesanan['nomor_meja']): ?>
            <div><i class="bi bi-table me-2"></i>Meja <?= e($pesanan['nomor_meja']) ?></div>
          <?php endif; ?>
          <div><i class="bi bi-telephone me-2"></i><?= e($pesanan['no_hp'] ?: '-') ?></div>
          <div><i class="bi bi-clock me-2"></i><?= tanggal_id($pesanan['created_at'], true) ?></div>
        </div>
      </div>
    </div>

    <?php if ($riwayat): ?>
    <div class="card-k mb-3">
      <div class="card-head">Riwayat Kunjungan Pelanggan Ini</div>
      <div class="card-body-k" style="padding-top:6px">
        <?php foreach ($riwayat as $r): ?>
          <a href="pesanan_detail.php?id=<?= $r['id'] ?>" class="d-block py-2" style="border-bottom:1px solid var(--border);color:inherit;text-decoration:none">
            <div class="d-flex justify-content-between">
              <span class="fw-semibold" style="font-size:13.5px"><?= e($r['nomor_pesanan']) ?></span>
              <span class="fw-semibold angka" style="font-size:13.5px"><?= rupiah($r['total']) ?></span>
            </div>
            <div class="text-secondary d-flex justify-content-between" style="font-size:12px">
              <span><?= $r['nomor_meja'] ? 'Meja ' . e($r['nomor_meja']) : '-' ?> · <?= tanggal_id($r['created_at'], true) ?></span>
              <?= badge_status_pesanan($r['status']) ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

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
          <div class="d-flex justify-content-between mb-3">
            <span class="text-secondary">Tanggal Bayar</span>
            <span><?= $bayar['tanggal_bayar'] ? tanggal_id($bayar['tanggal_bayar'], true) : '-' ?></span>
          </div>
          <?php if ($bayar['status'] === 'belum_dibayar'): ?>
            <form method="post" onsubmit="return confirm('Terima pembayaran <?= rupiah($bayar['jumlah']) ?> tunai/QRIS untuk pesanan ini?')">
              <input type="hidden" name="aksi" value="verifikasi_bayar">
              <input type="hidden" name="bayar_id" value="<?= $bayar['id'] ?>">
              <button class="btn btn-primary w-100"><i class="bi bi-cash-coin me-1"></i>Terima & Verifikasi Bayar</button>
            </form>
          <?php endif; ?>
        <?php else: ?>
          <p class="text-secondary mb-0">Belum ada data pembayaran.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-k">
      <div class="card-head">Ubah Status Pesanan</div>
      <div class="card-body-k">
        <div class="d-grid gap-2">
          <?php
          $tombolStatus = [
              'menunggu'   => ['bi-hourglass-split', 'Menunggu'],
              'diproses'   => ['bi-arrow-repeat',    'Diproses'],
              'siap'       => ['bi-check2-circle',   'Siap Diambil'],
              'selesai'    => ['bi-check-all',       'Selesai'],
              'dibatalkan' => ['bi-x-circle',        'Dibatalkan'],
          ];
          foreach ($tombolStatus as $s => [$ikonS, $labelS]):
            $aktifS = $pesanan['status'] === $s;
            $kelas  = $aktifS ? 'btn-primary' : ($s === 'dibatalkan' ? 'btn-outline-danger' : 'btn-outline-primary');
          ?>
            <?php if ($aktifS): ?>
              <button type="button" class="btn <?= $kelas ?> text-start" style="padding:14px 18px;font-size:15px;font-weight:700" disabled>
                <i class="bi <?= $ikonS ?> me-2"></i><?= $labelS ?> <i class="bi bi-check-lg ms-2"></i>
              </button>
            <?php else: ?>
              <form method="post" class="d-grid" <?= $s === 'dibatalkan' ? "onsubmit=\"return confirm('Batalkan pesanan ini?')\"" : '' ?>>
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="status" value="<?= $s ?>">
                <button class="btn <?= $kelas ?> text-start" style="padding:14px 18px;font-size:15px;font-weight:600">
                  <i class="bi <?= $ikonS ?> me-2"></i><?= $labelS ?>
                </button>
              </form>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
