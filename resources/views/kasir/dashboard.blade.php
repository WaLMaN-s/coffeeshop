@include('partials.kasir_top')

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

@include('partials.kasir_bottom')
