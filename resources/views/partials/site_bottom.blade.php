<?php
$jmlKrj   = jumlah_item_keranjang();
$navLinks = [
    'beranda'   => ['index.php',        'bi-house-door', 'Beranda'],
    'keranjang' => ['keranjang.php',    'bi-bag',        'Keranjang'],
    'pesanan'   => ['pesanan_saya.php', 'bi-receipt',    'Pesanan'],
    'akun'      => ['akun.php',         'bi-person',     'Akun'],
];
?>
</div><!-- /.wrap -->

<nav class="nav-bawah">
  <?php foreach ($navLinks as $key => [$href, $icon, $label]): ?>
    <a href="<?= $href ?>" class="<?= $activeNav === $key ? 'aktif' : '' ?>">
      <i class="bi <?= $icon ?><?= $activeNav === $key ? '-fill' : '' ?>"></i>
      <?= $label ?>
      <?php if ($key === 'keranjang'): ?>
        <span class="badge-keranjang" id="badgeKrj" <?= $jmlKrj ? '' : 'style="display:none"' ?>><?= $jmlKrj ?: '' ?></span>
      <?php endif; ?>
    </a>
  <?php endforeach; ?>
</nav>

<div class="toast-lk" id="toastKedai"></div>

<!-- ============ Sheet opsi pesanan (ala Kopi Kenangan) ============ -->
<div class="sheet-overlay" id="sheetOverlay"></div>
<div class="sheet" id="sheetOpsi" role="dialog" aria-modal="true">
  <div class="sheet-garis"></div>
  <div class="sheet-atas">
    <img id="shFoto" class="sheet-foto" src="" alt="" style="display:none">
    <span id="shFotoKosong" class="sheet-foto sheet-foto-kosong"><i class="bi bi-cup-hot"></i></span>
    <div>
      <div id="shNama" style="font-weight:800;font-size:15.5px"></div>
      <div id="shHargaDasar" style="color:var(--primary-dark);font-weight:800;font-size:14px;margin-top:2px"></div>
    </div>
    <button class="sheet-tutup" id="shTutup" aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
  </div>

  <div id="shOpsiMinuman">
    <div class="opsi-grup">
      <div class="opsi-judul">Ukuran</div>
      <div class="opsi-pil">
        <label><input type="radio" name="sh_ukuran" value="Regular" checked><span>Regular</span></label>
        <label><input type="radio" name="sh_ukuran" value="Large"><span>Large <small>+Rp 5.000</small></span></label>
      </div>
    </div>
    <div class="opsi-grup">
      <div class="opsi-judul">Penyajian</div>
      <div class="opsi-pil">
        <label><input type="radio" name="sh_saji" value="Dingin" checked><span><i class="bi bi-snow"></i> Dingin</span></label>
        <label><input type="radio" name="sh_saji" value="Panas"><span><i class="bi bi-fire"></i> Panas</span></label>
      </div>
    </div>
    <div class="opsi-grup" id="shGrupGula">
      <div class="opsi-judul">Gula</div>
      <div class="opsi-pil">
        <label><input type="radio" name="sh_gula" value="Normal Sugar" checked><span>Normal</span></label>
        <label><input type="radio" name="sh_gula" value="Less Sugar"><span>Less Sugar</span></label>
        <label><input type="radio" name="sh_gula" value="No Sugar"><span>No Sugar</span></label>
      </div>
    </div>
  </div>

  <div class="opsi-grup" style="display:flex;align-items:center;justify-content:space-between">
    <div class="opsi-judul" style="margin:0">Jumlah</div>
    <div class="stepper">
      <button type="button" id="shKurang">−</button>
      <span class="qty" id="shQty">1</span>
      <button type="button" id="shPlus">+</button>
    </div>
  </div>

  <button class="btn-utama btn-blok" id="shSimpan" style="margin-top:14px">
    Tambah ke Keranjang — <span id="shTotal"></span>
  </button>
