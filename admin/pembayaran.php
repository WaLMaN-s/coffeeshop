<?php
require_once __DIR__ . '/includes/init.php';

/* ---------- Aksi verifikasi / ubah status ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ubah_status') {
    $id   = (int) $_POST['id'];
    $baru = $_POST['status'] ?? '';
    if (in_array($baru, ['belum_dibayar', 'sudah_dibayar', 'gagal'], true)) {
        if ($baru === 'sudah_dibayar') {
            $db->prepare("UPDATE pembayaran SET status = ?, tanggal_bayar = NOW() WHERE id = ?")->execute([$baru, $id]);
            $info = $db->prepare('SELECT p.id, p.nomor_pesanan FROM pembayaran b JOIN pesanan p ON p.id = b.pesanan_id WHERE b.id = ?');
            $info->execute([$id]);
            if ($row = $info->fetch()) {
                tambah_notifikasi($db, 'pembayaran', 'Pembayaran pesanan ' . $row['nomor_pesanan'] . ' berhasil diverifikasi.', (int) $row['id']);
            }
        } else {
            $db->prepare("UPDATE pembayaran SET status = ?, tanggal_bayar = NULL WHERE id = ?")->execute([$baru, $id]);
        }
        set_flash('sukses', 'Status pembayaran diubah menjadi "' . label_status_bayar($baru) . '".');
    }
    header('Location: pembayaran.php' . ($_POST['kembali'] ?? '' ? '?' . $_POST['kembali'] : ''));
    exit;
}

/* ---------- Filter ---------- */
$q      = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');
$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = '(pl.nama LIKE ? OR p.nama_tamu LIKE ? OR p.nomor_pesanan LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($status !== '' && in_array($status, ['belum_dibayar', 'sudah_dibayar', 'gagal'], true)) {
    $where[] = 'b.status = ?';
    $params[] = $status;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT b.*, p.nomor_pesanan, p.id pesanan_id, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja
    FROM pembayaran b
    JOIN pesanan p ON p.id = b.pesanan_id
    LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
    LEFT JOIN meja m ON m.id = p.meja_id
    $sqlWhere ORDER BY b.created_at DESC LIMIT 200");
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$pageTitle = 'Manajemen Pembayaran';
$active    = 'pembayaran';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k">
  <div class="card-head">
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input type="text" name="q" class="form-control" style="width:230px"
             placeholder="Nama pelanggan / no. pesanan…" value="<?= e($q) ?>">
      <select name="status" class="form-select" style="width:170px">
        <option value="">Semua Status</option>
        <?php foreach (['belum_dibayar', 'sudah_dibayar', 'gagal'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= label_status_bayar($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button>
      <?php if ($q || $status): ?><a href="pembayaran.php" class="btn btn-light">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-k">
      <thead>
        <tr><th>No. Pesanan</th><th>Pelanggan</th><th>Metode</th><th>Total</th><th>Tanggal Bayar</th><th>Status</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="7" class="text-center text-secondary py-4">Tidak ada pembayaran.</td></tr>
      <?php else: foreach ($daftar as $b): ?>
        <tr>
          <td><a href="pesanan_detail.php?id=<?= $b['pesanan_id'] ?>" class="fw-semibold" style="color:var(--primary)"><?= e($b['nomor_pesanan']) ?></a></td>
          <td>
            <?= e($b['pelanggan']) ?>
            <?php if ($b['nomor_meja']): ?><span class="text-secondary" style="font-size:12px">· Meja <?= e($b['nomor_meja']) ?></span><?php endif; ?>
          </td>
          <td class="text-uppercase fw-semibold" style="font-size:12.5px"><?= e($b['metode']) ?></td>
          <td class="angka fw-semibold"><?= rupiah($b['jumlah']) ?></td>
          <td class="text-secondary" style="font-size:13px"><?= $b['tanggal_bayar'] ? tanggal_id($b['tanggal_bayar'], true) : '-' ?></td>
          <td><?= badge_status_bayar($b['status']) ?></td>
          <td class="text-end">
            <?php if ($b['status'] === 'belum_dibayar'): ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Verifikasi pembayaran <?= e($b['nomor_pesanan']) ?>?')">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="status" value="sudah_dibayar">
                <button class="btn btn-sm btn-primary"><i class="bi bi-check2 me-1"></i>Verifikasi</button>
              </form>
              <form method="post" class="d-inline" onsubmit="return confirm('Tandai pembayaran ini gagal?')">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="status" value="gagal">
                <button class="btn btn-sm btn-outline-danger">Gagal</button>
              </form>
            <?php else: ?>
              <form method="post" class="d-inline" onsubmit="return confirm('Kembalikan status ke Belum Dibayar?')">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="status" value="belum_dibayar">
                <button class="btn btn-sm btn-light" title="Reset status"><i class="bi bi-arrow-counterclockwise"></i></button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
