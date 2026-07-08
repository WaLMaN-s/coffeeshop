<?php
require_once __DIR__ . '/includes/site_init.php';

$kode = trim($_GET['kode'] ?? $_POST['kode'] ?? '');

// Sudah punya sesi dan tidak sedang pindah meja -> langsung ke menu.
if ($kode === '' && meja_aktif()) {
    header('Location: index.php');
    exit;
}

$meja  = null;
$error = '';
if ($kode !== '') {
    $stmt = $db->prepare("SELECT * FROM meja WHERE kode = ? AND status = 'aktif'");
    $stmt->execute([$kode]);
    $meja = $stmt->fetch();
    if (!$meja) {
        $error = 'Kode QR tidak dikenali atau meja sedang tidak aktif. Coba scan ulang atau panggil staf kami.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $meja) {
    $nama = trim($_POST['nama'] ?? '');
    if ($nama === '') {
        $error = 'Nama wajib diisi dulu ya.';
    } else {
        session_regenerate_id(true);
        $_SESSION['meja'] = [
            'meja_id'    => (int) $meja['id'],
            'nomor_meja' => $meja['nomor_meja'],
            'nama'       => $nama,
            'sesi'       => bin2hex(random_bytes(16)),
        ];
        $_SESSION['keranjang'] = [];
        set_flash('sukses', 'Selamat datang, ' . $nama . '! Kamu di Meja ' . $meja['nomor_meja'] . '.');
        header('Location: index.php');
        exit;
    }
}

$pengaturan = get_pengaturan($db);
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2a78d6">
<title><?= e($namaToko) ?> — Pesan di Meja</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/site.css" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-kartu" style="margin-top:36px;text-align:center">
    <?php if (!empty($pengaturan['logo'])): ?>
      <img src="uploads/toko/<?= e($pengaturan['logo']) ?>" alt="Logo" style="width:56px;height:56px;border-radius:16px;object-fit:cover;margin-bottom:10px">
    <?php else: ?>
      <span class="logo-ikon" style="width:56px;height:56px;font-size:26px;border-radius:16px;margin:0 auto 10px;display:flex">
        <i class="bi bi-cup-hot-fill"></i>
      </span>
    <?php endif; ?>
    <h1 style="font-size:19px;font-weight:800;margin:0 0 4px"><?= e($namaToko) ?></h1>

    <?php if (!$meja): ?>
      <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 22px">
        Pesan langsung dari mejamu.<br>Scan QR code yang ada di atas meja untuk mulai.
      </p>
      <div style="background:var(--bg);border-radius:14px;padding:18px;margin-bottom:18px">
        <i class="bi bi-qrcode" style="font-size:34px;color:var(--primary)"></i>
        <div style="font-size:12.5px;color:var(--ink-muted);margin-top:8px">Arahkan kamera HP ke QR code di meja kamu</div>
      </div>

      <?php if ($error): ?><div class="pesan-info pesan-gagal" style="text-align:left"><?= e($error) ?></div><?php endif; ?>

      <details style="text-align:left;margin-top:6px">
        <summary style="cursor:pointer;font-size:13px;font-weight:600;color:var(--primary)">QR tidak bisa discan? Masukkan kode meja</summary>
        <form method="get" style="margin-top:12px">
          <div class="form-grup" style="margin-bottom:10px">
            <input type="text" name="kode" class="input" placeholder="Kode meja (lihat di stiker meja)" required
                   value="<?= e($kode) ?>">
          </div>
          <button type="submit" class="btn-utama btn-blok">Lanjut</button>
        </form>
      </details>
    <?php else: ?>
      <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 20px">
        Kamu di <b style="color:var(--primary-dark)">Meja <?= e($meja['nomor_meja']) ?></b>. Masukkan nama untuk mulai memesan.
      </p>
      <?php if ($error): ?><div class="pesan-info pesan-gagal" style="text-align:left"><?= e($error) ?></div><?php endif; ?>
      <form method="post" style="text-align:left">
        <input type="hidden" name="kode" value="<?= e($kode) ?>">
        <div class="form-grup">
          <label>Nama Kamu</label>
          <input type="text" name="nama" class="input" placeholder="Contoh: Budi" required autofocus maxlength="100"
                 value="<?= e($_POST['nama'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-utama btn-blok"><i class="bi bi-cup-hot"></i> Mulai Pesan</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
