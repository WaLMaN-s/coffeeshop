<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KasirAkunController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post')) {
            $aksi = $request->input('aksi', '');
            $id   = (int) $request->input('id', 0);

            if ($aksi === 'tambah') {
                $username = trim($request->input('username', ''));
                $nama     = trim($request->input('nama', ''));
                $password = $request->input('password', '');
                if ($username === '' || $nama === '' || strlen($password) < 6) {
                    set_flash('gagal', 'Username, nama wajib diisi, dan password minimal 6 karakter.');
                } else {
                    $cek = $db->prepare('SELECT COUNT(*) FROM kasir WHERE username = ?');
                    $cek->execute([$username]);
                    if ($cek->fetchColumn() > 0) {
                        set_flash('gagal', 'Username sudah dipakai.');
                    } else {
                        $db->prepare('INSERT INTO kasir (username, password, nama) VALUES (?,?,?)')
                           ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama]);
                        set_flash('sukses', 'Akun kasir ' . $nama . ' dibuat.');
                    }
                }
            }

            if ($aksi === 'simpan') {
                $nama     = trim($request->input('nama', ''));
                $password = $request->input('password', '');
                if ($nama === '') {
                    set_flash('gagal', 'Nama wajib diisi.');
                } else {
                    if ($password !== '') {
                        if (strlen($password) < 6) {
                            set_flash('gagal', 'Password baru minimal 6 karakter.');
                            return redirect('admin/kasir.php');
                        }
                        $db->prepare('UPDATE kasir SET nama = ?, password = ? WHERE id = ?')
                           ->execute([$nama, password_hash($password, PASSWORD_DEFAULT), $id]);
                    } else {
                        $db->prepare('UPDATE kasir SET nama = ? WHERE id = ?')->execute([$nama, $id]);
                    }
                    set_flash('sukses', 'Akun kasir diperbarui.');
                }
            }

            if ($aksi === 'hapus') {
                $db->prepare('DELETE FROM kasir WHERE id = ?')->execute([$id]);
                set_flash('sukses', 'Akun kasir dihapus.');
            }

            return redirect('admin/kasir.php');
        }

        $daftar = $db->query('SELECT * FROM kasir ORDER BY nama')->fetchAll();

        return view('admin.kasir', [
            'pageTitle' => 'Akun Kasir',
            'active'    => 'kasir',
            'daftar'    => $daftar,
        ]);
    }
}
