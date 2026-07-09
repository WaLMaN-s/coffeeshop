<table border="1">
  <tr><td colspan="3"><b><?= e($namaToko) ?> — Laporan Penjualan</b></td></tr>
  <tr><td colspan="3">Periode: <?= tanggal_id($mulai) ?> s.d. <?= tanggal_id($sampai) ?></td></tr>
  <tr><td colspan="3"></td></tr>
  <tr><td><b>Total Transaksi</b></td><td colspan="2"><?= $ringkasan['transaksi'] ?></td></tr>
  <tr><td><b>Total Pendapatan</b></td><td colspan="2"><?= rupiah($ringkasan['pendapatan']) ?></td></tr>
  <tr><td><b>Jumlah Pesanan</b></td><td colspan="2"><?= $ringkasan['jumlah_pesanan'] ?></td></tr>
  <tr><td colspan="3"></td></tr>

  <tr><td colspan="3"><b>Rincian Per Hari</b></td></tr>
  <tr><td><b>Tanggal</b></td><td><b>Transaksi</b></td><td><b>Pendapatan</b></td></tr>
  <?php foreach ($perHari as $h): ?>
  <tr>
    <td><?= tanggal_id($h['tgl']) ?></td>
    <td><?= (int) $h['transaksi'] ?></td>
    <td><?= (float) $h['pendapatan'] ?></td>
  </tr>
  <?php endforeach; ?>
  <tr><td colspan="3"></td></tr>

  <tr><td colspan="3"><b>Menu Terlaris</b></td></tr>
  <tr><td><b>Menu</b></td><td><b>Terjual</b></td><td><b>Omzet</b></td></tr>
  <?php foreach ($terlarisLaporan as $m): ?>
  <tr>
    <td><?= e($m['nama']) ?> (<?= e($m['kategori']) ?>)</td>
    <td><?= (int) $m['terjual'] ?></td>
    <td><?= (float) $m['omzet'] ?></td>
  </tr>
  <?php endforeach; ?>
</table>
