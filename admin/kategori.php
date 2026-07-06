<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');

    if ($aksi === 'simpan') {
        if ($nama === '') {
            set_flash('gagal', 'Nama kategori wajib diisi.');
        } elseif ($id > 0) {
            $db->prepare('UPDATE kategori SET nama = ? WHERE id = ?')->execute([$nama, $id]);
            set_flash('sukses', 'Kategori diperbarui.');
        } else {
            $db->prepare('INSERT INTO kategori (nama) VALUES (?)')->execute([$nama]);
            set_flash('sukses', 'Kategori ditambahkan.');
        }
    }

    if ($aksi === 'hapus') {
        $cek = $db->prepare('SELECT COUNT(*) FROM menu WHERE kategori_id = ?');
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            set_flash('gagal', 'Kategori masih dipakai oleh menu — pindahkan menunya dulu.');
        } else {
            $db->prepare('DELETE FROM kategori WHERE id = ?')->execute([$id]);
            set_flash('sukses', 'Kategori dihapus.');
        }
    }

    header('Location: kategori.php');
    exit;
}

$daftar = $db->query("
    SELECT k.*, COUNT(m.id) jumlah_menu
    FROM kategori k LEFT JOIN menu m ON m.kategori_id = k.id
    GROUP BY k.id ORDER BY k.nama")->fetchAll();

$pageTitle = 'Manajemen Kategori';
$active    = 'kategori';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card-k">
      <div class="card-head">
        Daftar Kategori
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalKategori" onclick="formTambah()">
          <i class="bi bi-plus-lg me-1"></i>Tambah Kategori
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-k">
          <thead><tr><th style="width:60px">#</th><th>Nama Kategori</th><th>Jumlah Menu</th><th class="text-end">Aksi</th></tr></thead>
          <tbody>
          <?php if (!$daftar): ?>
            <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada kategori.</td></tr>
          <?php else: foreach ($daftar as $i => $k): ?>
            <tr>
              <td class="angka"><?= $i + 1 ?></td>
              <td class="fw-semibold"><?= e($k['nama']) ?></td>
              <td class="angka"><?= (int) $k['jumlah_menu'] ?> menu</td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalKategori"
                        onclick="formEdit(<?= $k['id'] ?>, '<?= e(addslashes($k['nama'])) ?>')">
                  <i class="bi bi-pencil"></i>
                </button>
                <form method="post" class="d-inline" onsubmit="return confirm('Hapus kategori <?= e($k['nama']) ?>?')">
                  <input type="hidden" name="aksi" value="hapus">
                  <input type="hidden" name="id" value="<?= $k['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalKategori" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="judulModal">Tambah Kategori</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" id="f_id" value="0">
        <label class="form-label">Nama Kategori</label>
        <input type="text" name="nama" id="f_nama" class="form-control" required>
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
  document.getElementById('judulModal').textContent = 'Tambah Kategori';
  document.getElementById('f_id').value = 0;
  document.getElementById('f_nama').value = '';
}
function formEdit(id, nama) {
  document.getElementById('judulModal').textContent = 'Edit Kategori';
  document.getElementById('f_id').value = id;
  document.getElementById('f_nama').value = nama;
}
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
