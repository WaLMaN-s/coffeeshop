<?php
/**
 * Fungsi bersama yang dipakai lintas sisi (pelanggan + kasir).
 * Dipisah dari config.php karena config.php berbeda per server
 * (kredensial DB) dan tidak ikut di-deploy.
 */

/**
 * Cari data pelanggan berdasarkan no. HP, atau buat baris baru kalau belum
 * ada; nama disamakan dengan input terakhir. Dipakai di cek-in QR meja
 * (meja.php/akun.php) dan POS kasir (kasir/pesanan_baru.php).
 */
function cari_atau_buat_pelanggan(PDO $db, string $nama, string $noHp): int
{
    $stmt = $db->prepare('SELECT id FROM pelanggan WHERE no_hp = ? LIMIT 1');
    $stmt->execute([$noHp]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $db->prepare('UPDATE pelanggan SET nama = ? WHERE id = ?')->execute([$nama, (int) $id]);
        return (int) $id;
    }

    $db->prepare('INSERT INTO pelanggan (nama, no_hp) VALUES (?, ?)')->execute([$nama, $noHp]);
    return (int) $db->lastInsertId();
}

/**
 * Nomor antrian harian, diambil dari urutan di nomor pesanan
 * (ORD-YYYYMMDD-0007 -> "7"). Reset otomatis tiap hari karena
 * nomor pesanan memang berurut per tanggal.
 */
function no_antrian(string $nomorPesanan): string
{
    $urut = substr($nomorPesanan, strrpos($nomorPesanan, '-') + 1);
    return ltrim($urut, '0') ?: '0';
}
