@include('partials.kasir_top')

<div class="card-k">
  <div class="card-head">
    <form class="d-flex gap-2" method="get">
      <input type="text" name="q" class="form-control" style="width:250px" placeholder="Cari nama / no. HP…" value="<?= e($q) ?>">
      <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
      <?php if ($q): ?><a href="pelanggan.php" class="btn btn-light">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-k">
      <thead>
        <tr><th>Nama</th><th>No. HP</th><th>Jumlah Kunjungan</th><th>Total Belanja</th><th>Kunjungan Terakhir</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">Belum ada pelanggan.</td></tr>
      <?php else: foreach ($daftar as $pl): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="avatar" style="width:34px;height:34px;font-size:14px"><?= strtoupper(substr($pl['nama'], 0, 1)) ?></span>
              <span class="fw-semibold"><?= e($pl['nama']) ?></span>
            </div>
          </td>
          <td><?= e($pl['no_hp'] ?: '-') ?></td>
          <td class="angka"><?= (int) $pl['jumlah_pesanan'] ?></td>
          <td class="angka fw-semibold"><?= rupiah($pl['total_belanja']) ?></td>
          <td class="text-secondary" style="font-size:13px"><?= $pl['kunjungan_terakhir'] ? tanggal_id($pl['kunjungan_terakhir'], true) : '-' ?></td>
          <td class="text-end">
            <a href="pesanan.php?q=<?= urlencode($pl['nama']) ?>" class="btn btn-sm btn-light" title="Lihat pesanan"><i class="bi bi-receipt"></i></a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPelanggan"
                    onclick='formEdit(<?= json_encode([
                        'id' => $pl['id'], 'nama' => $pl['nama'], 'no_hp' => $pl['no_hp'],
                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="modalPelanggan" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Edit Pelanggan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" id="f_id">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input type="text" name="nama" id="f_nama" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">No. HP</label>
          <input type="text" name="no_hp" id="f_nohp" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script>
function formEdit(p) {
  document.getElementById('f_id').value = p.id;
  document.getElementById('f_nama').value = p.nama;
  document.getElementById('f_nohp').value = p.no_hp || '';
}
</script>

@include('partials.kasir_bottom')
