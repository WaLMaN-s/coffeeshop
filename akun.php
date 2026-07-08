<?php
require_once __DIR__ . '/includes/site_init.php';

if (!meja_aktif()) {
    header('Location: meja.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'ganti_nama') {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        set_flash('gagal', 'Nama wajib diisi.');
    } else {
        $_SESSION['meja']['nama'] = $nama;
        set_flash('sukses', 'Nama diperbarui.');
    }
    header('Location: akun.php');
    exit;
}

$pageTitle = 'Akun Saya';
$activeNav = 'akun';
require __DIR__ . '/includes/site_top.php';
?>

<div class="kartu" style="margin-top:18px;display:flex;align-items:center;gap:14px">
  <span class="logo-ikon" style="width:52px;height:52px;font-size:22px;border-radius:50%">
    <?= strtoupper(substr($_SESSION['meja']['nama'], 0, 1)) ?>
  </span>
  <div style="flex:1">
    <div style="font-weight:800;font-size:16px"><?= e($_SESSION['meja']['nama']) ?></div>
    <div style="font-size:12.5px;color:var(--ink-muted)"><i class="bi bi-table"></i> Meja <?= e($_SESSION['meja']['nomor_meja']) ?></div>
  </div>
  <a href="keluar.php" class="btn-keluar" style="margin-left:0" onclick="return confirm('Akhiri sesi meja ini?')">
    <i class="bi bi-box-arrow-right"></i> Keluar
  </a>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:12px">Ubah Nama</div>
  <form method="post">
    <input type="hidden" name="aksi" value="ganti_nama">
    <div class="form-grup">
      <label>Nama</label>
      <input type="text" name="nama" class="input" required maxlength="100" value="<?= e($_SESSION['meja']['nama']) ?>">
    </div>
    <button class="btn-utama">Simpan</button>
  </form>
</div>

<?php if (!empty($pengaturan['whatsapp'])): ?>
<a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $pengaturan['whatsapp'])) ?>" target="_blank" rel="noopener"
   class="kartu" style="display:flex;align-items:center;gap:12px;margin-top:12px">
  <i class="bi bi-whatsapp" style="font-size:22px;color:#0ca30c"></i>
  <div>
    <div style="font-weight:700;font-size:13.5px">Hubungi Kami</div>
    <div style="font-size:12px;color:var(--ink-muted)">Ada kendala? Chat via WhatsApp</div>
  </div>
  <i class="bi bi-chevron-right" style="margin-left:auto;color:var(--ink-muted)"></i>
</a>
<?php endif; ?>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
