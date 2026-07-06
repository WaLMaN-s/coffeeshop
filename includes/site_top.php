<?php
/** Variabel: $pageTitle, $activeNav ('beranda'|'keranjang'|'pesanan'|'akun') */
$flash      = get_flash();
$jmlKrj     = jumlah_item_keranjang();
$navLinks = [
    'beranda'   => ['index.php',        'bi-house-door', 'Beranda'],
    'keranjang' => ['keranjang.php',    'bi-bag',        'Keranjang'],
    'pesanan'   => ['pesanan_saya.php', 'bi-receipt',    'Pesanan'],
    'akun'      => ['akun.php',         'bi-person',     'Akun'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2a78d6">
<title><?= e($pageTitle) ?> — <?= e($namaToko) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/site.css" rel="stylesheet">
</head>
<body>

<header class="site-header">
  <div class="wrap inner">
    <a href="index.php" style="display:flex;align-items:center;gap:11px">
      <?php if (!empty($pengaturan['logo'])): ?>
        <img src="uploads/toko/<?= e($pengaturan['logo']) ?>" alt="Logo" class="logo-toko">
      <?php else: ?>
        <span class="logo-ikon"><i class="bi bi-cup-hot-fill"></i></span>
      <?php endif; ?>
      <span>
        <span class="nama-toko"><?= e($namaToko) ?></span>
        <?php if (!empty($pengaturan['jam_operasional'])): ?>
          <div class="jam-toko"><i class="bi bi-clock"></i> <?= e($pengaturan['jam_operasional']) ?></div>
        <?php endif; ?>
      </span>
    </a>
    <nav class="nav-desktop">
      <?php foreach ($navLinks as $key => [$href, $icon, $label]): ?>
        <a href="<?= $href ?>" class="<?= $activeNav === $key ? 'aktif' : '' ?>">
          <i class="bi <?= $icon ?>"></i><?= $label ?>
          <?php if ($key === 'keranjang' && $jmlKrj): ?>
            <span class="badge-keranjang" style="position:static" id="badgeKrjTop"><?= $jmlKrj ?></span>
          <?php elseif ($key === 'keranjang'): ?>
            <span class="badge-keranjang" style="position:static;display:none" id="badgeKrjTop"></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <?php if (pelanggan_masuk()): ?>
      <a href="keluar.php" class="btn-keluar" title="Keluar (<?= e($_SESSION['pelanggan_nama']) ?>)">
        <i class="bi bi-box-arrow-right"></i><span class="btn-keluar-teks">Keluar</span>
      </a>
    <?php else: ?>
      <a href="masuk.php" class="btn-keluar btn-masuk" title="Masuk">
        <i class="bi bi-box-arrow-in-right"></i><span class="btn-keluar-teks">Masuk</span>
      </a>
    <?php endif; ?>
  </div>
</header>

<div class="wrap">
<?php if ($flash): ?>
  <div class="pesan-info <?= $flash['tipe'] === 'sukses' ? 'pesan-sukses' : 'pesan-gagal' ?>"><?= e($flash['pesan']) ?></div>
<?php endif; ?>
