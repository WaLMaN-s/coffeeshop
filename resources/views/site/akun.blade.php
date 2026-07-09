<?php
$pengaturan = get_pengaturan(db());
$mejaSesi   = session('meja', []);
?>
@include('partials.site_top')

<div class="kartu" style="margin-top:18px;display:flex;align-items:center;gap:14px">
  <span class="logo-ikon" style="width:52px;height:52px;font-size:22px;border-radius:50%">
    <?= strtoupper(substr($mejaSesi['nama'], 0, 1)) ?>
  </span>
  <div style="flex:1">
    <div style="font-weight:800;font-size:16px"><?= e($mejaSesi['nama']) ?></div>
    <div style="font-size:12.5px;color:var(--ink-muted)"><i class="bi bi-table"></i> Meja <?= e($mejaSesi['nomor_meja']) ?></div>
    <?php if (!empty($mejaSesi['no_hp'])): ?>
      <div style="font-size:12.5px;color:var(--ink-muted)"><i class="bi bi-telephone"></i> <?= e($mejaSesi['no_hp']) ?></div>
    <?php endif; ?>
  </div>
  <a href="keluar.php" class="btn-keluar" style="margin-left:0" onclick="return confirm('Akhiri sesi meja ini?')">
    <i class="bi bi-box-arrow-right"></i> Keluar
  </a>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:12px">Ubah Data</div>
  <form method="post">
    <input type="hidden" name="aksi" value="ganti_nama">
    <div class="form-grup">
      <label>Nama</label>
      <input type="text" name="nama" class="input" required maxlength="100" value="<?= e($mejaSesi['nama']) ?>">
    </div>
    <div class="form-grup">
      <label>No. HP</label>
      <input type="tel" name="no_hp" class="input" required maxlength="20" value="<?= e($mejaSesi['no_hp'] ?? '') ?>">
    </div>
    <button class="btn-utama">Simpan</button>
  </form>
</div>

<?php if (!empty($pengaturan['whatsapp'])): ?>
<a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', $pengaturan['whatsapp'])) ?>" target="_blank" rel="noopener"
   class="kartu" style="display:flex;align-items:center;gap:12px;margin-top:12px">
  <i class="bi bi-whatsapp" style="font-size:22px;color:#0ca30c"></i>
  <div>
    <div style="font-weight:700;font-size:13.5px">Hubungi Kami</div>
    <div style="font-size:12px;color:var(--ink-muted)">Ada kendala? Chat via WhatsApp</div>
  </div>
  <i class="bi bi-chevron-right" style="margin-left:auto;color:var(--ink-muted)"></i>
</a>
<?php endif; ?>

@include('partials.site_bottom')
