<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PelangganController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post')) {
            $aksi = $request->input('aksi', '');
            $id   = (int) $request->input('id', 0);

            if ($aksi === 'simpan') {
                $nama  = trim($request->input('nama', ''));
                $email = trim($request->input('email', ''));
                $noHp  = trim($request->input('no_hp', ''));
                if ($nama === '') {
                    set_flash('gagal', 'Nama pelanggan wajib diisi.');
                } else {
                    $db->prepare('UPDATE pelanggan SET nama = ?, email = ?, no_hp = ? WHERE id = ?')
                       ->execute([$nama, $email ?: null, $noHp ?: null, $id]);
                    set_flash('sukses', 'Data pelanggan diperbarui.');
                }
            }

            if ($aksi === 'hapus') {
                $cek = $db->prepare('SELECT COUNT(*) FROM pesanan WHERE pelanggan_id = ?');
                $cek->execute([$id]);
                if ($cek->fetchColumn() > 0) {
                    set_flash('gagal', 'Pelanggan memiliki riwayat pesanan dan tidak bisa dihapus.');
                } else {
                    $db->prepare('DELETE FROM pelanggan WHERE id = ?')->execute([$id]);
                    set_flash('sukses', 'Pelanggan dihapus.');
                }
            }

            return redirect('admin/pelanggan.php');
        }

        $q      = trim($request->query('q', ''));
        $params = [];
        $sqlWhere = '';
        if ($q !== '') {
            $sqlWhere = 'WHERE pl.nama LIKE ? OR pl.email LIKE ? OR pl.no_hp LIKE ?';
            $params   = ["%$q%", "%$q%", "%$q%"];
        }
        $stmt = $db->prepare("
            SELECT pl.*, COUNT(p.id) jumlah_pesanan, COALESCE(SUM(CASE WHEN p.status='selesai' THEN p.total END),0) total_belanja,
                   MAX(p.created_at) kunjungan_terakhir
            FROM pelanggan pl LEFT JOIN pesanan p ON p.pelanggan_id = pl.id
            $sqlWhere GROUP BY pl.id ORDER BY kunjungan_terakhir DESC, pl.created_at DESC");
        $stmt->execute($params);
        $daftar = $stmt->fetchAll();

        return view('admin.pelanggan', [
            'pageTitle' => 'Manajemen Pelanggan',
            'active'    => 'pelanggan',
            'q' => $q, 'daftar' => $daftar,
        ]);
    }
}
