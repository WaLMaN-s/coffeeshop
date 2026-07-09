<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function daftar()
    {
        $db   = db();
        $meja = session('meja');

        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM pesanan_item pi WHERE pi.pesanan_id = p.id) jumlah_item,
                   (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar,
                   (SELECT b.metode FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) metode
            FROM pesanan p WHERE p.meja_id = ? AND p.sesi_kode = ?
            ORDER BY p.created_at DESC LIMIT 50");
        $stmt->execute([$meja['meja_id'], $meja['sesi']]);
        $daftar = $stmt->fetchAll();

        return view('site.pesanan_saya', [
            'pageTitle' => 'Pesanan Saya',
            'activeNav' => 'pesanan',
            'daftar'    => $daftar,
        ]);
    }

    /**
     * Status ringkas untuk polling real-time di halaman pelanggan.
     * ?id=N -> status satu pesanan; tanpa id -> sidik jari seluruh pesanan sesi
     * ini (halaman "Pesanan Saya" reload saat ada perubahan).
     */
    public function status(Request $request)
    {
        $db   = db();
        $meja = session('meja');
        $id   = (int) $request->query('id', 0);

        if ($id > 0) {
            $stmt = $db->prepare('
                SELECT p.status,
                       (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar
                FROM pesanan p WHERE p.id = ? AND p.meja_id = ? AND p.sesi_kode = ?');
            $stmt->execute([$id, $meja['meja_id'], $meja['sesi']]);
            $row = $stmt->fetch();
            if (!$row) return response()->json(['ok' => false]);
            return response()->json(['ok' => true, 'status' => $row['status'], 'bayar' => $row['status_bayar'] ?? '']);
        }

        $stmt = $db->prepare("
            SELECT MD5(GROUP_CONCAT(CONCAT(p.id, ':', p.status, ':', COALESCE(
                (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1), '-')
            ) ORDER BY p.id))
            FROM pesanan p WHERE p.meja_id = ? AND p.sesi_kode = ?");
        $stmt->execute([$meja['meja_id'], $meja['sesi']]);
        return response()->json(['ok' => true, 'sidik' => (string) $stmt->fetchColumn()]);
    }

    public function lihat(Request $request)
    {
        $db   = db();
        $meja = session('meja');
        $id   = (int) $request->query('id', 0);

        $stmt = $db->prepare('SELECT * FROM pesanan WHERE id = ? AND meja_id = ? AND sesi_kode = ?');
        $stmt->execute([$id, $meja['meja_id'], $meja['sesi']]);
        $pesanan = $stmt->fetch();
        if (!$pesanan) {
            set_flash('gagal', 'Pesanan tidak ditemukan.');
            return redirect('pesanan_saya.php');
        }

        /* ---------- Batalkan (hanya saat masih menunggu) ---------- */
        if ($request->isMethod('post') && $request->input('aksi') === 'batal') {
            if ($pesanan['status'] === 'menunggu') {
                $db->prepare("UPDATE pesanan SET status = 'dibatalkan' WHERE id = ?")->execute([$id]);
                $db->prepare("UPDATE pembayaran SET status = 'gagal' WHERE pesanan_id = ? AND status = 'belum_dibayar'")->execute([$id]);
                tambah_notifikasi($db, 'pesanan_batal', 'Pesanan ' . $pesanan['nomor_pesanan'] . ' dibatalkan pelanggan.', $id);
                set_flash('sukses', 'Pesanan dibatalkan.');
            } else {
                set_flash('gagal', 'Pesanan sudah diproses dan tidak bisa dibatalkan.');
            }
            return redirect('pesanan_lihat.php?id=' . $id);
        }

        $stmt = $db->prepare('
            SELECT pi.*, m.nama menu, m.foto FROM pesanan_item pi
            JOIN menu m ON m.id = pi.menu_id WHERE pi.pesanan_id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetchAll();

        $stmt = $db->prepare('SELECT * FROM pembayaran WHERE pesanan_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$id]);
        $bayar = $stmt->fetch();

        /* Progres status */
        $tahap      = ['menunggu' => 0, 'diproses' => 1, 'siap' => 2, 'selesai' => 3];
        $posisi     = $tahap[$pesanan['status']] ?? -1;
        $batal      = $pesanan['status'] === 'dibatalkan';
        $labelTahap = ['Menunggu', 'Diproses', 'Siap Diambil', 'Selesai'];

        return view('site.pesanan_lihat', [
            'pageTitle'  => 'Detail Pesanan',
            'activeNav'  => 'pesanan',
            'pesanan'    => $pesanan,
            'item'       => $item,
            'bayar'      => $bayar,
            'posisi'     => $posisi,
            'batal'      => $batal,
            'labelTahap' => $labelTahap,
        ]);
    }
}
