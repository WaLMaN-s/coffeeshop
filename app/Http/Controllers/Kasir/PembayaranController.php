<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PembayaranController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        /* ---------- Aksi verifikasi / ubah status ---------- */
        if ($request->isMethod('post') && $request->input('aksi') === 'ubah_status') {
            $id   = (int) $request->input('id');
            $baru = $request->input('status', '');
            if (in_array($baru, ['belum_dibayar', 'sudah_dibayar', 'gagal'], true)) {
                if ($baru === 'sudah_dibayar') {
                    $db->prepare("UPDATE pembayaran SET status = ?, tanggal_bayar = NOW() WHERE id = ?")->execute([$baru, $id]);
                    $info = $db->prepare('SELECT p.id, p.nomor_pesanan FROM pembayaran b JOIN pesanan p ON p.id = b.pesanan_id WHERE b.id = ?');
                    $info->execute([$id]);
                    if ($row = $info->fetch()) {
                        tambah_notifikasi($db, 'pembayaran', 'Pembayaran pesanan ' . $row['nomor_pesanan'] . ' berhasil diverifikasi.', (int) $row['id']);
                    }
                } else {
                    $db->prepare("UPDATE pembayaran SET status = ?, tanggal_bayar = NULL WHERE id = ?")->execute([$baru, $id]);
                }
                set_flash('sukses', 'Status pembayaran diubah menjadi "' . label_status_bayar($baru) . '".');
            }
            $kembali = $request->input('kembali', '');
            return redirect('kasir/pembayaran.php' . ($kembali ? '?' . $kembali : ''));
        }

        /* ---------- Filter ---------- */
        $q      = trim($request->query('q', ''));
        $status = trim($request->query('status', ''));
        $where  = [];
        $params = [];
        if ($q !== '') {
            $where[]  = '(pl.nama LIKE ? OR p.nama_tamu LIKE ? OR p.nomor_pesanan LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($status !== '' && in_array($status, ['belum_dibayar', 'sudah_dibayar', 'gagal'], true)) {
            $where[] = 'b.status = ?';
            $params[] = $status;
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT b.*, p.nomor_pesanan, p.id pesanan_id, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja
            FROM pembayaran b
            JOIN pesanan p ON p.id = b.pesanan_id
            LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
            LEFT JOIN meja m ON m.id = p.meja_id
            $sqlWhere ORDER BY b.created_at DESC LIMIT 200");
        $stmt->execute($params);
        $daftar = $stmt->fetchAll();

        return view('kasir.pembayaran', [
            'pageTitle' => 'Pembayaran',
            'active'    => 'pembayaran',
            'q' => $q, 'status' => $status, 'daftar' => $daftar,
        ]);
    }
}
