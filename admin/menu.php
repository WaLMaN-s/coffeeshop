<?php
require_once __DIR__ . '/includes/init.php';

/* ---------- Aksi ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan') {
        $id        = (int) ($_POST['id'] ?? 0);
        $nama      = trim($_POST['nama'] ?? '');
        $kategori  = (int) ($_POST['kategori_id'] ?? 0);
        $harga     = (float) str_replace('.', '', $_POST['harga'] ?? '0');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status    = $_POST['status'] === 'nonaktif' ? 'nonaktif' : 'aktif';

        if ($nama === '' || $kategori <= 0 || $harga <= 0) {
            set_flash('gagal', 'Nama, kategori, dan harga wajib diisi.');
        } else {
            $errUpload = null;
            $foto = upload_gambar('foto', 'menu', $errUpload);
            if ($errUpload) {
                set_flash('gagal', $errUpload);
            } elseif ($id > 0) {
                if ($foto) {
                    $lama = $db->prepare('SELECT foto FROM menu WHERE id = ?');
                    $lama->execute([$id]);
                    hapus_gambar($lama->fetchColumn(), 'menu');
                    $db->prepare('UPDATE menu SET kategori_id=?, nama=?, harga=?, deskripsi=?, status=?, foto=? WHERE id=?')
                       ->execute([$kategori, $nama, $harga, $deskripsi, $status, $foto, $id]);
                } else {
                    $db->prepare('UPDATE menu SET kategori_id=?, nama=?, harga=?, deskripsi=?, status=? WHERE id=?')
                       ->execute([$kategori, $nama, $harga, $deskripsi, $status, $id]);
                }
                set_flash('sukses', 'Menu berhasil diperbarui.');
            } else {
                $db->prepare('INSERT INTO menu (kategori_id, nama, harga, deskripsi, status, foto) VALUES (?,?,?,?,?,?)')
                   ->execute([$kategori, $nama, $harga, $deskripsi, $status, $foto]);
                set_flash('sukses', 'Menu baru berhasil ditambahkan.');
            }
        }
    }

    if ($aksi === 'toggle') {
        $id = (int) $_POST['id'];
        $db->prepare("UPDATE menu SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([$id]);
        set_flash('sukses', 'Status menu diubah.');
    }

    if ($aksi === 'hapus') {
        $id  = (int) $_POST['id'];
        $ada = $db->prepare('SELECT COUNT(*) FROM pesanan_item WHERE menu_id = ?');
        $ada->execute([$id]);
        if ($ada->fetchColumn() > 0) {
            set_flash('gagal', 'Menu sudah dipakai di pesanan — nonaktifkan saja agar riwayat tetap utuh.');
        } else {
            $lama = $db->prepare('SELECT foto FROM menu WHERE id = ?');
            $lama->execute([$id]);
            hapus_gambar($lama->fetchColumn(), 'menu');
            $db->prepare('DELETE FROM menu WHERE id = ?')->execute([$id]);
            set_flash('sukses', 'Menu berhasil dihapus.');
        }
    }

    header('Location: menu.php' . (!empty($_GET['q']) ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

/* ---------- Data ---------- */
$q      = trim($_GET['q'] ?? '');
$fkat   = (int) ($_GET['kategori'] ?? 0);
$where  = [];
$params = [];
if ($q !== '')   { $where[] = 'm.nama LIKE ?';      $params[] = "%$q%"; }
if ($fkat > 0)   { $where[] = 'm.kategori_id = ?';  $params[] = $fkat; }
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT m.*, k.nama kategori FROM menu m
    JOIN kategori k ON k.id = m.kategori_id
    $sqlWhere ORDER BY m.created_at DESC");
$stmt->execute($params);
$daftarMenu = $stmt->fetchAll();
$daftarKategori = $db->query('SELECT * FROM kategori ORDER BY nama')->fetchAll();

$pageTitle = 'Manajemen Menu';
$active    = 'menu';
require __DIR__ . '/includes/layout_top.php';
?>

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
}
function formEdit(m) {
  document.getElementById('judulModal').textContent = 'Edit Menu';
  document.getElementById('f_id').value = m.id;
  document.getElementById('f_nama').value = m.nama;
  document.getElementById('f_kategori').value = m.kategori_id;
  document.getElementById('f_harga').value = m.harga;
  document.getElementById('f_deskripsi').value = m.deskripsi || '';
  document.getElementById('f_status').value = m.status;
}
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
