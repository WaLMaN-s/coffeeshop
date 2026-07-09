<?php
require_once __DIR__ . '/includes/site_init.php';

if (!meja_aktif()) {
    header('Location: meja.php');
    exit;
}

/* ---------- Aksi keranjang ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi  = $_POST['aksi'] ?? '';
    $ajax  = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    $pesan = '';

    if ($aksi === 'tambah') {
        $menuId = (int) ($_POST['menu_id'] ?? 0);
        $jumlah = max(1, min(20, (int) ($_POST['jumlah'] ?? 1)));
        $cek = $db->prepare("
            SELECT m.nama, m.tanpa_gula, k.nama kategori FROM menu m
            JOIN kategori k ON k.id = m.kategori_id
            WHERE m.id = ? AND m.status = 'aktif'");
        $cek->execute([$menuId]);
        if ($m = $cek->fetch()) {
            $minuman = in_array($m['kategori'], KATEGORI_MINUMAN, true);
            $ukuran  = $saji = $gula = null;
            if ($minuman) {
                $ukuran = array_key_exists($_POST['ukuran'] ?? '', UKURAN_OPSI) ? $_POST['ukuran'] : 'Regular';
                $saji   = in_array($_POST['saji'] ?? '', SAJI_OPSI, true) ? $_POST['saji'] : 'Dingin';
                // menu tanpa_gula (Espresso, Americano, dll) tidak menyimpan opsi gula
                $gula   = $m['tanpa_gula'] ? null
                        : (in_array($_POST['gula'] ?? '', GULA_OPSI, true) ? $_POST['gula'] : 'Normal Sugar');
            }
            $key = kunci_keranjang($menuId, $ukuran, $saji, $gula);
            if (isset($_SESSION['keranjang'][$key])) {
                $_SESSION['keranjang'][$key]['jumlah'] += $jumlah;
            } else {
                $_SESSION['keranjang'][$key] = [
                    'menu_id' => $menuId, 'jumlah' => $jumlah,
                    'ukuran' => $ukuran, 'saji' => $saji, 'gula' => $gula,
                ];
            }
            $pesan = $m['nama'] . ' masuk keranjang';
        }
    } elseif ($aksi === 'plus') {
        $key = $_POST['key'] ?? '';
        if (isset($_SESSION['keranjang'][$key])) $_SESSION['keranjang'][$key]['jumlah']++;
    } elseif ($aksi === 'kurang') {
        $key = $_POST['key'] ?? '';
        if (isset($_SESSION['keranjang'][$key]) && --$_SESSION['keranjang'][$key]['jumlah'] <= 0) {
            unset($_SESSION['keranjang'][$key]);
        }
    } elseif ($aksi === 'hapus') {
        unset($_SESSION['keranjang'][$_POST['key'] ?? '']);
    } elseif ($aksi === 'kosongkan') {
        $_SESSION['keranjang'] = [];
    }

    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'jumlah' => jumlah_item_keranjang(), 'pesan' => $pesan]);
        exit;
    }
    header('Location: keranjang.php');
    exit;
}

$item  = isi_keranjang($db);
$total = array_sum(array_column($item, 'subtotal'));

$pageTitle = 'Keranjang';
$activeNav = 'keranjang';
require __DIR__ . '/includes/site_top.php';
?>

<div class="judul-bagian" style="margin-top:18px">Keranjang Saya</div>

<?php if (!$item): ?>
  <div class="kosong">
    <i class="bi bi-bag"></i>
    Keranjang masih kosong.<br><br>
    <a href="index.php" class="btn-utama"><i class="bi bi-cup-hot"></i> Lihat Menu</a>
  </div>
<?php else: ?>
  <div class="kartu">
    <?php foreach ($item as $it): ?>
      <div class="item-keranjang">
        <?php if ($it['foto']): ?>
          <img class="thumb" src="uploads/menu/<?= e($it['foto']) ?>" alt="">
        <?php else: ?>
          <span class="thumb"><i class="bi bi-cup-hot"></i></span>
        <?php endif; ?>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:13.5px"><?= e($it['nama']) ?></div>
          <?php if ($it['opsi_label']): ?>
            <div style="font-size:11.5px;color:var(--ink-muted);margin-top:1px"><?= e($it['opsi_label']) ?></div>
          <?php endif; ?>
          <div style="color:var(--primary-dark);font-weight:800;font-size:13.5px;margin-top:2px"><?= rupiah($it['harga_satuan']) ?></div>
        </div>
        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:6px">
          <div class="stepper">
            <form method="post" style="display:contents">
              <input type="hidden" name="aksi" value="kurang">
              <input type="hidden" name="key" value="<?= e($it['key']) ?>">
              <button aria-label="Kurangi">−</button>
            </form>
            <span class="qty"><?= $it['jumlah'] ?></span>
            <form method="post" style="display:contents">
              <input type="hidden" name="aksi" value="plus">
              <input type="hidden" name="key" value="<?= e($it['key']) ?>">
              <button aria-label="Tambah">+</button>
            </form>
          </div>
          <form method="post">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="key" value="<?= e($it['key']) ?>">
            <button style="border:0;background:none;color:#b3403f;font-size:12px;font-weight:600;cursor:pointer">
              <i class="bi bi-trash"></i> Hapus
            </button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="height:84px"></div><!-- ruang untuk bar total -->

  <div class="bar-total">
    <div class="inner">
      <div>
        <div style="font-size:12px;color:var(--ink-muted);font-weight:600"><?= jumlah_item_keranjang() ?> item · Total</div>
        <div style="font-size:18px;font-weight:800"><?= rupiah($total) ?></div>
      </div>
      <a href="checkout.php" class="btn-utama">Checkout <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/site_bottom.php'; ?>
