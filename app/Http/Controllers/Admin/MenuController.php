<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        /* ---------- Aksi ---------- */
        if ($request->isMethod('post')) {
            $aksi = $request->input('aksi', '');

            if ($aksi === 'simpan') {
                $id        = (int) $request->input('id', 0);
                $nama      = trim($request->input('nama', ''));
                $kategori  = (int) $request->input('kategori_id', 0);
                $harga     = (float) str_replace('.', '', $request->input('harga', '0'));
                $deskripsi = trim($request->input('deskripsi', ''));
                $status    = $request->input('status') === 'nonaktif' ? 'nonaktif' : 'aktif';
                $tanpaGula = $request->input('tanpa_gula') ? 1 : 0;

                if ($nama === '' || $kategori <= 0 || $harga <= 0) {
                    set_flash('gagal', 'Nama, kategori, dan harga wajib diisi.');
                } else {
                    $errUpload = null;
                    $foto = upload_gambar('foto', 'menu', $errUpload);
                    if ($errUpload) {
                        set_flash('gagal', $errUpload);
                    } elseif ($id > 0) {
                        if ($foto) {
                            $lama = $db->prepare('SELECT foto FROM menu WHERE id = ?');
                            $lama->execute([$id]);
                            hapus_gambar($lama->fetchColumn(), 'menu');
                            $db->prepare('UPDATE menu SET kategori_id=?, nama=?, harga=?, deskripsi=?, tanpa_gula=?, status=?, foto=? WHERE id=?')
                               ->execute([$kategori, $nama, $harga, $deskripsi, $tanpaGula, $status, $foto, $id]);
                        } else {
                            $db->prepare('UPDATE menu SET kategori_id=?, nama=?, harga=?, deskripsi=?, tanpa_gula=?, status=? WHERE id=?')
                               ->execute([$kategori, $nama, $harga, $deskripsi, $tanpaGula, $status, $id]);
                        }
                        set_flash('sukses', 'Menu berhasil diperbarui.');
                    } else {
                        $db->prepare('INSERT INTO menu (kategori_id, nama, harga, deskripsi, tanpa_gula, status, foto) VALUES (?,?,?,?,?,?,?)')
                           ->execute([$kategori, $nama, $harga, $deskripsi, $tanpaGula, $status, $foto]);
                        set_flash('sukses', 'Menu baru berhasil ditambahkan.');
                    }
                }
            }

            if ($aksi === 'toggle') {
                $id = (int) $request->input('id');
                $db->prepare("UPDATE menu SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([$id]);
                set_flash('sukses', 'Status menu diubah.');
            }

            if ($aksi === 'hapus') {
                $id  = (int) $request->input('id');
                $ada = $db->prepare('SELECT COUNT(*) FROM pesanan_item WHERE menu_id = ?');
                $ada->execute([$id]);
                if ($ada->fetchColumn() > 0) {
                    set_flash('gagal', 'Menu sudah dipakai di pesanan — nonaktifkan saja agar riwayat tetap utuh.');
                } else {
                    $lama = $db->prepare('SELECT foto FROM menu WHERE id = ?');
                    $lama->execute([$id]);
                    hapus_gambar($lama->fetchColumn(), 'menu');
                    $db->prepare('DELETE FROM menu WHERE id = ?')->execute([$id]);
                    set_flash('sukses', 'Menu berhasil dihapus.');
                }
            }

            $q = $request->query('q', '');
            return redirect('admin/menu.php' . ($q ? '?q=' . urlencode($q) : ''));
        }

        /* ---------- Data ---------- */
        $q      = trim($request->query('q', ''));
        $fkat   = (int) $request->query('kategori', 0);
        $where  = [];
        $params = [];
        if ($q !== '')   { $where[] = 'm.nama LIKE ?';      $params[] = "%$q%"; }
        if ($fkat > 0)   { $where[] = 'm.kategori_id = ?';  $params[] = $fkat; }
        $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $db->prepare("
            SELECT m.*, k.nama kategori FROM menu m
            JOIN kategori k ON k.id = m.kategori_id
            $sqlWhere ORDER BY m.created_at DESC");
        $stmt->execute($params);
        $daftarMenu = $stmt->fetchAll();
        $daftarKategori = $db->query('SELECT * FROM kategori ORDER BY nama')->fetchAll();

        return view('admin.menu', [
            'pageTitle' => 'Manajemen Menu',
            'active'    => 'menu',
            'q' => $q, 'fkat' => $fkat,
            'daftarMenu' => $daftarMenu, 'daftarKategori' => $daftarKategori,
        ]);
    }
}
