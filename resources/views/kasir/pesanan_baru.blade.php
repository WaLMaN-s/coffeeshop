<?php
use App\Http\Controllers\Kasir\PosController;

$POS_UKURAN  = PosController::POS_UKURAN;
$POS_SAJI    = PosController::POS_SAJI;
$POS_GULA    = PosController::POS_GULA;
$POS_MINUMAN = PosController::POS_MINUMAN;
?>
@include('partials.kasir_top')

<div class="row g-3">
  <!-- ===== Kiri: pilih menu ===== -->
  <div class="col-lg-7">
    <div class="card-k">
      <div class="card-head">
        <input type="search" id="posCari" class="form-control" style="width:220px" placeholder="Cari menu…">
        <div class="d-flex gap-2 flex-wrap" id="posKategori">
          <button type="button" class="btn btn-primary" data-kat="" style="padding:8px 18px;font-weight:600">Semua</button>
          <?php foreach ($kategori as $k): ?>
            <button type="button" class="btn btn-light" data-kat="<?= e($k) ?>" style="padding:8px 18px;font-weight:600"><?= e($k) ?></button>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="card-body-k">
        <div class="row g-2" id="posGrid">
          <?php foreach ($menu as $m): ?>
            <div class="col-6 col-md-4 pos-item" data-nama="<?= e(strtolower($m['nama'])) ?>" data-kat="<?= e($m['kategori']) ?>">
              <button type="button" class="w-100 text-start border-0 p-0" style="background:none;cursor:pointer"
                      onclick='posTambah(<?= json_encode([
                          'id' => (int) $m['id'], 'nama' => $m['nama'],
                          'harga' => (float) $m['harga'],
                          'minuman' => in_array($m['kategori'], $POS_MINUMAN, true),
                          'tanpa_gula' => (bool) $m['tanpa_gula'],
                      ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                <span class="d-flex align-items-center gap-2" style="background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px 14px;min-height:96px">
                  <?php if ($m['foto']): ?>
                    <img src="../uploads/menu/<?= e($m['foto']) ?>" class="foto-menu" alt="" style="width:62px;height:62px">
                  <?php else: ?>
                    <span class="foto-placeholder" style="width:62px;height:62px"><i class="bi bi-cup-hot"></i></span>
                  <?php endif; ?>
                  <span style="min-width:0">
                    <span class="fw-bold d-block text-truncate" style="font-size:15.5px"><?= e($m['nama']) ?></span>
                    <span class="text-secondary fw-semibold" style="font-size:14px"><?= rupiah($m['harga']) ?></span>
                  </span>
                </span>
              </button>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ===== Kanan: keranjang & data pelanggan ===== -->
  <div class="col-lg-5">
    <form method="post" id="posForm">
      <div class="card-k mb-3">
        <div class="card-head"><span>Pesanan</span><span class="text-secondary" style="font-size:12.5px" id="posJumlah">0 item</span></div>
        <div class="card-body-k" id="posKeranjang">
          <p class="text-secondary mb-0" style="font-size:13.5px">Klik menu di kiri untuk menambahkan.</p>
        </div>
        <div class="card-body-k pt-0 d-flex justify-content-between fw-bold" style="font-size:16px">
          <span>Total</span><span id="posTotal">Rp 0</span>
        </div>
      </div>

      <div class="card-k mb-3">
        <div class="card-head">Pelanggan &amp; Pembayaran</div>
        <div class="card-body-k">
          <div class="mb-3">
            <label class="form-label">Nama <span class="text-secondary fw-normal">(opsional, buat dipanggil)</span></label>
            <input type="text" name="nama" class="form-control" placeholder="Pelanggan Kasir" maxlength="100">
          </div>
          <div class="row">
            <div class="col-6 mb-3">
              <label class="form-label">Meja <span class="text-secondary fw-normal">(opsional)</span></label>
              <select name="meja_id" class="form-select">
                <option value="0">Bawa pulang / di kasir</option>
                <?php foreach ($daftarMeja as $mj): ?>
                  <option value="<?= $mj['id'] ?>">Meja <?= e($mj['nomor_meja']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 mb-3">
              <label class="form-label">Metode</label>
              <select name="metode" class="form-select">
                <option value="cash">Cash</option>
                <option value="qris">QRIS</option>
              </select>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Catatan <span class="text-secondary fw-normal">(opsional)</span></label>
            <input type="text" name="catatan" class="form-control" placeholder="Contoh: es sedikit">
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="lunas" value="1" id="posLunas" checked>
            <label class="form-check-label" for="posLunas">Sudah dibayar (lunas di kasir)</label>
          </div>
          <input type="hidden" name="item" id="posItem">
          <button type="submit" class="btn btn-primary w-100" id="posSimpan" style="padding:15px;font-size:16.5px;font-weight:700" disabled>
            <i class="bi bi-check2-circle me-1"></i>Buat Pesanan
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
const POS_UKURAN = <?= json_encode($POS_UKURAN) ?>;
const POS_SAJI   = <?= json_encode($POS_SAJI) ?>;
const POS_GULA   = <?= json_encode($POS_GULA) ?>;
let posItems = []; // {menu_id, nama, harga, minuman, tanpa_gula, jumlah, ukuran, saji, gula}

function posTambah(m) {
  // gabung kalau menu+opsi default sama persis
  const sama = posItems.find(i => i.menu_id === m.id && i.ukuran === 'Regular' && i.saji === 'Dingin' && i.gula === 'Normal Sugar' && m.minuman)
            || (!m.minuman && posItems.find(i => i.menu_id === m.id));
  if (sama) { sama.jumlah++; posGambar(); return; }
  posItems.push({ menu_id: m.id, nama: m.nama, harga: m.harga, minuman: m.minuman, tanpa_gula: m.tanpa_gula,
                  jumlah: 1, ukuran: 'Regular', saji: 'Dingin', gula: 'Normal Sugar' });
  posGambar();
}
function posHargaSatuan(i) { return i.harga + (i.minuman ? (POS_UKURAN[i.ukuran] || 0) : 0); }
function posRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }

