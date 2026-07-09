<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;

class PendapatanController extends Controller
{
    public function index()
    {
        $db = db();

        $hariIni = (float) $db->query("
            SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
            WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) = CURDATE()")->fetchColumn();
        $mingguIni = (float) $db->query("
            SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
            WHERE status = 'sudah_dibayar' AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)")->fetchColumn();
        $bulanIni = (float) $db->query("
            SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
            WHERE status = 'sudah_dibayar'
              AND YEAR(tanggal_bayar) = YEAR(CURDATE()) AND MONTH(tanggal_bayar) = MONTH(CURDATE())")->fetchColumn();

        $perHari = $db->query("
            SELECT DATE(tanggal_bayar) tgl, COUNT(*) transaksi, SUM(jumlah) total
            FROM pembayaran
            WHERE status = 'sudah_dibayar' AND tanggal_bayar IS NOT NULL
            GROUP BY DATE(tanggal_bayar)
            ORDER BY tgl DESC
            LIMIT 30")->fetchAll();

        return view('kasir.pendapatan', [
            'pageTitle' => 'Pendapatan',
            'active'    => 'pendapatan',
            'hariIni' => $hariIni, 'mingguIni' => $mingguIni, 'bulanIni' => $bulanIni,
            'perHari' => $perHari,
        ]);
    }
}