</div>

<script>
function tampilkanToast(pesan) {
  const t = document.getElementById('toastKedai');
  t.textContent = pesan;
  t.classList.add('tampil');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('tampil'), 1800);
}
function perbaruiBadge(jumlah) {
  for (const id of ['badgeKrj', 'badgeKrjTop']) {
    const b = document.getElementById(id);
    if (!b) continue;
    b.textContent = jumlah || '';
    b.style.display = jumlah ? '' : 'none';
  }
}

/* ---------- Sheet opsi ---------- */
const sheet = document.getElementById('sheetOpsi');
const overlay = document.getElementById('sheetOverlay');
const fmt = n => 'Rp ' + Number(n).toLocaleString('id-ID');
let itemAktif = null, qty = 1;

function hitungTotal() {
  if (!itemAktif) return;
  let harga = itemAktif.harga;
  if (itemAktif.minuman && document.querySelector('input[name=sh_ukuran]:checked')?.value === 'Large') harga += 5000;
  document.getElementById('shTotal').textContent = fmt(harga * qty);
  document.getElementById('shQty').textContent = qty;
}

function bukaSheet(item) {
  itemAktif = item; qty = 1;
  document.getElementById('shNama').textContent = item.nama;
  document.getElementById('shHargaDasar').textContent = fmt(item.harga);
  const foto = document.getElementById('shFoto'), kosong = document.getElementById('shFotoKosong');
  if (item.foto) { foto.src = item.foto; foto.style.display = ''; kosong.style.display = 'none'; }
  else { foto.style.display = 'none'; kosong.style.display = ''; }
  document.getElementById('shOpsiMinuman').style.display = item.minuman ? '' : 'none';
  // menu seperti Espresso/Americano tidak menampilkan pilihan gula
  document.getElementById('shGrupGula').style.display = item.tanpa_gula ? 'none' : '';
  // reset pilihan default
  for (const [n, v] of [['sh_ukuran', 'Regular'], ['sh_saji', 'Dingin'], ['sh_gula', 'Normal Sugar']]) {
    const el = document.querySelector(`input[name=${n}][value="${v}"]`);
    if (el) el.checked = true;
  }
  hitungTotal();
  sheet.classList.add('buka');
  overlay.classList.add('buka');
  document.body.style.overflow = 'hidden';
}
function tutupSheet() {
  sheet.classList.remove('buka');
  overlay.classList.remove('buka');
  document.body.style.overflow = '';
}
overlay.addEventListener('click', tutupSheet);
document.getElementById('shTutup').addEventListener('click', tutupSheet);
document.getElementById('shPlus').addEventListener('click', () => { if (qty < 20) qty++; hitungTotal(); });
document.getElementById('shKurang').addEventListener('click', () => { if (qty > 1) qty--; hitungTotal(); });
sheet.addEventListener('change', hitungTotal);

document.getElementById('shSimpan').addEventListener('click', async () => {
  if (!itemAktif) return;
  const p = new URLSearchParams({ aksi: 'tambah', menu_id: itemAktif.id, jumlah: qty });
  if (itemAktif.minuman) {
    p.set('ukuran', document.querySelector('input[name=sh_ukuran]:checked').value);
    p.set('saji',   document.querySelector('input[name=sh_saji]:checked').value);
    if (!itemAktif.tanpa_gula) p.set('gula', document.querySelector('input[name=sh_gula]:checked').value);
  }
  const res = await fetch('keranjang.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'fetch' },
    body: p.toString()
  });
  const data = await res.json();
  if (data.ok) { tutupSheet(); perbaruiBadge(data.jumlah); tampilkanToast(data.pesan); }
});

// Klik tombol + / kartu menu → buka sheet
document.addEventListener('click', (ev) => {
  const el = ev.target.closest('[data-item]');
  if (!el) return;
  ev.preventDefault();
  bukaSheet(JSON.parse(el.dataset.item));
});
</script>
</body>
</html>
