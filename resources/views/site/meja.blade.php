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
      <p style="color:var(--ink-muted);font-size:13.5px;margin:0 0 18px">
        Pesan langsung dari mejamu.<br>Arahkan kamera ke QR code yang ada di atas meja.
      </p>

      <div id="videoWrap" style="position:relative;background:#000;border-radius:14px;overflow:hidden;margin-bottom:14px;display:none">
        <video id="video" style="width:100%;display:block" playsinline muted></video>
        <div style="position:absolute;inset:0;border:3px solid rgba(255,255,255,.45);margin:16%;border-radius:12px;pointer-events:none"></div>
      </div>
      <canvas id="canvas" style="display:none"></canvas>

      <div id="kotakInfo" style="background:var(--bg);border-radius:14px;padding:18px;margin-bottom:14px">
        <i class="bi bi-qrcode" style="font-size:34px;color:var(--primary)"></i>
        <div id="statusKamera" style="font-size:12.5px;color:var(--ink-muted);margin-top:8px">Meminta izin kamera…</div>
        <button type="button" id="btnKamera" class="btn-utama" style="display:none;margin-top:12px">
          <i class="bi bi-camera"></i> Nyalakan Kamera
        </button>
        <div id="infoDebug" style="font-size:10.5px;color:#aab;margin-top:10px;word-break:break-word"></div>
      </div>

      <?php if ($error): ?><div class="pesan-info pesan-gagal" style="text-align:left"><?= e($error) ?></div><?php endif; ?>

      <a href="index.php" class="btn-garis btn-blok" style="margin-bottom:10px">
        <i class="bi bi-cup-hot"></i> Lihat Menu Dulu
      </a>

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
        <div class="form-grup">
          <label>No. HP</label>
          <input type="tel" name="no_hp" class="input" placeholder="Contoh: 081234567890" required maxlength="20"
                 value="<?= e($_POST['no_hp'] ?? '') ?>">
        </div>
        <button type="submit" class="btn-utama btn-blok"><i class="bi bi-cup-hot"></i> Mulai Pesan</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if (!$meja): ?>
<script src="assets/js/jsqr.js"></script>
<script>
/* Scanner QR meja langsung dari halaman (kamera belakang HP). */
const video     = document.getElementById('video');
const canvas    = document.getElementById('canvas');
const ctx       = canvas.getContext('2d', { willReadFrequently: true });
const videoWrap = document.getElementById('videoWrap');
const statusKam = document.getElementById('statusKamera');
let stream = null, aktif = true;

function kodeDariQr(teks) {
  // QR meja berisi URL meja.php?kode=... — ambil kodenya saja (hindari redirect ke luar).
  try {
    const u = new URL(teks);
    const k = u.searchParams.get('kode');
    if (k) return k;
  } catch (e) { /* bukan URL, anggap teks = kode */ }
  return teks.trim();
}

function ketemu(teks) {
  aktif = false;
  if (stream) stream.getTracks().forEach(t => t.stop());
  statusKam.textContent = 'QR terbaca! Mengarahkan…';
  location.href = 'meja.php?kode=' + encodeURIComponent(kodeDariQr(teks));
}

function tick() {
  if (!aktif) return;
  if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
    canvas.width  = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const img  = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const hasil = jsQR(img.data, img.width, img.height);
    if (hasil && hasil.data) { ketemu(hasil.data); return; }
  }
  requestAnimationFrame(tick);
}

const btnKamera = document.getElementById('btnKamera');
const infoDebug = document.getElementById('infoDebug');

/* Deteksi browser dalam aplikasi (WA/IG/FB dsb) — webview sering tidak bisa kamera. */
function dalamAplikasi() {
  const ua = navigator.userAgent || '';
  return /wv\)|WhatsApp|Instagram|FBAN|FBAV|Line\//i.test(ua);
}

async function statusIzin() {
  try {
    const p = await navigator.permissions.query({ name: 'camera' });
    return p.state; // granted | prompt | denied
  } catch (e) { return '?'; /* browser lama */ }
}

