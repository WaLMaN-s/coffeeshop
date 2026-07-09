<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'tambah') {
        $username = trim($_POST['username'] ?? '');
        $nama     = trim($_POST['nama'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username === '' || $nama === '' || strlen($password) < 6) {
            set_flash('gagal', 'Username, nama wajib diisi, dan password minimal 6 karakter.');
        } else {
            $cek = $db->prepare('SELECT COUNT(*) FROM kasir WHERE username = ?');
            $cek->execute([$username]);
            if ($cek->fetchColumn() > 0) {
                set_flash('gagal', 'Username sudah dipakai.');
            } else {
                $db->prepare('INSERT INTO kasir (username, password, nama) VALUES (?,?,?)')
                   ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama]);
                set_flash('sukses', 'Akun kasir ' . $nama . ' dibuat.');
            }
        }
    }

    if ($aksi === 'simpan') {
        $nama     = trim($_POST['nama'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($nama === '') {
            set_flash('gagal', 'Nama wajib diisi.');
        } else {
            if ($password !== '') {
                if (strlen($password) < 6) {
                    set_flash('gagal', 'Password baru minimal 6 karakter.');
                    header('Location: kasir.php');
                    exit;
                }
                $db->prepare('UPDATE kasir SET nama = ?, password = ? WHERE id = ?')
                   ->execute([$nama, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $db->prepare('UPDATE kasir SET nama = ? WHERE id = ?')->execute([$nama, $id]);
            }
            set_flash('sukses', 'Akun kasir diperbarui.');
        }
    }

    if ($aksi === 'hapus') {
        $db->prepare('DELETE FROM kasir WHERE id = ?')->execute([$id]);
        set_flash('sukses', 'Akun kasir dihapus.');
    }

    header('Location: kasir.php');
    exit;
}

$daftar = $db->query('SELECT * FROM kasir ORDER BY nama')->fetchAll();

$pageTitle = 'Akun Kasir';
$active    = 'kasir';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k">
  <div class="card-head">
    <span>Daftar Akun Kasir</span>
    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Kasir</button>
  </div>

  <div class="table-responsive">
    <table class="table table-k align-middle">
      <thead><tr><th>Nama</th><th>Username</th><th>Dibuat</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada akun kasir.</td></tr>
      <?php else: foreach ($daftar as $k): ?>
        <tr>
          <td class="fw-semibold"><?= e($k['nama']) ?></td>
          <td><?= e($k['username']) ?></td>
          <td class="text-secondary" style="font-size:13px"><?= tanggal_id($k['created_at']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit"
                    onclick='formEdit(<?= json_encode(['id' => $k['id'], 'nama' => $k['nama'], 'username' => $k['username']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus akun kasir <?= e($k['nama']) ?>?')">
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= $k['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="pesan-info no-print" style="background:var(--primary-soft, #eaf2fc);color:var(--primary-dark, #1a4d8f);border-radius:12px;padding:12px 16px;font-size:13.5px;margin-top:14px">
  <i class="bi bi-info-circle"></i>
  Kasir login terpisah dari admin di <code><?= e(rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/')) ?>/../kasir/login.php</code>
  — kasir cuma bisa lihat & proses Pesanan, Pembayaran, dan Pelanggan (tidak bisa ubah Menu/Kategori/Pengaturan).
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Akun Kasir</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="tambah">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input type="text" name="nama" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required minlength="6">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Edit Akun Kasir</h5>
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
          <label class="form-label">Username</label>
          <input type="text" id="f_username" class="form-control" disabled>
        </div>
        <div class="mb-3">
          <label class="form-label">Password Baru (opsional)</label>
          <input type="password" name="password" class="form-control" minlength="6" placeholder="Kosongkan jika tidak diganti">
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
function formEdit(k) {
  document.getElementById('f_id').value = k.id;
  document.getElementById('f_nama').value = k.nama;
  document.getElementById('f_username').value = k.username;
}
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
