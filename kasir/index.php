<?php
require_once __DIR__ . '/includes/init.php';

$pesananHariIni = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$menunggu       = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();
$diproses       = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'diproses'")->fetchColumn();
$siap           = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'siap'")->fetchColumn();
$belumBayar     = (int) $db->query("SELECT COUNT(*) FROM pembayaran WHERE status = 'belum_dibayar'")->fetchColumn();

$antrean = $db->query("
    SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja,
           (SELECT GROUP_CONCAT(CONCAT(pi.jumlah, '× ', mn.nama) ORDER BY pi.id SEPARATOR ', ')
            FROM pesanan_item pi JOIN menu mn ON mn.id = pi.menu_id
            WHERE pi.pesanan_id = p.id) item_ringkas,
           (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
    LEFT JOIN meja m ON m.id = p.meja_id
    WHERE p.status IN ('menunggu','diproses','siap')
    ORDER BY p.created_at ASC LIMIT 50")->fetchAll();

/* Item terjual hari ini (pesanan yang tidak dibatalkan), per menu */
$terjualHariIni = $db->query("
    SELECT mn.nama, SUM(pi.jumlah) qty
    FROM pesanan_item pi
    JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan' AND DATE(p.created_at) = CURDATE()
    JOIN menu mn ON mn.id = pi.menu_id
    GROUP BY pi.menu_id, mn.nama
    ORDER BY qty DESC, mn.nama")->fetchAll();
$totalItemHariIni = array_sum(array_column($terjualHariIni, 'qty'));

$pageTitle = 'Dashboard';
$active    = 'dashboard';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="row g-3 mb-3">
  <?php
  $statCards = [
      ['bi-receipt',       'ic-yellow', 'Pesanan Hari Ini', $pesananHariIni],
      ['bi-hourglass-split','ic-blue',  'Menunggu',         $menunggu],
      ['bi-arrow-repeat',  'ic-violet', 'Diproses',         $diproses],
      ['bi-check2-circle', 'ic-green',  'Siap Diambil',     $siap],
      ['bi-credit-card',   'ic-aqua',   'Belum Dibayar',    $belumBayar],
  ];
  foreach ($statCards as [$icon, $cls, $label, $nilai]): ?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="stat-tile">
      <span class="stat-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></span>
      <div>
        <div class="stat-label"><?= $label ?></div>
        <div class="stat-value angka"><?= $nilai ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-k">
      <div class="card-head">
        <span>Antrean Pesanan Aktif</span>
        <a href="pesanan_baru.php" class="btn btn-sm btn-primary"><i class="bi bi-cart-plus me-1"></i>Pesanan Baru (Kasir)</a>
      </div>
      <div class="table-responsive">
        <table class="table table-k align-middle">
          <thead>
            <tr><th>Antrian</th><th>No. Pesanan</th><th>Pelanggan</th><th>Item</th><th>Status</th><th>Pembayaran</th><th class="text-end">Aksi</th></tr>
          </thead>
          <tbody>
          <?php if (!$antrean): ?>
            <tr><td colspan="7" class="text-center text-secondary py-4">Tidak ada pesanan aktif. Kerja bagus! ☕</td></tr>
          <?php else: foreach ($antrean as $p): ?>
            <tr>
              <td><span class="fw-bold" style="font-size:18px;color:var(--primary)">#<?= no_antrian($p['nomor_pesanan']) ?></span></td>
              <td class="fw-semibold"><?= e($p['nomor_pesanan']) ?>
                <span class="d-block text-secondary" style="font-size:11.5px"><?= tanggal_id($p['created_at'], true) ?></span>
              </td>
              <td>
                <?= e($p['pelanggan']) ?>
                <?php if ($p['nomor_meja']): ?><span class="text-secondary" style="font-size:12px">· Meja <?= e($p['nomor_meja']) ?></span><?php endif; ?>
              </td>
              <td style="max-width:220px;font-size:13px"><?= e($p['item_ringkas'] ?: '-') ?></td>
              <td><?= badge_status_pesanan($p['status']) ?></td>
              <td><?= $p['status_bayar'] ? badge_status_bayar($p['status_bayar']) : '<span class="text-secondary">-</span>' ?></td>
              <td class="text-end">
                <a href="pesanan_detail.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye me-1"></i>Proses
                </a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-k">
      <div class="card-head">
        <span>Terjual Hari Ini</span>
        <span class="text-secondary" style="font-size:12.5px;font-weight:600"><?= (int) $totalItemHariIni ?> item</span>
      </div>
      <div class="table-responsive">
        <table class="table table-k align-middle">
          <thead><tr><th>Menu</th><th class="text-end">Jumlah</th></tr></thead>
          <tbody>
          <?php if (!$terjualHariIni): ?>
            <tr><td colspan="2" class="text-center text-secondary py-4">Belum ada penjualan hari ini.</td></tr>
          <?php else: foreach ($terjualHariIni as $t): ?>
            <tr>
              <td><?= e($t['nama']) ?></td>
              <td class="angka text-end fw-semibold"><?= (int) $t['qty'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
