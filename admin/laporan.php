<?php
require_once __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/laporan_data.php';

$qs = http_build_query(['periode' => $periode, 'mulai' => $mulai, 'sampai' => $sampai]);

$pageTitle = 'Laporan';
$active    = 'laporan';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k mb-3 no-print">
  <div class="card-body-k">
    <form class="d-flex gap-2 flex-wrap align-items-end" method="get" id="formLaporan">
      <div>
        <label class="form-label">Periode</label>
        <select name="periode" class="form-select" style="width:180px" onchange="ubahPeriode(this.value)">
          <option value="harian"   <?= $periode === 'harian' ? 'selected' : '' ?>>Hari Ini</option>
          <option value="mingguan" <?= $periode === 'mingguan' ? 'selected' : '' ?>>Minggu Ini</option>
          <option value="bulanan"  <?= $periode === 'bulanan' ? 'selected' : '' ?>>Bulan Ini</option>
          <option value="rentang"  <?= $periode === 'rentang' ? 'selected' : '' ?>>Rentang Tanggal</option>
        </select>
      </div>
      <div id="wrapRentang" style="<?= $periode === 'rentang' ? '' : 'display:none' ?>">
        <label class="form-label">Dari</label>
        <input type="date" name="mulai" class="form-control" value="<?= e($mulai) ?>">
      </div>
      <div id="wrapRentang2" style="<?= $periode === 'rentang' ? '' : 'display:none' ?>">
        <label class="form-label">Sampai</label>
        <input type="date" name="sampai" class="form-control" value="<?= e($sampai) ?>">
      </div>
      <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Tampilkan</button>
      <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
          <i class="bi bi-file-earmark-pdf me-1"></i>Cetak PDF
        </button>
        <a href="laporan_excel.php?<?= e($qs) ?>" class="btn btn-outline-primary">
          <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
      </div>
    </form>
  </div>
</div>

<div class="mb-3 d-none d-print-block">
  <h2 style="font-size:18px;font-weight:800;margin:0"><?= e($namaToko) ?> — Laporan Penjualan</h2>
  <p style="margin:4px 0 0;color:#555">Periode: <?= tanggal_id($mulai) ?> s.d. <?= tanggal_id($sampai) ?> · Dicetak <?= tanggal_id(date('Y-m-d H:i'), true) ?></p>
</div>

<div class="row g-3 mb-3">
  <div class="col-md-4">
    <div class="stat-tile">
      <span class="stat-icon ic-blue"><i class="bi bi-credit-card"></i></span>
      <div><div class="stat-label">Total Transaksi</div><div class="stat-value angka"><?= $ringkasan['transaksi'] ?></div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-tile">
      <span class="stat-icon ic-green"><i class="bi bi-wallet2"></i></span>
      <div><div class="stat-label">Total Pendapatan</div><div class="stat-value angka"><?= rupiah($ringkasan['pendapatan']) ?></div></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-tile">
      <span class="stat-icon ic-yellow"><i class="bi bi-receipt"></i></span>
      <div><div class="stat-label">Jumlah Pesanan</div><div class="stat-value angka"><?= $ringkasan['jumlah_pesanan'] ?></div></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-k h-100">
      <div class="card-head">Rincian Per Hari</div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th>Tanggal</th><th>Transaksi</th><th class="text-end">Pendapatan</th></tr></thead>
          <tbody>
          <?php if (!$perHari): ?>
            <tr><td colspan="3" class="text-center text-secondary py-4">Tidak ada transaksi pada periode ini.</td></tr>
          <?php else: foreach ($perHari as $h): ?>
            <tr>
              <td><?= tanggal_id($h['tgl']) ?></td>
              <td class="angka"><?= (int) $h['transaksi'] ?></td>
              <td class="angka text-end fw-semibold"><?= rupiah($h['pendapatan']) ?></td>
            </tr>
          <?php endforeach; ?>
            <tr>
              <td class="fw-bold">Total</td>
              <td class="angka fw-bold"><?= $ringkasan['transaksi'] ?></td>
              <td class="angka text-end fw-bold"><?= rupiah($ringkasan['pendapatan']) ?></td>
            </tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-k h-100">
      <div class="card-head">Menu Terlaris</div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th>#</th><th>Menu</th><th>Terjual</th><th class="text-end">Omzet</th></tr></thead>
          <tbody>
          <?php if (!$terlarisLaporan): ?>
            <tr><td colspan="4" class="text-center text-secondary py-4">Tidak ada penjualan pada periode ini.</td></tr>
          <?php else: foreach ($terlarisLaporan as $i => $m): ?>
            <tr>
              <td class="angka"><?= $i + 1 ?></td>
              <td>
                <span class="fw-semibold"><?= e($m['nama']) ?></span>
                <span class="text-secondary" style="font-size:12.5px">· <?= e($m['kategori']) ?></span>
              </td>
              <td class="angka"><?= (int) $m['terjual'] ?></td>
              <td class="angka text-end fw-semibold"><?= rupiah($m['omzet']) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function ubahPeriode(v) {
  const tampil = v === 'rentang';
  document.getElementById('wrapRentang').style.display  = tampil ? '' : 'none';
  document.getElementById('wrapRentang2').style.display = tampil ? '' : 'none';
  if (!tampil) document.getElementById('formLaporan').submit();
}
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
