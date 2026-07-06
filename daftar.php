<?php
require_once __DIR__ . '/includes/site_init.php';

if (pelanggan_masuk()) {
    header('Location: akun.php');
    exit;
}

$lanjut = $_GET['lanjut'] ?? $_POST['lanjut'] ?? '';
if (!preg_match('/^[a-z_]+\.php$/', $lanjut)) $lanjut = '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $noHp     = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nama === '' || $email === '' || $password === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $cek = $db->prepare('SELECT COUNT(*) FROM pelanggan WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetchColumn() > 0) {
            $error = 'Email sudah terdaftar — silakan masuk.';
        } else {
            $db->prepare('INSERT INTO pelanggan (nama, email, no_hp, password) VALUES (?,?,?,?)')
               ->execute([$nama, $email, $noHp ?: null, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['pelanggan_id']   = (int) $db->lastInsertId();
            $_SESSION['pelanggan_nama'] = $nama;
            set_flash('sukses', 'Selamat datang, ' . $nama . '!');
            header('Location: ' . ($lanjut ?: 'index.php'));
            exit;
        }
    }
}

$pageTitle = 'Daftar';
$activeNav = 'akun';
require __DIR__ . '/includes/site_top.php';
?>

<div class="auth-wrap" style="padding:0">
  <div class="auth-kartu" style="margin-top:20px">
    <h1 style="font-size:20px;font-weight:800;margin:0 0 4px">Daftar Akun</h1>
    <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 20px">Sekali daftar, pesan kapan saja.</p>

    <?php if ($error): ?><div class="pesan-info pesan-gagal" style="margin-top:0"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <?php if ($lanjut): ?><input type="hidden" name="lanjut" value="<?= e($lanjut) ?>"><?php endif; ?>
      <div class="form-grup">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" class="input" required value="<?= e($_POST['nama'] ?? '') ?>">
      </div>
      <div class="form-grup">
        <label>Email</label>
        <input type="email" name="email" class="input" required value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="form-grup">
        <label>No. HP (opsional)</label>
        <input type="tel" name="no_hp" class="input" value="<?= e($_POST['no_hp'] ?? '') ?>">
      </div>
      <div class="form-grup">
        <label>Password <span style="font-weight:400;color:var(--ink-muted)">(min. 6 karakter)</span></label>
        <input type="password" name="password" class="input" required minlength="6">
      </div>
      <button type="submit" class="btn-utama btn-blok" style="margin-top:6px">Daftar</button>
    </form>

    <p style="text-align:center;font-size:13.5px;margin:18px 0 0;color:var(--ink-2)">
      Sudah punya akun?
      <a href="masuk.php<?= $lanjut ? '?lanjut=' . e($lanjut) : '' ?>" style="color:var(--primary);font-weight:700">Masuk</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
