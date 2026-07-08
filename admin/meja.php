<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    if ($aksi === 'tambah') {
        $nomor = trim($_POST['nomor_meja'] ?? '');
        if ($nomor === '') {
            set_flash('gagal', 'Nomor meja wajib diisi.');
        } else {
            $kode = bin2hex(random_bytes(8));
            $db->prepare('INSERT INTO meja (nomor_meja, kode, status) VALUES (?,?,?)')
               ->execute([$nomor, $kode, 'aktif']);
            set_flash('sukses', 'Meja ' . $nomor . ' ditambahkan. QR-nya otomatis dibuat.');
        }
    }

    if ($aksi === 'simpan') {
        $nomor = trim($_POST['nomor_meja'] ?? '');
        if ($nomor === '') {
            set_flash('gagal', 'Nomor meja wajib diisi.');
        } else {
            $db->prepare('UPDATE meja SET nomor_meja = ? WHERE id = ?')->execute([$nomor, $id]);
            set_flash('sukses', 'Meja diperbarui.');
        }
    }

    if ($aksi === 'toggle') {
        $db->prepare("UPDATE meja SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([$id]);
        set_flash('sukses', 'Status meja diubah.');
    }

    if ($aksi === 'ulang_qr') {
        $db->prepare('UPDATE meja SET kode = ? WHERE id = ?')->execute([bin2hex(random_bytes(8)), $id]);
        $lama = dirname(__DIR__) . '/uploads/qrcode/meja-' . $id . '.png';
        if (is_file($lama)) @unlink($lama);
        set_flash('sukses', 'Kode QR meja diperbarui — QR lama tidak berlaku lagi.');
    }

    if ($aksi === 'hapus') {
        $cek = $db->prepare('SELECT COUNT(*) FROM pesanan WHERE meja_id = ?');
        $cek->execute([$id]);
        if ($cek->fetchColumn() > 0) {
            set_flash('gagal', 'Meja punya riwayat pesanan dan tidak bisa dihapus. Nonaktifkan saja.');
        } else {
            $db->prepare('DELETE FROM meja WHERE id = ?')->execute([$id]);
            $file = dirname(__DIR__) . '/uploads/qrcode/meja-' . $id . '.png';
            if (is_file($file)) @unlink($file);
            set_flash('sukses', 'Meja dihapus.');
        }
    }

    header('Location: meja.php');
    exit;
}

$daftar = $db->query('SELECT * FROM meja ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja')->fetchAll();

