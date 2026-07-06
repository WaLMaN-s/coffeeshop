<?php
require_once __DIR__ . '/includes/site_init.php';

// Sudah masuk? Arahkan sesuai peran.
if (!empty($_SESSION['admin_id'])) {
    header('Location: admin/index.php');
    exit;
}
if (pelanggan_masuk()) {
    header('Location: akun.php');
    exit;
}

$lanjut = $_GET['lanjut'] ?? $_POST['lanjut'] ?? '';
// Hanya izinkan redirect ke file lokal (hindari open redirect)
if (!preg_match('/^[a-z_]+\.php$/', $lanjut)) $lanjut = '';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identitas = trim($_POST['identitas'] ?? '');
    $password  = $_POST['password'] ?? '';

    // 1) Coba sebagai ADMIN (username)
    $stmt = $db->prepare('SELECT * FROM admin WHERE username = ?');
    $stmt->execute([$identitas]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        header('Location: admin/index.php');
        exit;
    }

    // 2) Coba sebagai PELANGGAN (email)
    $stmt = $db->prepare('SELECT * FROM pelanggan WHERE email = ?');
    $stmt->execute([$identitas]);
    $pl = $stmt->fetch();
    if ($pl && $pl['password'] && password_verify($password, $pl['password'])) {
        session_regenerate_id(true);
        $_SESSION['pelanggan_id']   = $pl['id'];
        $_SESSION['pelanggan_nama'] = $pl['nama'];
        header('Location: ' . ($lanjut ?: 'index.php'));
        exit;
    }

    $error = 'Email/username atau password salah.';
}

$pageTitle = 'Masuk';
$activeNav = 'akun';
require __DIR__ . '/includes/site_top.php';
?>

<div class="auth-wrap" style="padding:0">
  <div class="auth-kartu" style="margin-top:20px">
    <h1 style="font-size:20px;font-weight:800;margin:0 0 4px">Masuk</h1>
    <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 20px">
      Satu pintu untuk pelanggan &amp; admin — akun admin otomatis diarahkan ke panel admin.
    </p>

    <?php if ($error): ?><div class="pesan-info pesan-gagal" style="margin-top:0"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <?php if ($lanjut): ?><input type="hidden" name="lanjut" value="<?= e($lanjut) ?>"><?php endif; ?>
      <div class="form-grup">
        <label>Email (pelanggan) / Username (admin)</label>
        <input type="text" name="identitas" class="input" required autofocus
               placeholder="email@kamu.com atau username" value="<?= e($_POST['identitas'] ?? '') ?>">
      </div>
      <div class="form-grup">
        <label>Password</label>
        <input type="password" name="password" class="input" required>
      </div>
      <button type="submit" class="btn-utama btn-blok" style="margin-top:6px">Masuk</button>
    </form>

    <p style="text-align:center;font-size:13.5px;margin:18px 0 0;color:var(--ink-2)">
      Belum punya akun?
      <a href="daftar.php<?= $lanjut ? '?lanjut=' . e($lanjut) : '' ?>" style="color:var(--primary);font-weight:700">Daftar</a>
    </p>
  </div>
</div>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
