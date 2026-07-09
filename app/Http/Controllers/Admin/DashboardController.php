<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        $db = db();

        /* ---------- Statistik kartu ---------- */
        $totalMenu      = (int) $db->query('SELECT COUNT(*) FROM menu')->fetchColumn();
        $totalKategori  = (int) $db->query('SELECT COUNT(*) FROM kategori')->fetchColumn();
        $totalMejaAktif = (int) $db->query("SELECT COUNT(*) FROM meja WHERE status = 'aktif'")->fetchColumn();

        $pesananHariIni = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        $pesananSelesai = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'selesai'")->fetchColumn();
        $pesananProses  = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'diproses'")->fetchColumn();

        $pendapatanHariIni = (float) $db->query("
            SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
            WHERE status = 'sudah_dibayar' AND DATE(tanggal_bayar) = CURDATE()")->fetchColumn();
        $pendapatanBulanIni = (float) $db->query("
            SELECT COALESCE(SUM(jumlah),0) FROM pembayaran
            WHERE status = 'sudah_dibayar'
              AND YEAR(tanggal_bayar) = YEAR(CURDATE()) AND MONTH(tanggal_bayar) = MONTH(CURDATE())")->fetchColumn();

        /* ---------- Grafik harian: pendapatan 14 hari terakhir ---------- */
        $harian = [];
        for ($i = 13; $i >= 0; $i--) {
            $tgl = date('Y-m-d', strtotime("-$i day"));
            $harian[$tgl] = 0;
        }
        $rows = $db->query("
            SELECT DATE(tanggal_bayar) tgl, SUM(jumlah) total FROM pembayaran
            WHERE status = 'sudah_dibayar' AND tanggal_bayar >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
            GROUP BY DATE(tanggal_bayar)")->fetchAll();
        foreach ($rows as $r) {
            if (isset($harian[$r['tgl']])) $harian[$r['tgl']] = (float) $r['total'];
        }
        $labelHarian = array_map(fn ($t) => date('j/n', strtotime($t)), array_keys($harian));
        $dataHarian  = array_values($harian);

        /* ---------- Grafik bulanan: pendapatan 12 bulan terakhir ---------- */
        $bulanan = [];
        for ($i = 11; $i >= 0; $i--) {
            $bulanan[date('Y-m', strtotime("first day of -$i month"))] = 0;
        }
        $rows = $db->query("
            SELECT DATE_FORMAT(tanggal_bayar, '%Y-%m') bln, SUM(jumlah) total FROM pembayaran
            WHERE status = 'sudah_dibayar'
              AND tanggal_bayar >= DATE_SUB(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 11 MONTH)
            GROUP BY DATE_FORMAT(tanggal_bayar, '%Y-%m')")->fetchAll();
        foreach ($rows as $r) {
            if (isset($bulanan[$r['bln']])) $bulanan[$r['bln']] = (float) $r['total'];
        }
        $namaBulan    = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $labelBulanan = array_map(fn ($b) => $namaBulan[(int) substr($b, 5)] . ' ' . substr($b, 2, 2), array_keys($bulanan));
        $dataBulanan  = array_values($bulanan);

        /* ---------- Menu terlaris (top 5, semua waktu) ---------- */
        $terlaris = $db->query("
            SELECT m.nama, k.nama kategori, SUM(pi.jumlah) terjual
            FROM pesanan_item pi
            JOIN menu m ON m.id = pi.menu_id
            JOIN kategori k ON k.id = m.kategori_id
            JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan'
            GROUP BY pi.menu_id ORDER BY terjual DESC LIMIT 5")->fetchAll();
        $maxTerjual = $terlaris ? max(array_column($terlaris, 'terjual')) : 1;

        /* ---------- Item terjual hari ini, per menu ---------- */
        $terjualHariIni = $db->query("
            SELECT mn.nama, SUM(pi.jumlah) qty
            FROM pesanan_item pi
            JOIN pesanan p ON p.id = pi.pesanan_id AND p.status <> 'dibatalkan' AND DATE(p.created_at) = CURDATE()
            JOIN menu mn ON mn.id = pi.menu_id
            GROUP BY pi.menu_id, mn.nama
            ORDER BY qty DESC, mn.nama")->fetchAll();
        $totalItemHariIni = array_sum(array_column($terjualHariIni, 'qty'));

        /* ---------- 5 pesanan terbaru ---------- */
        $terbaru = $db->query("
            SELECT p.*, COALESCE(pl.nama, p.nama_tamu, 'Tamu') pelanggan, m.nomor_meja
            FROM pesanan p
            LEFT JOIN pelanggan pl ON pl.id = p.pelanggan_id
            LEFT JOIN meja m ON m.id = p.meja_id
            ORDER BY p.created_at DESC LIMIT 5")->fetchAll();

        return view('admin.dashboard', [
            'pageTitle' => 'Dashboard',
            'active'    => 'dashboard',
            'totalMenu' => $totalMenu, 'totalKategori' => $totalKategori, 'totalMejaAktif' => $totalMejaAktif,
            'pesananHariIni' => $pesananHariIni, 'pesananSelesai' => $pesananSelesai, 'pesananProses' => $pesananProses,
            'pendapatanHariIni' => $pendapatanHariIni, 'pendapatanBulanIni' => $pendapatanBulanIni,
            'labelHarian' => $labelHarian, 'dataHarian' => $dataHarian,
            'labelBulanan' => $labelBulanan, 'dataBulanan' => $dataBulanan,
            'terlaris' => $terlaris, 'maxTerjual' => $maxTerjual,
            'terjualHariIni' => $terjualHariIni, 'totalItemHariIni' => $totalItemHariIni,
            'terbaru' => $terbaru,
        ]);
    }
}
