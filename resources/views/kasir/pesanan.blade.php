@include('partials.kasir_top')

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
        <tr><th>Antrian</th><th>No. Pesanan</th><th>Pelanggan</th><th>Item</th><th>Total</th><th>Status</th><th>Pembayaran</th><th>Waktu</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="9" class="text-center text-secondary py-4">Tidak ada pesanan.</td></tr>
      <?php else: foreach ($daftar as $p): ?>
        <tr>
          <td class="fw-bold" style="color:var(--primary)">#<?= no_antrian($p['nomor_pesanan']) ?></td>
          <td class="fw-semibold"><?= e($p['nomor_pesanan']) ?></td>
          <td>
            <?= e($p['pelanggan']) ?>
            <?php if ($p['nomor_meja']): ?><span class="text-secondary" style="font-size:12px">· Meja <?= e($p['nomor_meja']) ?></span><?php endif; ?>
          </td>
          <td style="max-width:260px;font-size:13px"><?= e($p['item_ringkas'] ?: '-') ?></td>
          <td class="angka fw-semibold"><?= rupiah($p['total']) ?></td>
          <td><?= badge_status_pesanan($p['status']) ?></td>
          <td><?= $p['status_bayar'] ? badge_status_bayar($p['status_bayar']) : '<span class="text-secondary">-</span>' ?></td>
          <td class="text-secondary" style="font-size:13px"><?= tanggal_id($p['created_at'], true) ?></td>
          <td class="text-end">
            <a href="struk.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-light" title="Cetak struk"><i class="bi bi-printer"></i></a>
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

@include('partials.kasir_bottom')
