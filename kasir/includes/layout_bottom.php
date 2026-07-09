    </main>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar mobile
const sb = document.getElementById('sidebar');
const bd = document.getElementById('sidebarBackdrop');
document.getElementById('btnSidebar')?.addEventListener('click', () => {
  sb.classList.add('open'); bd.classList.add('show');
});
bd.addEventListener('click', () => { sb.classList.remove('open'); bd.classList.remove('show'); });

// Buka-tutup sidebar (desktop), pilihan diingat browser
document.getElementById('btnSidebarMini')?.addEventListener('click', () => {
  const l = document.getElementById('layoutKasir');
  l.classList.toggle('sidebar-mini');
  localStorage.setItem('kasirSidebarMini', l.classList.contains('sidebar-mini') ? '1' : '0');
});

// ---------- Suara dering pesanan masuk ----------
let suaraAktif = localStorage.getItem('notifSuara') === '1';
let audioCtx = null;
const btnSuara = document.getElementById('btnSuara');

function gambarBtnSuara() {
  btnSuara.innerHTML = suaraAktif ? '<i class="bi bi-volume-up-fill" style="color:var(--primary)"></i>' : '<i class="bi bi-volume-mute"></i>';
}
function dering() {
  try {
    audioCtx = audioCtx || new (window.AudioContext || window.webkitAudioContext)();
    if (audioCtx.state === 'suspended') audioCtx.resume();
    const t = audioCtx.currentTime;
    // "ding-dong" dua nada, diulang 2x biar kedengeran di kedai yang ramai
    [[880, 0], [1174.66, .18], [880, .9], [1174.66, 1.08]].forEach(([f, d]) => {
      const o = audioCtx.createOscillator(), g = audioCtx.createGain();
      o.type = 'sine'; o.frequency.value = f;
      g.gain.setValueAtTime(.0001, t + d);
      g.gain.exponentialRampToValueAtTime(.5, t + d + .02);
      g.gain.exponentialRampToValueAtTime(.0001, t + d + .6);
      o.connect(g).connect(audioCtx.destination);
      o.start(t + d); o.stop(t + d + .65);
    });
  } catch (e) { /* browser tanpa WebAudio */ }
}
btnSuara?.addEventListener('click', () => {
  suaraAktif = !suaraAktif;
  localStorage.setItem('notifSuara', suaraAktif ? '1' : '0');
  gambarBtnSuara();
  if (suaraAktif) dering(); // sekalian tes bunyi + buka izin audio browser
});
gambarBtnSuara();

// ---------- Notifikasi (polling tiap 5 detik) ----------
let notifIdTerakhir = null;
const judulAsli = document.title;

async function muatNotif() {
  try {
    const res = await fetch('api/notifikasi.php');
    const data = await res.json();
    const badge = document.getElementById('notifBadge');
    if (data.jumlah > 0) {
      badge.textContent = data.jumlah > 99 ? '99+' : data.jumlah;
      badge.classList.remove('d-none');
    } else {
      badge.classList.add('d-none');
      document.title = judulAsli;
    }
    // badge merah "pesanan menunggu" di sidebar
    const bp = document.getElementById('badgePesanan');
    if (bp) {
      bp.textContent = data.antrean;
      bp.classList.toggle('d-none', !(data.antrean > 0));
    }
    const ikon = { pesanan_baru: 'bi-receipt', pembayaran: 'bi-credit-card', pesanan_batal: 'bi-x-circle' };
    const list = document.getElementById('notifList');
    list.innerHTML = data.item.length === 0
      ? '<div class="notif-empty">Tidak ada notifikasi</div>'
      : data.item.map(n => `
        <a href="${n.pesanan_id ? 'pesanan_detail.php?id=' + n.pesanan_id : '#'}"
           class="notif-item ${n.dibaca == 0 ? 'baru' : ''}">
          <i class="bi ${ikon[n.tipe] || 'bi-bell'}"></i>
          <div><div class="notif-pesan">${n.pesan}</div>
          <div class="notif-waktu">${n.waktu}</div></div>
        </a>`).join('');

    // Deteksi pesanan baru sejak poll sebelumnya -> dering
    const maxId = data.item.reduce((m, n) => Math.max(m, n.id || 0), 0);
    if (notifIdTerakhir === null) {
      notifIdTerakhir = maxId; // muat pertama: jangan langsung bunyi
    } else if (maxId > notifIdTerakhir) {
      const adaPesananBaru = data.item.some(n => n.id > notifIdTerakhir && n.tipe === 'pesanan_baru' && n.dibaca == 0);
      notifIdTerakhir = maxId;
      if (adaPesananBaru) {
        if (suaraAktif) dering();
        document.title = '🔔 Pesanan baru! — ' + judulAsli;
      }
    }
  } catch (e) { /* diam saat offline */ }
}
document.getElementById('btnNotifBaca')?.addEventListener('click', async (ev) => {
  ev.stopPropagation();
  await fetch('api/notifikasi.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'aksi=baca_semua' });
  document.title = judulAsli;
  muatNotif();
});
muatNotif();
setInterval(muatNotif, 5000);
</script>
</body>
</html>
