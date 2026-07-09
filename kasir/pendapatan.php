<?php
require_once __DIR__ . '/includes/init.php';

$hariIni = (float) $db->query("
    SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
    WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) = CURDATE()")->fetchColumn();
$mingguIni = (float) $db->query("
    SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
    WHERE status = 'sudah_dibayar' AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn();
$bulanIni = (float) $db->query("
    SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
    WHERE status = 'sudah_dibayar'
      AND YEAR(tanggal_bayar) = YEAR(CURDATE()) AND MONTH(tanggal_bayar) = MONTH(CURDATE())")->fetchColumn();

$perHari = $db->query("
    SELECT DATE(tanggal_bayar) tgl, COUNT(*) transaksi, SUM(jumlah) total
    FROM pembayaran
    WHERE status = 'sudah_dibayar' AND tanggal_bayar IS NOT NULL
    GROUP BY DATE(tanggal_bayar)
    ORDER BY tgl DESC
    LIMIT 30")->fetchAll();

$pageTitle = 'Pendapatan';
$active    = 'pendapatan';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="row g-3 mb-3">
  <?php
  $statCards = [
      ['bi-wallet2',        'ic-green',  'Hari Ini',              rupiah($hariIni)],
      ['bi-calendar-week',  'ic-blue',   '7 Hari Terakhir',       rupiah($mingguIni)],
      ['bi-graph-up-arrow', 'ic-violet', 'Bulan Ini',             rupiah($bulanIni)],
  ];
  foreach ($statCards as [$icon, $cls, $label, $nilai]): ?>
  <div class="col-12 col-md-4">
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

<div class="card-k">
  <div class="card-head">Pendapatan Per Hari <span class="text-secondary fw-normal" style="font-size:12.5px">30 hari terakhir (hanya pembayaran terverifikasi)</span></div>
  <div class="table-responsive">
    <table class="table table-k align-middle">
      <thead><tr><th>Tanggal</th><th>Transaksi</th><th class="text-end">Total</th></tr></thead>
      <tbody>
      <?php if (!$perHari): ?>
        <tr><td colspan="3" class="text-center text-secondary py-4">Belum ada pendapatan.</td></tr>
      <?php else: foreach ($perHari as $h): ?>
        <tr>
          <td class="fw-semibold">
            <?= tanggal_id($h['tgl']) ?>
            <?php if ($h['tgl'] === date('Y-m-d')): ?><span class="badge-status badge-selesai ms-1">Hari ini</span><?php endif; ?>
          </td>
          <td class="angka"><?= (int) $h['transaksi'] ?></td>
          <td class="angka text-end fw-bold"><?= rupiah($h['total']) ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