function posGambar() {
  const wrap = document.getElementById('posKeranjang');
  if (!posItems.length) {
    wrap.innerHTML = '<p class="text-secondary mb-0" style="font-size:13.5px">Klik menu di kiri untuk menambahkan.</p>';
  } else {
    wrap.innerHTML = posItems.map((i, x) => `
      <div style="border-bottom:1px solid var(--border);padding:12px 0">
        <div class="d-flex align-items-center gap-2">
          <span class="fw-bold flex-grow-1" style="font-size:15px">${i.nama}</span>
          <span class="fw-bold" style="font-size:14.5px">${posRupiah(posHargaSatuan(i) * i.jumlah)}</span>
        </div>
        ${i.minuman ? `
        <div class="d-flex gap-2 mt-2">
            <select class="form-select fw-semibold" style="flex:1;min-width:0;padding:12px 8px;font-size:15px" onchange="posItems[${x}].ukuran=this.value;posGambar()">
              ${Object.keys(POS_UKURAN).map(u => `<option ${i.ukuran===u?'selected':''}>${u}</option>`).join('')}
            </select>
            <select class="form-select fw-semibold" style="flex:1;min-width:0;padding:12px 8px;font-size:15px" onchange="posItems[${x}].saji=this.value;posGambar()">
              ${POS_SAJI.map(s => `<option ${i.saji===s?'selected':''}>${s}</option>`).join('')}
            </select>
            ${!i.tanpa_gula ? `
            <select class="form-select fw-semibold" style="flex:1.3;min-width:0;padding:12px 8px;font-size:15px" onchange="posItems[${x}].gula=this.value;posGambar()">
              ${POS_GULA.map(g => `<option ${i.gula===g?'selected':''}>${g}</option>`).join('')}
            </select>` : ''}
        </div>` : ''}
        <div class="d-flex align-items-center gap-2 mt-2">
          <span class="ms-auto d-flex align-items-center gap-2">
            <button type="button" class="btn btn-light" style="width:48px;height:48px;font-size:20px;padding:0" onclick="if(--posItems[${x}].jumlah<1)posItems.splice(${x},1);posGambar()"><i class="bi bi-dash-lg"></i></button>
            <span class="fw-bold px-1" style="font-size:18px;min-width:28px;text-align:center">${i.jumlah}</span>
            <button type="button" class="btn btn-light" style="width:48px;height:48px;font-size:20px;padding:0" onclick="posItems[${x}].jumlah++;posGambar()"><i class="bi bi-plus-lg"></i></button>
            <button type="button" class="btn btn-outline-danger" style="width:48px;height:48px;font-size:17px;padding:0" onclick="posItems.splice(${x},1);posGambar()"><i class="bi bi-trash"></i></button>
          </span>
        </div>
      </div>`).join('');
  }
  const total  = posItems.reduce((t, i) => t + posHargaSatuan(i) * i.jumlah, 0);
  const jumlah = posItems.reduce((t, i) => t + i.jumlah, 0);
  document.getElementById('posTotal').textContent  = posRupiah(total);
  document.getElementById('posJumlah').textContent = jumlah + ' item';
  document.getElementById('posSimpan').disabled = !posItems.length;
}

document.getElementById('posForm').addEventListener('submit', () => {
  document.getElementById('posItem').value = JSON.stringify(posItems.map(i => ({
    menu_id: i.menu_id, jumlah: i.jumlah, ukuran: i.ukuran, saji: i.saji, gula: i.gula
  })));
});

// filter kategori + cari
let katAktif = '';
document.querySelectorAll('#posKategori button').forEach(b => b.addEventListener('click', () => {
  katAktif = b.dataset.kat;
  document.querySelectorAll('#posKategori button').forEach(x => x.classList.replace('btn-primary', 'btn-light'));
  b.classList.replace('btn-light', 'btn-primary');
  posFilter();
}));
document.getElementById('posCari').addEventListener('input', posFilter);
function posFilter() {
  const q = document.getElementById('posCari').value.toLowerCase().trim();
  document.querySelectorAll('.pos-item').forEach(el => {
    const cocok = (!katAktif || el.dataset.kat === katAktif) && (!q || el.dataset.nama.includes(q));
    el.style.display = cocok ? '' : 'none';
  });
}
</script>

@include('partials.kasir_bottom')
