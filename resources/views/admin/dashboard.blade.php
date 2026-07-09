@include('partials.admin_top')

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

<!-- Baris 3: terjual hari ini + menu terlaris + pesanan terbaru -->
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-k h-100">
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
  <div class="col-lg-4">
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
  <div class="col-lg-4">
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

@include('partials.admin_bottom')
