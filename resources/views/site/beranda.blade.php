<?php
$pengaturan = get_pengaturan(db());
$namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';
$mejaSesi   = session('meja', []);
?>
@include('partials.site_top')

<div class="salam-user">
  <span class="salam-emoji">👋</span>
  <div>
    <div class="salam-teks">Mari ngopi, <b><?= e($mejaSesi['nama']) ?></b>!</div>
    <div class="salam-sub">Meja <?= e($mejaSesi['nomor_meja']) ?> · Mau pesan apa hari ini?</div>
  </div>
</div>

<?php if ($q === '' && $fkat === 0): ?>
<div class="banner-toko">
  <?php if (!empty($pengaturan['banner'])): ?>
    <img src="uploads/toko/<?= e($pengaturan['banner']) ?>" alt="Banner">
  <?php endif; ?>
  <div class="banner-isi">
    <h1><?= e($namaToko) ?></h1>
    <p><?= e($pengaturan['deskripsi'] ?? '') ?></p>
    <?php if (!empty($pengaturan['alamat'])): ?>
      <p style="margin-top:8px"><i class="bi bi-geo-alt"></i> <?= e($pengaturan['alamat']) ?></p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<form class="cari-box" method="get">
  <i class="bi bi-search"></i>
  <input type="search" name="q" placeholder="Mau ngopi apa hari ini?" value="<?= e($q) ?>">
  <?php if ($fkat): ?><input type="hidden" name="kategori" value="<?= $fkat ?>"><?php endif; ?>
</form>

<div class="chip-baris">
  <a class="chip <?= $fkat === 0 ? 'aktif' : '' ?>" href="index.php<?= $q ? '?q=' . urlencode($q) : '' ?>">Semua</a>
  <?php foreach ($daftarKategori as $k): ?>
    <a class="chip <?= $fkat === (int) $k['id'] ? 'aktif' : '' ?>"
       href="index.php?kategori=<?= $k['id'] ?><?= $q ? '&q=' . urlencode($q) : '' ?>"><?= e($k['nama']) ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$daftarMenu): ?>
  <div class="kosong">
    <i class="bi bi-cup-hot"></i>
    Menu tidak ditemukan<?= $q ? ' untuk "' . e($q) . '"' : '' ?>.
  </div>
<?php else: ?>
  <?php
  // Kelompokkan per kategori bila tanpa filter, agar mudah dijelajah
  $kelompok = [];
  foreach ($daftarMenu as $m) $kelompok[$m['kategori']][] = $m;
  ?>
  <?php foreach ($kelompok as $namaKategori => $items): ?>
    <?php if (count($kelompok) > 1): ?><div class="judul-bagian"><?= e($namaKategori) ?></div><?php endif; ?>
    <div class="grid-menu" style="margin-bottom:16px">
      <?php foreach ($items as $m):
        $dataItem = json_encode([
            'id'      => (int) $m['id'],
            'nama'    => $m['nama'],
            'harga'   => (float) $m['harga'],
            'foto'    => $m['foto'] ? 'uploads/menu/' . $m['foto'] : null,
            'minuman' => in_array($m['kategori'], KATEGORI_MINUMAN, true),
            'tanpa_gula' => (bool) $m['tanpa_gula'],
        ], JSON_HEX_APOS | JSON_HEX_QUOT);
      ?>
        <div class="kartu-menu" data-item='<?= $dataItem ?>' style="cursor:pointer">
          <?php if ($m['foto']): ?>
            <img class="foto" src="uploads/menu/<?= e($m['foto']) ?>" alt="<?= e($m['nama']) ?>" loading="lazy">
          <?php else: ?>
            <div class="foto-kosong"><i class="bi bi-cup-hot"></i></div>
          <?php endif; ?>
          <div class="isi">
            <div class="nama"><?= e($m['nama']) ?></div>
            <?php if ($m['deskripsi']): ?><div class="ket"><?= e($m['deskripsi']) ?></div><?php endif; ?>
            <div class="bawah">
              <span class="harga"><?= rupiah($m['harga']) ?></span>
              <button class="btn-tambah" aria-label="Tambah <?= e($m['nama']) ?>">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

@include('partials.site_bottom')