/* Kirim laporan ke server supaya kendala kamera pelanggan bisa dilihat di log. */
function kirimLog(teks) {
  try { fetch('kamera_log.php', { method: 'POST', body: teks }); } catch (e) { /* abaikan */ }
}

async function tulisDebug(err) {
  const izin = await statusIzin();
  infoDebug.textContent = 'Info teknis: ' + (err ? err.name + ' — ' + err.message : '-')
    + ' · izin situs: ' + izin
    + ' · ' + (navigator.userAgent || '').slice(0, 90);
  kirimLog((err ? err.name + ' — ' + err.message : 'boot') + ' | izin: ' + izin + ' | ' + navigator.userAgent);
}

function gagalKamera(err) {
  btnKamera.style.display = '';
  tulisDebug(err);
  if (dalamAplikasi()) {
    statusKam.innerHTML = 'Sepertinya halaman ini dibuka dari <b>dalam aplikasi lain</b> (WhatsApp/Instagram dll) '
      + 'yang tidak mengizinkan kamera.<br>Ketuk menu <b>&#8942;</b> lalu pilih <b>Buka di Chrome/browser</b>, atau masukkan kode meja di bawah.';
    return;
  }
  if (err && err.name === 'NotAllowedError') {
    statusKam.innerHTML = 'Izin kamera <b>diblokir</b> untuk situs ini, jadi browser tidak menampilkan popup izin lagi.<br>'
      + 'Cara membukanya:<br>'
      + '1) Ketuk ikon <i class="bi bi-lock-fill"></i> / <i class="bi bi-sliders"></i> di sebelah alamat web &rarr; '
      + '<b>Izin</b> (Permissions) &rarr; <b>Kamera</b> &rarr; <b>Izinkan</b>, lalu muat ulang halaman.<br>'
      + '2) Kalau menu itu tidak ada: Setelan HP &rarr; Aplikasi &rarr; Chrome/Brave &rarr; <b>Izin</b> &rarr; '
      + 'Kamera &rarr; <b>Izinkan</b>, lalu buka lagi halaman ini.<br>'
      + 'Setelah itu tekan tombol di bawah.';
  } else if (err && err.name === 'NotFoundError') {
    statusKam.textContent = 'Kamera tidak ditemukan di perangkat ini — masukkan kode meja di bawah.';
  } else if (err && err.name === 'NotReadableError') {
    statusKam.textContent = 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi itu lalu coba lagi.';
  } else {
    statusKam.textContent = 'Kamera tidak bisa diakses (' + (err && err.name ? err.name : 'tidak diketahui')
      + '). Coba lagi, atau masukkan kode meja di bawah.';
  }
}

async function mulaiKamera() {
  if (!navigator.mediaDevices || !window.isSecureContext) {
    statusKam.textContent = 'Kamera tidak tersedia di browser ini — scan pakai aplikasi kamera HP, atau masukkan kode meja di bawah.';
    return;
  }
  statusKam.textContent = 'Meminta izin kamera…';
  aktif = true;
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
  } catch (e) {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
    } catch (e2) {
      gagalKamera(e2);
      return;
    }
  }
  btnKamera.style.display = 'none';
  video.srcObject = stream;
  await video.play();
  videoWrap.style.display = '';
  statusKam.textContent = 'Arahkan kamera ke QR code di meja kamu.';
  kirimLog('OK kamera nyala | ' + navigator.userAgent);
  requestAnimationFrame(tick);
}

/* Cek status izin dulu: kalau sudah diblokir, getUserMedia langsung gagal diam-diam
   tanpa popup — jadi tampilkan instruksi buka blokir, bukan "meminta izin" palsu. */
async function boot() {
  tulisDebug(null);
  if (dalamAplikasi()) { gagalKamera(null); return; }
  const izin = await statusIzin();
  if (izin === 'denied') {
    gagalKamera({ name: 'NotAllowedError', message: 'izin situs berstatus diblokir (pre-check)' });
    return;
  }
  mulaiKamera();
}

btnKamera.addEventListener('click', mulaiKamera);
boot();
</script>
<?php endif; ?>
</body>
</html>
