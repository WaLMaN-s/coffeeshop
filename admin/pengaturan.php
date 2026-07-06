<?php
require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama_toko'] ?? '');
    $alamat    = trim($_POST['alamat'] ?? '');
    $wa        = preg_replace('/[^0-9+]/', '', $_POST['whatsapp'] ?? '');
    $jam       = trim($_POST['jam_operasional'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if ($nama === '') {
        set_flash('gagal', 'Nama toko wajib diisi.');
    } else {
        $err1 = $err2 = null;
        $logo   = upload_gambar('logo', 'toko', $err1);
        $banner = upload_gambar('banner', 'toko', $err2);
        if ($err1 || $err2) {
            set_flash('gagal', $err1 ?: $err2);
        } else {
            $lama = $db->query('SELECT logo, banner FROM pengaturan WHERE id = 1')->fetch();
            if ($logo)   hapus_gambar($lama['logo'] ?? null, 'toko');
            if ($banner) hapus_gambar($lama['banner'] ?? null, 'toko');

            $db->prepare('
                UPDATE pengaturan SET nama_toko = ?, alamat = ?, whatsapp = ?, jam_operasional = ?, deskripsi = ?,
                       logo = COALESCE(?, logo), banner = COALESCE(?, banner)
                WHERE id = 1')
               ->execute([$nama, $alamat, $wa, $jam, $deskripsi, $logo, $banner]);
            set_flash('sukses', 'Pengaturan toko berhasil disimpan.');
        }
    }
    header('Location: pengaturan.php');
    exit;
}

$p = $db->query('SELECT * FROM pengaturan WHERE id = 1')->fetch();

$pageTitle = 'Pengaturan Toko';
$active    = 'pengaturan';
require __DIR__ . '/includes/layout_top.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-9">
    <form method="post" enctype="multipart/form-data">
      <div class="card-k mb-3">
        <div class="card-head">Informasi Toko</div>
        <div class="card-body-k">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nama Toko</label>
              <input type="text" name="nama_toko" class="form-control" required value="<?= e($p['nama_toko'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Nomor WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control" placeholder="628xxxxxxxxxx" value="<?= e($p['whatsapp'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="2"><?= e($p['alamat'] ?? '') ?></textarea>
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Jam Operasional</label>
              <input type="text" name="jam_operasional" class="form-control" placeholder="08.00 - 22.00 WIB" value="<?= e($p['jam_operasional'] ?? '') ?>">
            </div>
          </div>
          <div class="mb-0">
            <label class="form-label">Deskripsi Toko</label>
            <textarea name="deskripsi" class="form-control" rows="3"><?= e($p['deskripsi'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <div class="card-k mb-3">
        <div class="card-head">Logo &amp; Banner</div>
        <div class="card-body-k">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Logo <span class="text-secondary fw-normal">(persegi, maks 2 MB)</span></label>
              <?php if (!empty($p['logo'])): ?>
                <div class="mb-2"><img src="../uploads/toko/<?= e($p['logo']) ?>" alt="Logo" style="width:72px;height:72px;border-radius:14px;object-fit:cover;border:1px solid var(--border)"></div>
              <?php endif; ?>
              <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Banner <span class="text-secondary fw-normal">(lebar, maks 2 MB)</span></label>
              <?php if (!empty($p['banner'])): ?>
                <div class="mb-2"><img src="../uploads/toko/<?= e($p['banner']) ?>" alt="Banner" style="width:100%;max-width:320px;height:90px;border-radius:12px;object-fit:cover;border:1px solid var(--border)"></div>
              <?php endif; ?>
              <input type="file" name="banner" class="form-control" accept=".jpg,.jpeg,.png,.webp">
            </div>
          </div>
        </div>
      </div>

      <button class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Simpan Pengaturan</button>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
