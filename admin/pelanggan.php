<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'simpan') {
        $nama  = trim($_POST['nama'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $noHp  = trim($_POST['no_hp'] ?? '');
        if ($nama === '') {
            set_flash('gagal', 'Nama pelanggan wajib diisi.');
        } else {
            $db->prepare('UPDATE pelanggan SET nama = ?, email = ?, no_hp = ? WHERE id = ?')
               ->execute([$nama, $email ?: null, $noHp ?: null, $id]);
            set_flash('sukses', 'Data pelanggan diperbarui.');
        }
    }

    if ($aksi === 'hapus') {
        $cek = $db->prepare('SELECT COUNT(*) FROM pesanan WHERE pelanggan_id = ?');
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            set_flash('gagal', 'Pelanggan memiliki riwayat pesanan dan tidak bisa dihapus.');
        } else {
            $db->prepare('DELETE FROM pelanggan WHERE id = ?')->execute([$id]);
            set_flash('sukses', 'Pelanggan dihapus.');
        }
    }

    header('Location: pelanggan.php');
    exit;
}

$q      = trim($_GET['q'] ?? '');
$params = [];
$sqlWhere = '';
if ($q !== '') {
    $sqlWhere = 'WHERE pl.nama LIKE ? OR pl.email LIKE ? OR pl.no_hp LIKE ?';
    $params   = ["%$q%", "%$q%", "%$q%"];
}
$stmt = $db->prepare("
    SELECT pl.*, COUNT(p.id) jumlah_pesanan, COALESCE(SUM(CASE WHEN p.status='selesai' THEN p.total END),0) total_belanja
    FROM pelanggan pl LEFT JOIN pesanan p ON p.pelanggan_id = pl.id
    $sqlWhere GROUP BY pl.id ORDER BY pl.created_at DESC");
$stmt->execute($params);
$daftar = $stmt->fetchAll();

$pageTitle = 'Manajemen Pelanggan';
$active    = 'pelanggan';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k">
  <div class="card-head">
    <form class="d-flex gap-2" method="get">
      <input type="text" name="q" class="form-control" style="width:250px" placeholder="Cari nama / email / no. HP…" value="<?= e($q) ?>">
      <button class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
      <?php if ($q): ?><a href="pelanggan.php" class="btn btn-light">Reset</a><?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-k">
      <thead>
        <tr><th>Nama</th><th>Email</th><th>No. HP</th><th>Jumlah Pesanan</th><th>Total Belanja</th><th>Terdaftar</th><th class="text-end">Aksi</th></tr>
      </thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="7" class="text-center text-secondary py-4">Tidak ada pelanggan.</td></tr>
      <?php else: foreach ($daftar as $pl): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="avatar" style="width:34px;height:34px;font-size:14px"><?= strtoupper(substr($pl['nama'], 0, 1)) ?></span>
              <span class="fw-semibold"><?= e($pl['nama']) ?></span>
            </div>
          </td>
          <td><?= e($pl['email'] ?: '-') ?></td>
          <td><?= e($pl['no_hp'] ?: '-') ?></td>
          <td class="angka"><?= (int) $pl['jumlah_pesanan'] ?></td>
          <td class="angka fw-semibold"><?= rupiah($pl['total_belanja']) ?></td>
          <td class="text-secondary" style="font-size:13px"><?= tanggal_id($pl['created_at']) ?></td>
          <td class="text-end">
            <a href="pesanan.php?q=<?= urlencode($pl['nama']) ?>" class="btn btn-sm btn-light" title="Lihat pesanan"><i class="bi bi-receipt"></i></a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPelanggan"
                    onclick='formEdit(<?= json_encode([
                        'id' => $pl['id'], 'nama' => $pl['nama'], 'email' => $pl['email'], 'no_hp' => $pl['no_hp'],
                    ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus pelanggan <?= e($pl['nama']) ?>?')">
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= $pl['id'] ?>">
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
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
          <label class="form-label">Email</label>
          <input type="email" name="email" id="f_email" class="form-control">
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
  document.getElementById('f_email').value = p.email || '';
  document.getElementById('f_nohp').value = p.no_hp || '';
}
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
