<?php
require_once dirname(__DIR__) . '/config/config.php';

if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $db->prepare('SELECT * FROM admin WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id']   = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        header('Location: index.php');
        exit;
    }
    $error = 'Username atau password salah.';
}

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login Admin — <?= e($namaToko) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/admin.css" rel="stylesheet">
<style>
  body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .login-wrap { width: 100%; max-width: 380px; padding: 20px; }
  .login-card { background: var(--surface); border: 1px solid var(--border); border-radius: 18px; padding: 32px 28px; }
</style>
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <div class="text-center mb-4">
      <?php if (!empty($pengaturan['logo'])): ?>
        <img src="../uploads/toko/<?= e($pengaturan['logo']) ?>" alt="Logo" style="width:52px;height:52px;border-radius:14px;object-fit:cover;margin-bottom:10px">
      <?php else: ?>
        <span class="brand-icon" style="width:52px;height:52px;font-size:24px;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;background:var(--primary);color:#fff;margin-bottom:10px">
          <i class="bi bi-cup-hot-fill"></i>
        </span>
      <?php endif; ?>
      <h1 class="fw-bold" style="font-size:19px;margin:0">Login Admin</h1>
      <p class="text-secondary" style="font-size:13px;margin:4px 0 0"><?= e($namaToko) ?></p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger py-2" style="font-size:13.5px"><?= e($error) ?></div><?php endif; ?>

    <form method="post">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>
  </div>
  <p class="text-center text-secondary mt-3" style="font-size:12.5px">
    Halaman pelanggan? <a href="../meja.php" style="color:var(--primary);font-weight:600">Pesan lewat QR meja</a>
  </p>
</div>
</body>
</html>
