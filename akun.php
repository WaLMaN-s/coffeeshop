<?php
require_once __DIR__ . '/includes/site_init.php';

if (!pelanggan_masuk()) {
    header('Location: masuk.php?lanjut=akun.php');
    exit;
}

$plId = (int) $_SESSION['pelanggan_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'profil') {
        $nama = trim($_POST['nama'] ?? '');
        $noHp = trim($_POST['no_hp'] ?? '');
        if ($nama === '') {
            set_flash('gagal', 'Nama wajib diisi.');
        } else {
            $db->prepare('UPDATE pelanggan SET nama = ?, no_hp = ? WHERE id = ?')->execute([$nama, $noHp ?: null, $plId]);
            $_SESSION['pelanggan_nama'] = $nama;
            set_flash('sukses', 'Profil diperbarui.');
        }
    }

    if ($aksi === 'password') {
        $lama = $_POST['password_lama'] ?? '';
        $baru = $_POST['password_baru'] ?? '';
        $stmt = $db->prepare('SELECT password FROM pelanggan WHERE id = ?');
        $stmt->execute([$plId]);
        $hash = $stmt->fetchColumn();
        if (!$hash || !password_verify($lama, $hash)) {
            set_flash('gagal', 'Password lama salah.');
        } elseif (strlen($baru) < 6) {
            set_flash('gagal', 'Password baru minimal 6 karakter.');
        } else {
            $db->prepare('UPDATE pelanggan SET password = ? WHERE id = ?')
               ->execute([password_hash($baru, PASSWORD_DEFAULT), $plId]);
            set_flash('sukses', 'Password berhasil diganti.');
        }
    }

    header('Location: akun.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM pelanggan WHERE id = ?');
$stmt->execute([$plId]);
$saya = $stmt->fetch();

$pageTitle = 'Akun Saya';
$activeNav = 'akun';
require __DIR__ . '/includes/site_top.php';
?>

<div class="kartu" style="margin-top:18px;display:flex;align-items:center;gap:14px">
  <span class="logo-ikon" style="width:52px;height:52px;font-size:22px;border-radius:50%">
    <?= strtoupper(substr($saya['nama'], 0, 1)) ?>
  </span>
  <div style="flex:1">
    <div style="font-weight:800;font-size:16px"><?= e($saya['nama']) ?></div>
    <div style="font-size:12.5px;color:var(--ink-muted)"><?= e($saya['email']) ?></div>
  </div>
  <a href="keluar.php" class="btn-keluar" style="margin-left:0">
    <i class="bi bi-box-arrow-right"></i> Keluar
  </a>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:12px">Ubah Profil</div>
  <form method="post">
    <input type="hidden" name="aksi" value="profil">
    <div class="form-grup">
      <label>Nama</label>
      <input type="text" name="nama" class="input" required value="<?= e($saya['nama']) ?>">
    </div>
    <div class="form-grup">
      <label>No. HP</label>
      <input type="tel" name="no_hp" class="input" value="<?= e($saya['no_hp'] ?? '') ?>">
    </div>
    <button class="btn-utama">Simpan</button>
  </form>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:12px">Ganti Password</div>
  <form method="post">
    <input type="hidden" name="aksi" value="password">
    <div class="form-grup">
      <label>Password Lama</label>
      <input type="password" name="password_lama" class="input" required>
    </div>
    <div class="form-grup">
      <label>Password Baru <span style="font-weight:400;color:var(--ink-muted)">(min. 6 karakter)</span></label>
      <input type="password" name="password_baru" class="input" required minlength="6">
    </div>
    <button class="btn-utama">Ganti Password</button>
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
