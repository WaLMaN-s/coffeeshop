@include('partials.kasir_top')

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
              <form method="post" class="d-inline" onsubmit="return confirm('Terima pembayaran <?= e($b['nomor_pesanan']) ?>?')">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="status" value="sudah_dibayar">
                <button class="btn btn-sm btn-primary"><i class="bi bi-check2 me-1"></i>Terima</button>
              </form>
              <form method="post" class="d-inline" onsubmit="return confirm('Tandai pembayaran ini gagal?')">
                <input type="hidden" name="aksi" value="ubah_status">
                <input type="hidden" name="id" value="<?= $b['id'] ?>">
                <input type="hidden" name="status" value="gagal">
                <button class="btn btn-sm btn-outline-danger">Gagal</button>
              </form>
            <?php else: ?>
              <a href="struk.php?id=<?= $b['pesanan_id'] ?>" target="_blank" class="btn btn-sm btn-light" title="Cetak struk"><i class="bi bi-printer"></i></a>
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

@include('partials.kasir_bottom')
