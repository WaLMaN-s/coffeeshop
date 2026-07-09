<?php $pengaturan = get_pengaturan(db()); ?>
@include('partials.site_top')

<a href="pesanan_saya.php" style="display:inline-flex;align-items:center;gap:6px;margin:16px 0 10px;font-weight:700;color:var(--primary);font-size:13.5px">
  <i class="bi bi-arrow-left"></i> Semua pesanan
</a>

<div class="kartu">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:10px">
    <div>
      <div style="font-weight:800;font-size:18px;color:var(--primary)">Antrian #<?= no_antrian($pesanan['nomor_pesanan']) ?></div>
      <div style="font-weight:700;font-size:13px"><?= e($pesanan['nomor_pesanan']) ?></div>
      <div style="font-size:12.5px;color:var(--ink-muted)"><?= tanggal_id($pesanan['created_at'], true) ?></div>
    </div>
    <?= badge_status_pesanan($pesanan['status']) ?>
  </div>

  <?php if (!$batal): ?>
  <!-- Progres -->
  <div style="display:flex;margin-top:18px">
    <?php foreach ($labelTahap as $i => $lbl): $aktif = $i <= $posisi; ?>
      <div style="flex:1;text-align:center;position:relative">
        <?php if ($i > 0): ?>
          <div style="position:absolute;top:11px;left:-50%;width:100%;height:3px;background:<?= $i <= $posisi ? 'var(--primary)' : 'var(--border)' ?>"></div>
        <?php endif; ?>
        <div style="position:relative;width:24px;height:24px;margin:0 auto;border-radius:50%;
                    background:<?= $aktif ? 'var(--primary)' : 'var(--surface)' ?>;
                    border:2.5px solid <?= $aktif ? 'var(--primary)' : 'var(--border)' ?>;
                    color:#fff;display:flex;align-items:center;justify-content:center;font-size:12px">
          <?= $aktif ? '<i class="bi bi-check-lg"></i>' : '' ?>
        </div>
        <div style="font-size:10.5px;font-weight:600;margin-top:5px;color:<?= $aktif ? 'var(--primary-dark)' : 'var(--ink-muted)' ?>"><?= $lbl ?></div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<div class="kartu">
  <div style="font-weight:700;margin-bottom:6px">Item Pesanan</div>
  <?php foreach ($item as $it): ?>
    <div class="item-keranjang">
      <?php if ($it['foto']): ?>
        <img class="thumb" src="uploads/menu/<?= e($it['foto']) ?>" alt="">
      <?php else: ?>
        <span class="thumb"><i class="bi bi-cup-hot"></i></span>
      <?php endif; ?>
      <div style="flex:1">
        <div style="font-weight:700;font-size:13.5px"><?= e($it['menu']) ?></div>
        <?php if (!empty($it['opsi'])): ?>
          <div style="font-size:11.5px;color:var(--ink-muted)"><?= e($it['opsi']) ?></div>
        <?php endif; ?>
        <div style="font-size:12.5px;color:var(--ink-muted)"><?= $it['jumlah'] ?> × <?= rupiah($it['harga']) ?></div>
      </div>
      <div style="font-weight:700"><?= rupiah($it['jumlah'] * $it['harga']) ?></div>
    </div>
  <?php endforeach; ?>
  <div style="display:flex;justify-content:space-between;padding-top:12px;font-weight:800;font-size:15.5px">
    <span>Total</span><span><?= rupiah($pesanan['total']) ?></span>
  </div>
  <?php if ($pesanan['catatan']): ?>
    <div style="margin-top:10px;font-size:13px;background:var(--bg);border-radius:10px;padding:10px 12px">
      <i class="bi bi-chat-left-text"></i> <?= e($pesanan['catatan']) ?>
    </div>
  <?php endif; ?>
</div>

<?php if ($bayar): ?>
<div class="kartu">
  <div style="font-weight:700;margin-bottom:10px">Pembayaran</div>
  <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0">
    <span style="color:var(--ink-muted)">Metode</span>
    <span style="font-weight:700;text-transform:uppercase"><?= e($bayar['metode']) ?></span>
  </div>
  <div style="display:flex;justify-content:space-between;font-size:13.5px;padding:4px 0;align-items:center">
    <span style="color:var(--ink-muted)">Status</span>
    <?= badge_status_bayar($bayar['status']) ?>
  </div>
  <?php if ($bayar['status'] === 'belum_dibayar' && !$batal): ?>
    <div class="pesan-info" style="background:var(--primary-soft);color:var(--primary-dark);margin-bottom:0">
      <i class="bi bi-info-circle"></i>
      <?= $bayar['metode'] === 'qris'
          ? 'Scan QRIS di kasir dan sebutkan nomor pesananmu untuk menyelesaikan pembayaran.'
          : 'Bayar tunai di kasir sambil menyebutkan nomor pesananmu.' ?>
    </div>
  <?php elseif ($bayar['status'] === 'sudah_dibayar'): ?>
    <div class="pesan-info pesan-sukses" style="margin-bottom:0">
      <i class="bi bi-check-circle-fill"></i>
      Pembayaran diterima<?= $bayar['tanggal_bayar'] ? ' pada ' . tanggal_id($bayar['tanggal_bayar'], true) : '' ?>. Terima kasih!
    </div>
  <?php endif; ?>
