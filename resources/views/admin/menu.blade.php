@include('partials.admin_top')

<div class="card-k">
  <div class="card-head">
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input type="text" name="q" class="form-control" style="width:220px" placeholder="Cari nama menu…" value="<?= e($q) ?>">
      <select name="kategori" class="form-select" style="width:170px" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        <?php foreach ($daftarKategori as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $fkat === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
    </form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalMenu" onclick="formTambah()">
      <i class="bi bi-plus-lg me-1"></i>Tambah Menu
    </button>
  </div>

  <div class="table-responsive">
    <table class="table table-k">
      <thead>
        <tr><th>Foto</th><th>Nama Menu</th><th>Kategori</th><th>Harga</th><th>Status</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftarMenu): ?>
        <tr><td colspan="6" class="text-center text-secondary py-4">Tidak ada menu.</td></tr>
      <?php else: foreach ($daftarMenu as $m): ?>
        <tr>
          <td>
            <?php if ($m['foto']): ?>
              <img src="../uploads/menu/<?= e($m['foto']) ?>" class="foto-menu" alt="">
            <?php else: ?>
              <span class="foto-placeholder"><i class="bi bi-cup-hot"></i></span>
            <?php endif; ?>
          </td>
          <td>
            <div class="fw-semibold"><?= e($m['nama']) ?></div>
            <?php if ($m['deskripsi']): ?>
              <div class="text-secondary" style="font-size:12.5px;max-width:320px"><?= e(mb_strimwidth($m['deskripsi'], 0, 80, '…')) ?></div>
            <?php endif; ?>
          </td>
          <td><?= e($m['kategori']) ?></td>
          <td class="angka fw-semibold"><?= rupiah($m['harga']) ?></td>
          <td>
            <form method="post" class="d-inline">
              <input type="hidden" name="aksi" value="toggle">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="badge-status <?= $m['status'] === 'aktif' ? 'badge-selesai' : 'badge-batal' ?>"
                      style="border:0" title="Klik untuk ubah status">
                <?= $m['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
              </button>
            </form>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMenu"
                    onclick='formEdit(<?= json_encode([
                        'id' => $m['id'], 'nama' => $m['nama'], 'kategori_id' => $m['kategori_id'],
                        'harga' => (float) $m['harga'], 'deskripsi' => $m['deskripsi'], 'status' => $m['status'],
                        'tanpa_gula' => (int) $m['tanpa_gula'],
                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus menu <?= e($m['nama']) ?>?')">
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal tambah/edit -->
<div class="modal fade" id="modalMenu" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="judulModal">Tambah Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" id="f_id" value="0">
        <div class="mb-3">
          <label class="form-label">Nama Menu</label>
          <input type="text" name="nama" id="f_nama" class="form-control" required>
        </div>
        <div class="row">
          <div class="col-6 mb-3">
            <label class="form-label">Kategori</label>
            <select name="kategori_id" id="f_kategori" class="form-select" required>
              <?php foreach ($daftarKategori as $k): ?>
                <option value="<?= $k['id'] ?>"><?= e($k['nama']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6 mb-3">
            <label class="form-label">Harga (Rp)</label>
            <input type="number" name="harga" id="f_harga" class="form-control" min="0" required>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Deskripsi</label>
          <textarea name="deskripsi" id="f_deskripsi" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="tanpa_gula" value="1" id="f_tanpa_gula">
          <label class="form-check-label" for="f_tanpa_gula">
            Tanpa opsi gula <span class="text-secondary fw-normal">(contoh: Espresso, Americano — pelanggan tidak ditawari pilihan gula)</span>
          </label>
        </div>
        <div class="row">
          <div class="col-7 mb-3">
            <label class="form-label">Foto Menu <span class="text-secondary fw-normal">(JPG/PNG/WEBP, maks 2 MB)</span></label>
            <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
          </div>
          <div class="col-5 mb-3">
            <label class="form-label">Status</label>
            <select name="status" id="f_status" class="form-select">
              <option value="aktif">Aktif</option>
              <option value="nonaktif">Nonaktif</option>
            </select>
          </div>
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
function formTambah() {
  document.getElementById('judulModal').textContent = 'Tambah Menu';
  ['f_id','f_nama','f_harga','f_deskripsi'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('f_id').value = 0;
  document.getElementById('f_status').value = 'aktif';
  document.getElementById('f_tanpa_gula').checked = false;
}
function formEdit(m) {
  document.getElementById('judulModal').textContent = 'Edit Menu';
  document.getElementById('f_id').value = m.id;
  document.getElementById('f_nama').value = m.nama;
  document.getElementById('f_kategori').value = m.kategori_id;
  document.getElementById('f_harga').value = m.harga;
  document.getElementById('f_deskripsi').value = m.deskripsi || '';
  document.getElementById('f_status').value = m.status;
  document.getElementById('f_tanpa_gula').checked = !!m.tanpa_gula;
}
</script>

@include('partials.admin_bottom')
