@include('partials.admin_top')

<a href="meja.php" class="d-inline-flex align-items-center gap-1 mb-3 fw-semibold" style="color:var(--primary)">
  <i class="bi bi-arrow-left"></i> Kembali ke Meja & QR
</a>

<div class="card-k">
  <div class="card-head">Scan QR Pakai Kamera Laptop</div>
  <div class="card-body-k">
    <p class="text-secondary" style="font-size:13.5px">
      Arahkan QR meja (di kertas cetak, atau di layar HP) ke kamera laptop.
      Cuma buat testing — pelanggan aslinya scan pakai kamera HP masing-masing.
    </p>

    <div id="videoWrap" style="max-width:420px;margin:0 auto;position:relative;background:#000;border-radius:14px;overflow:hidden">
      <video id="video" style="width:100%;display:block" playsinline muted></video>
      <div style="position:absolute;inset:0;border:3px solid rgba(255,255,255,.4);margin:15%;border-radius:12px;pointer-events:none"></div>
    </div>
    <canvas id="canvas" style="display:none"></canvas>

    <div id="statusBox" class="text-center text-secondary mt-3" style="font-size:13.5px">Meminta izin kamera…</div>
    <div id="debugBox" class="text-center mt-1" style="font-size:11.5px;color:#aaa"></div>

    <div id="hasilBox" class="d-none" style="max-width:420px;margin:16px auto 0;text-align:center">
      <div class="alert alert-success mb-2" style="font-size:13.5px">QR terbaca!</div>
      <div class="mb-2" style="word-break:break-all;font-size:12.5px" id="hasilTeks"></div>
      <a id="hasilLink" href="#" target="_blank" class="btn btn-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Buka Halaman Meja</a>
      <button class="btn btn-light ms-2" onclick="scanLagi()">Scan Lagi</button>
    </div>

    <hr class="my-4">
    <div class="text-center">
      <p class="text-secondary mb-2" style="font-size:13px">
        Kamera susah fokus / cahaya kurang? Tes dulu tanpa kamera pakai QR yang sudah jadi file,
        buat mastiin pembaca QR-nya sendiri jalan normal:
      </p>
      <button class="btn btn-outline-primary btn-sm" onclick="ujiTanpaKamera()">
        <i class="bi bi-file-earmark-image me-1"></i>Uji Pakai QR Meja 1 (Tanpa Kamera)
      </button>
      <div id="ujiFileHasil" class="mt-2" style="font-size:12.5px"></div>
    </div>
  </div>
</div>

<script src="../assets/js/jsqr.js"></script>
<script>
const video     = document.getElementById('video');
const canvas    = document.getElementById('canvas');
const ctx       = canvas.getContext('2d', { willReadFrequently: true });
const statusBox = document.getElementById('statusBox');
const debugBox  = document.getElementById('debugBox');
const hasilBox  = document.getElementById('hasilBox');
let stream = null, aktif = true, percobaan = 0;

async function mulaiKamera() {
  if (!window.isSecureContext) {
    statusBox.textContent = 'Halaman ini tidak "secure context" — kamera diblokir browser. Buka lewat http://localhost, bukan IP.';
    return;
  }
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
  } catch (e) {
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true });
    } catch (e2) {
      statusBox.textContent = 'Gagal akses kamera: ' + e2.message + ' (izinkan akses kamera di ikon gembok/kamera pada address bar)';
      return;
    }
  }
  video.srcObject = stream;
  await video.play();
  statusBox.textContent = 'Mengarahkan… cari QR code di dalam kotak.';
  requestAnimationFrame(tick);
}

function tick() {
  if (!aktif) return;
  if (video.readyState === video.HAVE_ENOUGH_DATA && video.videoWidth > 0) {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    try {
      const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      const kode = jsQR(img.data, img.width, img.height);
      percobaan++;
      debugBox.textContent = 'Resolusi kamera: ' + canvas.width + '×' + canvas.height + ' · percobaan ke-' + percobaan;
      if (kode && kode.data) {
        ketemu(kode.data);
        return;
      }
    } catch (err) {
      debugBox.textContent = 'Error decode: ' + err.message;
    }
  } else {
    debugBox.textContent = 'Menunggu video kamera siap… (readyState=' + video.readyState + ')';
  }
  requestAnimationFrame(tick);
}

function ketemu(teks) {
  aktif = false;
  document.getElementById('hasilTeks').textContent = teks;
  document.getElementById('hasilLink').href = teks;
  hasilBox.classList.remove('d-none');
  statusBox.textContent = '';
  if (stream) stream.getTracks().forEach(t => t.stop());
}

function scanLagi() {
  hasilBox.classList.add('d-none');
  aktif = true;
  percobaan = 0;
  statusBox.textContent = 'Meminta izin kamera…';
  mulaiKamera();
}

async function ujiTanpaKamera() {
  const box = document.getElementById('ujiFileHasil');
  box.textContent = 'Memuat gambar…';
  try {
    const img = new Image();
    img.crossOrigin = 'anonymous';
    await new Promise((resolve, reject) => {
      img.onload = resolve;
      img.onerror = reject;
      img.src = '../uploads/qrcode/meja-1.png?v=' + Date.now();
    });
    const c = document.createElement('canvas');
    c.width = img.naturalWidth; c.height = img.naturalHeight;
    const cctx = c.getContext('2d');
    cctx.drawImage(img, 0, 0);
    const data = cctx.getImageData(0, 0, c.width, c.height);
    const kode = jsQR(data.data, data.width, data.height);
    if (kode && kode.data) {
      box.innerHTML = '<span class="text-success fw-semibold">Berhasil dibaca!</span> Isi: ' + kode.data +
        '<br><span class="text-secondary">Artinya jsQR & kameranya normal — masalahnya di jarak/fokus/cahaya saat scan langsung.</span>';
    } else {
      box.innerHTML = '<span class="text-danger fw-semibold">Gagal dibaca dari file juga.</span> Berarti ada masalah di library/setup, bukan di kamera.';
    }
  } catch (e) {
    box.innerHTML = '<span class="text-danger">Error: ' + e.message + '</span>';
  }
}

mulaiKamera();
</script>

@include('partials.admin_bottom')
