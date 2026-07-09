<?php
/** Variabel yang diharapkan: $pageTitle (string), $active (string kunci menu). */
$jmlMenunggu = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();
$menuSidebar = [
    'dashboard'    => ['index.php',        'bi-grid-1x2',    'Dashboard'],
    'pesanan_baru' => ['pesanan_baru.php', 'bi-cart-plus',   'Pesanan Baru'],
    'pesanan'      => ['pesanan.php',      'bi-receipt',     'Pesanan'],
    'pembayaran'   => ['pembayaran.php',   'bi-credit-card', 'Pembayaran'],
    'pendapatan'   => ['pendapatan.php',   'bi-wallet2',     'Pendapatan'],
    'pelanggan'    => ['pelanggan.php',    'bi-people',      'Pelanggan'],
];
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — Kasir <?= e($namaToko) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="../assets/css/admin.css?v=2" rel="stylesheet">
</head>
<body>
<div class="layout" id="layoutKasir">
<script>/* pulihkan posisi sidebar sebelum halaman tampil, biar tidak kedip */
if (localStorage.getItem('kasirSidebarMini') === '1') document.getElementById('layoutKasir').classList.add('sidebar-mini');</script>

  <!-- ================= SIDEBAR ================= -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <?php if (!empty($pengaturan['logo'])): ?>
        <img src="../uploads/toko/<?= e($pengaturan['logo']) ?>" alt="Logo" class="brand-logo">
      <?php else: ?>
        <span class="brand-icon"><i class="bi bi-receipt-cutoff"></i></span>
      <?php endif; ?>
      <span class="brand-name"><?= e($namaToko) ?> · Kasir</span>
    </div>
    <nav class="sidebar-nav">
      <?php foreach ($menuSidebar as $key => [$href, $icon, $label]): ?>
        <a href="<?= $href ?>" class="nav-item <?= $active === $key ? 'active' : '' ?>">
          <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
          <?php if ($key === 'pesanan'): ?>
            <span class="badge rounded-pill bg-danger ms-auto <?= $jmlMenunggu > 0 ? '' : 'd-none' ?>" id="badgePesanan" style="font-size:11px"><?= $jmlMenunggu ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
      <div class="sidebar-sep"></div>
      <a href="logout.php" class="nav-item nav-logout" onclick="return confirm('Keluar dari kasir?')">
        <i class="bi bi-box-arrow-right"></i><span>Logout</span>
      </a>
    </nav>
  </aside>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <!-- ================= KONTEN ================= -->
  <div class="main">
    <header class="topbar">
      <button class="btn-icon d-lg-none" id="btnSidebar" aria-label="Menu"><i class="bi bi-list"></i></button>
      <button class="btn-icon d-none d-lg-inline-flex" id="btnSidebarMini" aria-label="Buka/tutup sidebar" title="Buka/tutup sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
      <div class="topbar-title"><?= e($pageTitle) ?></div>
      <div class="topbar-right">

        <button class="btn-icon" id="btnSuara" title="Suara notifikasi pesanan masuk (klik untuk nyalakan/matikan)">
          <i class="bi bi-volume-mute"></i>
        </button>

        <!-- Notifikasi -->
        <div class="dropdown">
          <button class="btn-icon position-relative" data-bs-toggle="dropdown" aria-label="Notifikasi" id="btnNotif">
            <i class="bi bi-bell"></i>
            <span class="notif-badge d-none" id="notifBadge">0</span>
          </button>
          <div class="dropdown-menu dropdown-menu-end notif-menu">
            <div class="notif-head">
              <span>Notifikasi</span>
              <button class="notif-clear" id="btnNotifBaca">Tandai dibaca</button>
            </div>
            <div id="notifList" class="notif-list">
              <div class="notif-empty">Memuat…</div>
            </div>
          </div>
        </div>

        <div class="topbar-user">
          <span class="avatar"><?= strtoupper(substr($namaKasir, 0, 1)) ?></span>
          <span class="d-none d-md-inline"><?= e($namaKasir) ?></span>
        </div>
      </div>
    </header>

    <main class="content">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['tipe'] === 'sukses' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
          <?= e($flash['pesan']) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