$pageTitle = 'Manajemen Meja & QR';
$active    = 'meja';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card-k mb-3 no-print">
  <div class="card-head">
    <span>Daftar Meja</span>
    <div class="d-flex gap-2">
      <a href="scan_test.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-camera me-1"></i>Uji Scan Kamera</a>
      <a href="meja_cetak.php" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer me-1"></i>Cetak Semua QR</a>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="bi bi-plus-lg me-1"></i>Tambah Meja</button>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-k align-middle">
      <thead><tr><th>Meja</th><th>Status</th><th>QR Code</th><th class="text-end">Aksi</th></tr></thead>
      <tbody>
      <?php if (!$daftar): ?>
        <tr><td colspan="4" class="text-center text-secondary py-4">Belum ada meja.</td></tr>
      <?php else: foreach ($daftar as $m):
        $file    = dirname(__DIR__) . '/uploads/qrcode/meja-' . $m['id'] . '.png';
        $adaFile = is_file($file);
        $url     = url_meja($m['kode']);
      ?>
        <tr>
          <td class="fw-bold" style="font-size:15px">Meja <?= e($m['nomor_meja']) ?></td>
          <td>
            <span class="badge-status <?= $m['status'] === 'aktif' ? 'badge-selesai' : 'badge-batal' ?>">
              <?= $m['status'] === 'aktif' ? 'Aktif' : 'Nonaktif' ?>
            </span>
          </td>
          <td>
            <div class="qr-cell" data-id="<?= $m['id'] ?>" data-url="<?= e($url) ?>">
              <img class="qr-img" width="72" height="72" style="border:1px solid var(--border);border-radius:8px;background:#fff"
                   src="<?= $adaFile ? '../uploads/qrcode/meja-' . $m['id'] . '.png?v=' . filemtime($file) : '' ?>"
                   <?= $adaFile ? '' : 'data-belum="1"' ?> alt="QR Meja <?= e($m['nomor_meja']) ?>">
            </div>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-primary" href="<?= e($url) ?>" target="_blank" rel="noopener" title="Buka halaman pesan meja ini (tanpa perlu scan)">
              <i class="bi bi-box-arrow-up-right"></i> Buka
            </a>
            <a class="btn btn-sm btn-light qr-unduh" href="<?= $adaFile ? '../uploads/qrcode/meja-' . $m['id'] . '.png' : '#' ?>"
               download="meja-<?= e($m['nomor_meja']) ?>-qr.png" title="Unduh PNG"><i class="bi bi-download"></i></a>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit"
                    onclick='formEdit(<?= json_encode(['id' => $m['id'], 'nomor_meja' => $m['nomor_meja']], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="Edit nomor">
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" class="d-inline">
              <input type="hidden" name="aksi" value="toggle">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="btn btn-sm btn-light" title="<?= $m['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                <i class="bi <?= $m['status'] === 'aktif' ? 'bi-toggle2-on' : 'bi-toggle2-off' ?>"></i>
              </button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Buat ulang kode QR Meja <?= e($m['nomor_meja']) ?>? QR lama tidak berlaku lagi.')">
              <input type="hidden" name="aksi" value="ulang_qr">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="btn btn-sm btn-light" title="Buat ulang QR"><i class="bi bi-arrow-repeat"></i></button>
            </form>
            <form method="post" class="d-inline" onsubmit="return confirm('Hapus Meja <?= e($m['nomor_meja']) ?>?')">
              <input type="hidden" name="aksi" value="hapus">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <button class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="pesan-info no-print" style="background:var(--primary-soft, #eaf2fc);color:var(--primary-dark, #1a4d8f);border-radius:12px;padding:12px 16px;font-size:13.5px">
  <i class="bi bi-info-circle"></i>
  QR otomatis dibuat & disimpan ke <code>uploads/qrcode/</code> begitu halaman ini dimuat. Tinggal klik <b>Unduh</b> per meja atau <b>Cetak Semua QR</b> untuk ditempel di meja.
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="post">
      <div class="modal-header">
        <h5 class="modal-title fw-bold">Tambah Meja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="tambah">
        <div class="mb-3">
          <label class="form-label">Nomor Meja</label>
          <input type="text" name="nomor_meja" class="form-control" placeholder="Contoh: 11 atau VIP-1" required>
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
        <h5 class="modal-title fw-bold">Edit Meja</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="id" id="f_id">
        <div class="mb-3">
          <label class="form-label">Nomor Meja</label>
          <input type="text" name="nomor_meja" id="f_nomor" class="form-control" required>
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
function formEdit(m) {
  document.getElementById('f_id').value = m.id;
  document.getElementById('f_nomor').value = m.nomor_meja;
}
</script>

<script src="../assets/js/qrcode.min.js"></script>
<script>
// Buat & simpan QR ke server untuk meja yang belum punya file QR.
document.querySelectorAll('.qr-cell').forEach(cell => {
  const img = cell.querySelector('.qr-img');
  if (!img.hasAttribute('data-belum')) return;

  const id  = cell.dataset.id;
  const url = cell.dataset.url;
  const tmp = document.createElement('div');
  tmp.style.display = 'none';
  document.body.appendChild(tmp);

  new QRCode(tmp, { text: url, width: 300, height: 300, correctLevel: QRCode.CorrectLevel.M });

  setTimeout(() => {
    const canvas = tmp.querySelector('canvas');
    if (!canvas) return;
    const dataUrl = canvas.toDataURL('image/png');
    img.src = dataUrl;
    fetch('api/simpan_qr.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ id, data: dataUrl }).toString()
    }).then(r => r.json()).then(res => {
      if (res.ok) {
        img.src = '../' + res.url + '?v=' + Date.now();
        const unduh = cell.closest('tr').querySelector('.qr-unduh');
        unduh.href = '../' + res.url;
      }
    });
    tmp.remove();
  }, 60);
});
</script>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
