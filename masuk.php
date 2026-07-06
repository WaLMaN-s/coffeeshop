<?php
require_once __DIR__ . '/includes/site_init.php';

// Pelanggan yang sudah masuk tak perlu login lagi.
// Sesi admin TIDAK memaksa redirect — cukup ditampilkan sebagai info,
// supaya halaman pelanggan tetap bisa diakses tanpa terlempar ke dashboard.
if (pelanggan_masuk()) {
    header('Location: akun.php');
    exit;
}
$sedangAdmin = !empty($_SESSION['admin_id']);

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
    <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 20px">Masuk untuk mulai memesan.</p>

    <?php if ($sedangAdmin): ?>
      <div class="pesan-info" style="margin-top:0;background:var(--primary-soft);color:var(--primary-dark)">
        Kamu sedang masuk sebagai <b>admin</b>.
        <a href="admin/index.php" style="font-weight:700;color:var(--primary-dark);text-decoration:underline">Buka Panel Admin</a>
        atau
        <a href="admin/logout.php" style="font-weight:700;color:var(--primary-dark);text-decoration:underline">keluar admin</a>,
        lalu masuk sebagai pelanggan di bawah.
      </div>
    <?php endif; ?>

    <?php if ($error): ?><div class="pesan-info pesan-gagal" style="margin-top:0"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <?php if ($lanjut): ?><input type="hidden" name="lanjut" value="<?= e($lanjut) ?>"><?php endif; ?>
      <div class="form-grup">
        <label>Email atau Username</label>
        <input type="text" name="identitas" class="input" required autofocus
               value="<?= e($_POST['identitas'] ?? '') ?>">
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
