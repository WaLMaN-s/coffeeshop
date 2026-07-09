<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post') && $request->input('aksi') === 'simpan') {
            $id   = (int) $request->input('id', 0);
            $nama = trim($request->input('nama', ''));
            $noHp = trim($request->input('no_hp', ''));
            if ($nama === '') {
                set_flash('gagal', 'Nama pelanggan wajib diisi.');
            } else {
                $db->prepare('UPDATE pelanggan SET nama = ?, no_hp = ? WHERE id = ?')->execute([$nama, $noHp ?: null, $id]);
                set_flash('sukses', 'Data pelanggan diperbarui.');
            }
            return redirect('kasir/pelanggan.php');
        }

        $q      = trim($request->query('q', ''));
        $params = [];
        $sqlWhere = '';
        if ($q !== '') {
            $sqlWhere = 'WHERE pl.nama LIKE ? OR pl.no_hp LIKE ?';
            $params   = ["%$q%", "%$q%"];
        }
        $stmt = $db->prepare("
            SELECT pl.*, COUNT(p.id) jumlah_pesanan, COALESCE(SUM(CASE WHEN p.status='selesai' THEN p.total END),0) total_belanja,
                   MAX(p.created_at) kunjungan_terakhir
            FROM pelanggan pl LEFT JOIN pesanan p ON p.pelanggan_id = pl.id
            $sqlWhere GROUP BY pl.id ORDER BY kunjungan_terakhir DESC, pl.created_at DESC");
        $stmt->execute($params);
        $daftar = $stmt->fetchAll();

        return view('kasir.pelanggan', [
            'pageTitle' => 'Pelanggan',
            'active'    => 'pelanggan',
            'q' => $q, 'daftar' => $daftar,
        ]);
    }
}
