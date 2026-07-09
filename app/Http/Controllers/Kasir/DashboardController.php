<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $db = db();

        $pesananHariIni = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $menunggu       = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();
        $diproses       = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'diproses'")->fetchColumn();
        $siap           = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'siap'")->fetchColumn();
        $belumBayar     = (int) $db->query("SELECT COUNT(*) FROM pembayaran WHERE status = 'belum_dibayar'")->fetchColumn();

        $antrean = $db->query("
            SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja,
                   (SELECT GROUP_CONCAT(CONCAT(pi.jumlah, '× ', mn.nama) ORDER BY pi.id SEPARATOR ', ')
                    FROM pesanan_item pi JOIN menu mn ON mn.id = pi.menu_id
                    WHERE pi.pesanan_id = p.id) item_ringkas,
                   (SELECT b.status FROM pembayaran b WHERE b.pesanan_id = p.id ORDER BY b.id DESC LIMIT 1) status_bayar
            FROM pesanan p
            LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
            LEFT JOIN meja m ON m.id = p.meja_id
            WHERE p.status IN ('menunggu','diproses','siap')
            ORDER BY p.created_at ASC LIMIT 50")->fetchAll();

        /* Item terjual hari ini (pesanan yang tidak dibatalkan), per menu */
        $terjualHariIni = $db->query("
            SELECT mn.nama, SUM(pi.jumlah) qty
            FROM pesanan_item pi
            JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan' AND DATE(p.created_at) = CURDATE()
            JOIN menu mn ON mn.id = pi.menu_id
            GROUP BY pi.menu_id, mn.nama
            ORDER BY qty DESC, mn.nama")->fetchAll();
        $totalItemHariIni = array_sum(array_column($terjualHariIni, 'qty'));

        return view('kasir.dashboard', [
            'pageTitle' => 'Dashboard',
            'active'    => 'dashboard',
            'pesananHariIni'   => $pesananHariIni,
            'menunggu'         => $menunggu,
            'diproses'         => $diproses,
            'siap'             => $siap,
            'belumBayar'       => $belumBayar,
            'antrean'          => $antrean,
            'terjualHariIni'   => $terjualHariIni,
            'totalItemHariIni' => $totalItemHariIni,
        ]);
    }
}
