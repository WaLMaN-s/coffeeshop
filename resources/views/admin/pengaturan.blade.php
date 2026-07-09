@include('partials.admin_top')

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
        <div class="card-head">WiFi Kedai</div>
        <div class="card-body-k">
          <p class="text-secondary" style="font-size:13px;margin-bottom:14px">Ditampilkan di struk cetak kasir, biar pelanggan bisa langsung connect.</p>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Nama WiFi (SSID)</label>
              <input type="text" name="wifi_ssid" class="form-control" placeholder="LorongKopi-Guest" value="<?= e($p['wifi_ssid'] ?? '') ?>">
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Password WiFi</label>
              <input type="text" name="wifi_password" class="form-control" placeholder="ngopidulu123" value="<?= e($p['wifi_password'] ?? '') ?>">
            </div>
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

@include('partials.admin_bottom')
