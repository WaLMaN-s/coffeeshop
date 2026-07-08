<?php
require_once __DIR__ . '/includes/init.php';

/* ---------- Statistik kartu ---------- */
$totalMenu      = (int) $db->query('SELECT COUNT(*) FROM menu')->fetchColumn();
$totalKategori  = (int) $db->query('SELECT COUNT(*) FROM kategori')->fetchColumn();
$totalMejaAktif = (int) $db->query("SELECT COUNT(*) FROM meja WHERE status = 'aktif'")->fetchColumn();

$pesananHariIni = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pesananSelesai = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'selesai'")->fetchColumn();
$pesananProses  = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'diproses'")->fetchColumn();

$pendapatanHariIni = (float) $db->query("
    SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
    WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) = CURDATE()")->fetchColumn();
$pendapatanBulanIni = (float) $db->query("
    SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
    WHERE status = 'sudah_dibayar'
      AND YEAR(tanggal_bayar) = YEAR(CURDATE()) AND MONTH(tanggal_bayar) = MONTH(CURDATE())")->fetchColumn();

/* ---------- Grafik harian: pendapatan 14 hari terakhir ---------- */
$harian = [];
for ($i = 13; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $harian[$tgl] = 0;
}
$rows = $db->query("
    SELECT DATE(tanggal_bayar) tgl, SUM(jumlah) total FROM pembayaran
    WHERE status = 'sudah_dibayar' AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(tanggal_bayar)")->fetchAll();
foreach ($rows as $r) {
    if (isset($harian[$r['tgl']])) $harian[$r['tgl']] = (float) $r['total'];
}
$labelHarian = array_map(fn($t) => date('j/n', strtotime($t)), array_keys($harian));
$dataHarian  = array_values($harian);

/* ---------- Grafik bulanan: pendapatan 12 bulan terakhir ---------- */
$bulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $bulanan[date('Y-m', strtotime("first day of -$i month"))] = 0;
}
$rows = $db->query("
    SELECT DATE_FORMAT(tanggal_bayar, '%Y-%m') bln, SUM(jumlah) total FROM pembayaran
    WHERE status = 'sudah_dibayar'
      AND tanggal_bayar >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
    GROUP BY DATE_FORMAT(tanggal_bayar, '%Y-%m')")->fetchAll();
foreach ($rows as $r) {
    if (isset($bulanan[$r['bln']])) $bulanan[$r['bln']] = (float) $r['total'];
}
$namaBulan    = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$labelBulanan = array_map(fn($b) => $namaBulan[(int) substr($b, 5)] . ' ' . substr($b, 2, 2), array_keys($bulanan));
$dataBulanan  = array_values($bulanan);

/* ---------- Menu terlaris (top 5, semua waktu) ---------- */
$terlaris = $db->query("
    SELECT m.nama, k.nama kategori, SUM(pi.jumlah) terjual
    FROM pesanan_item pi
    JOIN menu m ON m.id = pi.menu_id
    JOIN kategori k ON k.id = m.kategori_id
    JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan'
    GROUP BY pi.menu_id ORDER BY terjual DESC LIMIT 5")->fetchAll();
$maxTerjual = $terlaris ? max(array_column($terlaris, 'terjual')) : 1;

/* ---------- 5 pesanan terbaru ---------- */
$terbaru = $db->query("
    SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
    LEFT JOIN meja m ON m.id = p.meja_id
    ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$active    = 'dashboard';
require __DIR__ . '/includes/layout_top.php';
?>

<!-- Baris 1: kartu statistik -->
<div class="row g-3 mb-3">
  <?php
  $statCards = [
      ['bi-cup-hot',        'ic-blue',   'Total Menu',            $totalMenu],
      ['bi-tags',           'ic-violet', 'Total Kategori',        $totalKategori],
      ['bi-qr-code',        'ic-aqua',   'Meja Aktif',            $totalMejaAktif],
      ['bi-receipt',        'ic-yellow', 'Pesanan Hari Ini',      $pesananHariIni],
      ['bi-check2-circle',  'ic-green',  'Pesanan Selesai',       $pesananSelesai],
      ['bi-arrow-repeat',   'ic-blue',   'Pesanan Diproses',      $pesananProses],
      ['bi-wallet2',        'ic-green',  'Pendapatan Hari Ini',   rupiah($pendapatanHariIni)],
      ['bi-graph-up-arrow', 'ic-violet', 'Pendapatan Bulan Ini',  rupiah($pendapatanBulanIni)],
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

<!-- Baris 2: grafik -->
<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <div class="card-k h-100">
      <div class="card-head">Penjualan Harian <span class="text-secondary fw-normal" style="font-size:12.5px">14 hari terakhir</span></div>
      <div class="card-body-k"><canvas id="grafikHarian" height="240"></canvas></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card-k h-100">
      <div class="card-head">Penjualan Bulanan <span class="text-secondary fw-normal" style="font-size:12.5px">12 bulan</span></div>
      <div class="card-body-k"><canvas id="grafikBulanan" height="240"></canvas></div>
    </div>
  </div>
</div>

<!-- Baris 3: menu terlaris + pesanan terbaru -->
<div class="row g-3">
  <div class="col-lg-5">
    <div class="card-k h-100">
      <div class="card-head">Menu Terlaris</div>
      <div class="card-body-k">
        <?php if (!$terlaris): ?>
          <p class="text-secondary mb-0">Belum ada penjualan.</p>
        <?php else: foreach ($terlaris as $i => $m): ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between mb-1">
              <span class="fw-semibold"><?= ($i + 1) ?>. <?= e($m['nama']) ?>
                <span class="text-secondary fw-normal" style="font-size:12.5px">· <?= e($m['kategori']) ?></span></span>
              <span class="angka fw-semibold"><?= (int) $m['terjual'] ?> terjual</span>
            </div>
            <div style="height:8px;background:var(--bg);border-radius:99px;overflow:hidden">
              <div style="height:100%;width:<?= round($m['terjual'] / $maxTerjual * 100) ?>%;background:#2a78d6;border-radius:99px"></div>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="card-k h-100">
      <div class="card-head">
        5 Pesanan Terbaru
        <a href="pesanan.php" class="fw-semibold" style="font-size:13px;color:var(--primary)">Lihat semua</a>
      </div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th>No. Pesanan</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
          <tbody>
          <?php if (!$terbaru): ?>
            <tr><td colspan="5" class="text-center text-secondary py-4">Belum ada pesanan.</td></tr>
          <?php else: foreach ($terbaru as $p): ?>
            <tr>
              <td><a href="pesanan_detail.php?id=<?= $p['id'] ?>" class="fw-semibold" style="color:var(--primary)"><?= e($p['nomor_pesanan']) ?></a></td>
              <td>
                <?= e($p['pelanggan']) ?>
                <?php if ($p['nomor_meja']): ?><span class="text-secondary" style="font-size:12px">· Meja <?= e($p['nomor_meja']) ?></span><?php endif; ?>
              </td>
              <td class="angka"><?= rupiah($p['total']) ?></td>
              <td><?= badge_status_pesanan($p['status']) ?></td>
              <td class="text-secondary" style="font-size:13px"><?= tanggal_id($p['created_at'], true) ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const inkMuted = '#898781', gridColor = '#e1e0d9', seri1 = '#2a78d6';
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.color = inkMuted;

const formatRupiah = v => 'Rp ' + Number(v).toLocaleString('id-ID');
const opsiDasar = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: '#0b0b0b', padding: 10, cornerRadius: 8, displayColors: false,
      callbacks: { label: ctx => 'Pendapatan: ' + formatRupiah(ctx.parsed.y) }
    }
  },
  scales: {
    x: { grid: { display: false }, border: { color: '#c3c2b7' } },
    y: {
      beginAtZero: true,
      grid: { color: gridColor }, border: { display: false },
      ticks: { callback: v => v >= 1000000 ? (v / 1000000) + ' jt' : (v / 1000) + ' rb', maxTicksLimit: 6 }
    }
  }
};

new Chart(document.getElementById('grafikHarian'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labelHarian) ?>,
    datasets: [{
      label: 'Pendapatan',
      data: <?= json_encode($dataHarian) ?>,
      borderColor: seri1, borderWidth: 2,
      pointRadius: 0, pointHoverRadius: 5,
      pointHoverBackgroundColor: seri1, pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
      tension: 0.3, fill: true,
      backgroundColor: c => {
        const g = c.chart.ctx.createLinearGradient(0, 0, 0, c.chart.height);
        g.addColorStop(0, 'rgba(42,120,214,0.18)');
        g.addColorStop(1, 'rgba(42,120,214,0)');
        return g;
      }
    }]
  },
  options: { ...opsiDasar, interaction: { mode: 'index', intersect: false } }
});

new Chart(document.getElementById('grafikBulanan'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($labelBulanan) ?>,
    datasets: [{
      label: 'Pendapatan',
      data: <?= json_encode($dataBulanan) ?>,
      backgroundColor: seri1,
      borderRadius: { topLeft: 4, topRight: 4 },
      barPercentage: 0.55, categoryPercentage: 0.8
    }]
  },
  options: opsiDasar
});
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
