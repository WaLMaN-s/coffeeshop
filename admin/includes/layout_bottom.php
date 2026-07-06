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

// Notifikasi (polling ringan tiap 20 detik)
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
  } catch (e) { /* diam saat offline */ }
}
document.getElementById('btnNotifBaca')?.addEventListener('click', async (ev) => {
  ev.stopPropagation();
  await fetch('api/notifikasi.php', { method: 'POST', headers: {'Content-Type': 'application/x-www-form-urlencoded'}, body: 'aksi=baca_semua' });
  muatNotif();
});
muatNotif();
setInterval(muatNotif, 20000);
</script>
</body>
</html>
