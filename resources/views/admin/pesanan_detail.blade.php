@include('partials.admin_top')

<a href="pesanan.php" class="d-inline-flex align-items-center gap-1 mb-3 fw-semibold" style="color:var(--primary)">
  <i class="bi bi-arrow-left"></i> Kembali ke daftar pesanan
</a>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="card-k mb-3">
      <div class="card-head">
        <span><?= e($pesanan['nomor_pesanan']) ?></span>
        <?= badge_status_pesanan($pesanan['status']) ?>
      </div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th class="text-end">Subtotal</th></tr></thead>
          <tbody>
          <?php foreach ($item as $it): ?>
            <tr>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <?php if ($it['foto']): ?>
                    <img src="../uploads/menu/<?= e($it['foto']) ?>" class="foto-menu" alt="">
                  <?php else: ?>
                    <span class="foto-placeholder"><i class="bi bi-cup-hot"></i></span>
                  <?php endif; ?>
                  <span>
                    <span class="fw-semibold"><?= e($it['menu']) ?></span>
                    <?php if (!empty($it['opsi'])): ?>
                      <span class="d-block text-secondary" style="font-size:12px"><?= e($it['opsi']) ?></span>
                    <?php endif; ?>
                  </span>
                </div>
              </td>
              <td class="angka"><?= rupiah($it['harga']) ?></td>
              <td class="angka"><?= (int) $it['jumlah'] ?></td>
              <td class="angka text-end fw-semibold"><?= rupiah($it['harga'] * $it['jumlah']) ?></td>
            </tr>
          <?php endforeach; ?>
            <tr>
              <td colspan="3" class="text-end fw-bold">Total</td>
              <td class="text-end fw-bold angka" style="font-size:16px"><?= rupiah($pesanan['total']) ?></td>
            </tr>
          </tbody>
        </table>
      </div>
      <?php if ($pesanan['catatan']): ?>
        <div class="card-body-k pt-0">
          <span class="text-secondary" style="font-size:13px">Catatan:</span>
          <div><?= e($pesanan['catatan']) ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card-k mb-3">
      <div class="card-head">Pelanggan</div>
      <div class="card-body-k">
        <div class="fw-bold mb-1" style="font-size:15px"><?= e($pesanan['pelanggan']) ?></div>
        <div class="text-secondary" style="font-size:13.5px">
          <?php if ($pesanan['nomor_meja']): ?>
            <div><i class="bi bi-table me-2"></i>Meja <?= e($pesanan['nomor_meja']) ?></div>
          <?php endif; ?>
          <div><i class="bi bi-telephone me-2"></i><?= e($pesanan['no_hp'] ?: '-') ?></div>
          <?php if (!$pesanan['nomor_meja']): ?>
            <div><i class="bi bi-envelope me-2"></i><?= e($pesanan['email'] ?: '-') ?></div>
          <?php endif; ?>
          <div><i class="bi bi-clock me-2"></i><?= tanggal_id($pesanan['created_at'], true) ?></div>
        </div>
      </div>
    </div>

    <?php if ($riwayat): ?>
    <div class="card-k mb-3">
      <div class="card-head">Riwayat Kunjungan Pelanggan Ini</div>
      <div class="card-body-k" style="padding-top:6px">
        <?php foreach ($riwayat as $r): ?>
          <a href="pesanan_detail.php?id=<?= $r['id'] ?>" class="d-block py-2" style="border-bottom:1px solid var(--border);color:inherit;text-decoration:none">
            <div class="d-flex justify-content-between">
              <span class="fw-semibold" style="font-size:13.5px"><?= e($r['nomor_pesanan']) ?></span>
              <span class="fw-semibold angka" style="font-size:13.5px"><?= rupiah($r['total']) ?></span>
            </div>
            <div class="text-secondary d-flex justify-content-between" style="font-size:12px">
              <span><?= $r['nomor_meja'] ? 'Meja ' . e($r['nomor_meja']) : '-' ?> · <?= tanggal_id($r['created_at'], true) ?></span>
              <?= badge_status_pesanan($r['status']) ?>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="card-k mb-3">
      <div class="card-head">Pembayaran</div>
      <div class="card-body-k">
        <?php if ($bayar): ?>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Metode</span>
            <span class="fw-semibold text-uppercase"><?= e($bayar['metode']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Jumlah</span>
            <span class="fw-semibold angka"><?= rupiah($bayar['jumlah']) ?></span>
          </div>
          <div class="d-flex justify-content-between mb-2">
            <span class="text-secondary">Status</span>
            <?= badge_status_bayar($bayar['status']) ?>
          </div>
          <div class="d-flex justify-content-between">
            <span class="text-secondary">Tanggal Bayar</span>
            <span><?= $bayar['tanggal_bayar'] ? tanggal_id($bayar['tanggal_bayar'], true) : '-' ?></span>
          </div>
        <?php else: ?>
          <p class="text-secondary mb-0">Belum ada data pembayaran.</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="card-k">
      <div class="card-head">Ubah Status Pesanan</div>
      <div class="card-body-k">
        <form method="post">
          <input type="hidden" name="aksi" value="ubah_status">
          <select name="status" class="form-select mb-3">
            <?php foreach (['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'] as $s): ?>
              <option value="<?= $s ?>" <?= $pesanan['status'] === $s ? 'selected' : '' ?>><?= label_status_pesanan($s) ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-primary w-100">Simpan Status</button>
        </form>
      </div>
    </div>
  </div>
</div>

@include('partials.admin_bottom')
