<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    public function daftar(Request $request)
    {
        $db = db();

        /* ---------- Filter ---------- */
        $q       = trim($request->query('q', ''));
        $tanggal = trim($request->query('tanggal', ''));
        $status  = trim($request->query('status', ''));

        $where  = [];
        $params = [];
        if ($q !== '') {
            $where[]  = '(pl.nama LIKE ? OR p.nama_tamu LIKE ? OR p.nomor_pesanan LIKE ?)';
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        if ($tanggal !== '') { $where[] = 'DATE(p.created_at) = ?'; $params[] = $tanggal; }
        if ($status !== '' && in_array($status, ['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'], true)) {
            $where[] = 'p.status = ?';
            $params[] = $status;
        }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja,
                   (SELECT GROUP_CONCAT(CONCAT(pi.jumlah, '× ', mn.nama) ORDER BY pi.id SEPARATOR ', ')
                    FROM pesanan_item pi JOIN menu mn ON mn.id = pi.menu_id
                    WHERE pi.pesanan_id = p.id) item_ringkas,
                   (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar
            FROM pesanan p
            LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
            LEFT JOIN meja m ON m.id = p.meja_id
            $sqlWhere ORDER BY p.created_at DESC LIMIT 200");
        $stmt->execute($params);
        $daftar = $stmt->fetchAll();

        return view('admin.pesanan', [
            'pageTitle' => 'Manajemen Pesanan',
            'active'    => 'pesanan',
            'q' => $q, 'tanggal' => $tanggal, 'status' => $status, 'daftar' => $daftar,
        ]);
    }

    public function detail(Request $request)
    {
        $db = db();
        $id = (int) $request->query('id', 0);

        /* ---------- Ubah status ---------- */
        if ($request->isMethod('post') && $request->input('aksi') === 'ubah_status') {
            $baru = $request->input('status', '');
            if (in_array($baru, ['menunggu', 'diproses', 'siap', 'selesai', 'dibatalkan'], true)) {
                $db->prepare('UPDATE pesanan SET status = ? WHERE id = ?')->execute([$baru, $id]);
                if ($baru === 'dibatalkan') {
                    $no = $db->prepare('SELECT nomor_pesanan FROM pesanan WHERE id = ?');
                    $no->execute([$id]);
                    tambah_notifikasi($db, 'pesanan_batal', 'Pesanan ' . $no->fetchColumn() . ' dibatalkan.', $id);
                }
                set_flash('sukses', 'Status pesanan diubah menjadi "' . label_status_pesanan($baru) . '".');
            }
            return redirect('admin/pesanan_detail.php?id=' . $id);
        }

        /* ---------- Data ---------- */
        $stmt = $db->prepare("
            SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, pl.email, pl.no_hp, m.nomor_meja
            FROM pesanan p
            LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
            LEFT JOIN meja m ON m.id = p.meja_id
            WHERE p.id = ?");
        $stmt->execute([$id]);
        $pesanan = $stmt->fetch();
        if (!$pesanan) {
            set_flash('gagal', 'Pesanan tidak ditemukan.');
            return redirect('admin/pesanan.php');
        }

        $stmt = $db->prepare("
            SELECT pi.*, m.nama menu, m.foto
            FROM pesanan_item pi JOIN menu m ON m.id = pi.menu_id
            WHERE pi.pesanan_id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetchAll();

        $stmt = $db->prepare('SELECT * FROM pembayaran WHERE pesanan_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$id]);
        $bayar = $stmt->fetch();

        $riwayat = [];
        if ($pesanan['pelanggan_id']) {
            $stmt = $db->prepare("
                SELECT p.id, p.nomor_pesanan, p.status, p.total, p.created_at, m.nomor_meja
                FROM pesanan p LEFT JOIN meja m ON m.id = p.meja_id
                WHERE p.pelanggan_id = ? AND p.id <> ?
                ORDER BY p.created_at DESC LIMIT 5");
            $stmt->execute([$pesanan['pelanggan_id'], $id]);
            $riwayat = $stmt->fetchAll();
        }

        return view('admin.pesanan_detail', [
            'pageTitle' => 'Detail Pesanan',
            'active'    => 'pesanan',
            'pesanan' => $pesanan, 'item' => $item, 'bayar' => $bayar, 'riwayat' => $riwayat,
        ]);
    }
}
