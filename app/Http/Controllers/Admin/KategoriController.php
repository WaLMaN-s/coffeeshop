<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post')) {
            $aksi = $request->input('aksi', '');
            $id   = (int) $request->input('id', 0);
            $nama = trim($request->input('nama', ''));

            if ($aksi === 'simpan') {
                if ($nama === '') {
                    set_flash('gagal', 'Nama kategori wajib diisi.');
                } elseif ($id > 0) {
                    $db->prepare('UPDATE kategori SET nama = ? WHERE id = ?')->execute([$nama, $id]);
                    set_flash('sukses', 'Kategori diperbarui.');
                } else {
                    $db->prepare('INSERT INTO kategori (nama) VALUES (?)')->execute([$nama]);
                    set_flash('sukses', 'Kategori ditambahkan.');
                }
            }

            if ($aksi === 'hapus') {
                $cek = $db->prepare('SELECT COUNT(*) FROM menu WHERE kategori_id = ?');
                $cek->execute([$id]);
                if ($cek->fetchColumn() > 0) {
                    set_flash('gagal', 'Kategori masih dipakai oleh menu — pindahkan menunya dulu.');
                } else {
                    $db->prepare('DELETE FROM kategori WHERE id = ?')->execute([$id]);
                    set_flash('sukses', 'Kategori dihapus.');
                }
            }

            return redirect('admin/kategori.php');
        }

        $daftar = $db->query("
            SELECT k.*, COUNT(m.id) jumlah_menu
            FROM kategori k LEFT JOIN menu m ON m.kategori_id = k.id
            GROUP BY k.id ORDER BY k.nama")->fetchAll();

        return view('admin.kategori', [
            'pageTitle' => 'Manajemen Kategori',
            'active'    => 'kategori',
            'daftar'    => $daftar,
        ]);
    }
}