</div>

<?php if ($bayar['status'] === 'sudah_dibayar' && !empty($pengaturan['wifi_ssid'])): ?>
<div class="kartu">
  <div style="font-weight:700;margin-bottom:4px"><i class="bi bi-wifi" style="color:var(--primary)"></i> WiFi Gratis Buat Kamu</div>
  <div style="font-size:12.5px;color:var(--ink-muted);margin-bottom:12px">Pembayaran lunas — silakan connect sambil menunggu pesanan.</div>
  <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:var(--bg);border-radius:10px;margin-bottom:8px">
    <div style="flex:1;min-width:0">
      <div style="font-size:11px;color:var(--ink-muted)">Nama WiFi</div>
      <div style="font-weight:700;font-size:14px" id="wifiSsid"><?= e($pengaturan['wifi_ssid']) ?></div>
    </div>
    <button type="button" class="btn-garis" style="padding:7px 12px;font-size:12.5px" onclick="salinWifi('wifiSsid', this)"><i class="bi bi-copy"></i> Salin</button>
  </div>
  <?php if (!empty($pengaturan['wifi_password'])): ?>
  <div style="display:flex;align-items:center;gap:8px;padding:9px 12px;background:var(--bg);border-radius:10px">
    <div style="flex:1;min-width:0">
      <div style="font-size:11px;color:var(--ink-muted)">Password</div>
      <div style="font-weight:700;font-size:14px" id="wifiPass"><?= e($pengaturan['wifi_password']) ?></div>
    </div>
    <button type="button" class="btn-garis" style="padding:7px 12px;font-size:12.5px" onclick="salinWifi('wifiPass', this)"><i class="bi bi-copy"></i> Salin</button>
  </div>
  <?php endif; ?>
</div>
<script>
function salinWifi(id, btn) {
  const teks = document.getElementById(id).textContent.trim();
  const beres = () => {
    // simpan tampilan asli sekali saja, biar klik berulang tidak "macet" di Tersalin
    if (!btn.dataset.asli) btn.dataset.asli = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check2"></i> Tersalin';
    clearTimeout(btn._timerSalin);
    btn._timerSalin = setTimeout(() => { btn.innerHTML = btn.dataset.asli; }, 1500);
  };
  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(teks).then(beres);
  } else {
    // fallback browser lama / non-HTTPS
    const ta = document.createElement('textarea');
    ta.value = teks; document.body.appendChild(ta);
    ta.select(); document.execCommand('copy'); ta.remove(); beres();
  }
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php if ($pesanan['status'] === 'menunggu'): ?>
  <form method="post" style="margin-top:14px" onsubmit="return confirm('Batalkan pesanan ini?')">
    <input type="hidden" name="aksi" value="batal">
    <button class="btn-garis btn-blok" style="border-color:#d03b3b;color:#b3403f">
      <i class="bi bi-x-circle"></i> Batalkan Pesanan
    </button>
  </form>
<?php endif; ?>

<script>
// Real-time: begitu kasir mengubah status / memverifikasi bayar, halaman ini
// langsung menyegarkan diri tanpa perlu refresh manual.
(function () {
  const statusAwal = <?= json_encode($pesanan['status']) ?>;
  const bayarAwal  = <?= json_encode($bayar['status'] ?? '') ?>;
  async function cekStatus() {
    if (document.hidden) return;
    try {
      const res  = await fetch('pesanan_status.php?id=<?= (int) $pesanan['id'] ?>', { cache: 'no-store' });
      const data = await res.json();
      if (data.ok && (data.status !== statusAwal || (data.bayar || '') !== bayarAwal)) {
        location.reload();
      }
    } catch (e) { /* diam saat offline */ }
  }
  setInterval(cekStatus, 5000);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) cekStatus(); });
})();
</script>

@include('partials.site_bottom')
