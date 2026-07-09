@include('partials.site_top')

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

@include('partials.site_bottom')
