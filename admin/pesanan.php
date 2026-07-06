<?php
require_once __DIR__ . '/includes/init.php';

/* ---------- Filter ---------- */
$q       = trim($_GET['q'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');
$status  = trim($_GET['status'] ?? '');

$where  = [];
$params = [];
if ($q !== '') {
    $where[]  = '(pl.nama LIKE ? OR p.nomor_pesanan LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($tanggal !== '') { $where[] = 'DATE(p.created_at) = ?'; $params[] = $tanggal; }
if ($status !== '' && in_array($status, ['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT p.*, pl.nama pelanggan,
           (SELECT COUNT(*) FROM pesanan_item pi WHERE pi.pesanan_id = p.id) jumlah_item,
           (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar
    FROM pesanan p JOIN pelanggan pl ON pl.id = p.pelanggan_id
    $sqlWhere ORDER BY p.created_at DESC LIMIT 200");
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$pageTitle = 'Manajemen Pesanan';
$active    = 'pesanan';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k">
  <div class="card-head">
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input type="text" name="q" class="form-control" style="width:230px"
             placeholder="Nama pelanggan / no. pesanan…" value="<?= e($q) ?>">
      <input type="date" name="tanggal" class="form-control" style="width:160px" value="<?= e($tanggal) ?>">
      <select name="status" class="form-select" style="width:160px">
        <option value="">Semua Status</option>
        <?php foreach (['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'] as $s): ?>
          <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= label_status_pesanan($s) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-primary"><i class="bi bi-search me-1"></i>Cari</button>
      <?php if ($q || $tanggal || $status): ?>
        <a href="pesanan.php" class="btn btn-light">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-k">
      <thead>
        <tr><th>No. Pesanan</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Waktu</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="8" class="text-center text-secondary py-4">Tidak ada pesanan.</td></tr>
      <?php else: foreach ($daftar as $p): ?>
        <tr>
          <td class="fw-semibold"><?= e($p['nomor_pesanan']) ?></td>
          <td><?= e($p['pelanggan']) ?></td>
          <td class="angka"><?= (int) $p['jumlah_item'] ?></td>
          <td class="angka fw-semibold"><?= rupiah($p['total']) ?></td>
          <td><?= badge_status_pesanan($p['status']) ?></td>
          <td><?= $p['status_bayar'] ? badge_status_bayar($p['status_bayar']) : '<span class="text-secondary">-</span>' ?></td>
          <td class="text-secondary" style="font-size:13px"><?= tanggal_id($p['created_at'], true) ?></td>
          <td class="text-end">
            <a href="pesanan_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-eye me-1"></i>Detail
            </a>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
