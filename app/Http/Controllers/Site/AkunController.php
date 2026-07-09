<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AkunController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post') && $request->input('aksi') === 'ganti_nama') {
            $nama = trim($request->input('nama', ''));
            $noHp = trim($request->input('no_hp', ''));
            if ($nama === '') {
                set_flash('gagal', 'Nama wajib diisi.');
            } elseif ($noHp === '' || !preg_match('/^[0-9+ -]{8,20}$/', $noHp)) {
                set_flash('gagal', 'Nomor HP wajib diisi dengan format yang benar.');
            } else {
                // Simpan juga ke tabel pelanggan, bukan cuma sesi.
                $meja = session('meja');
                $meja['pelanggan_id'] = cari_atau_buat_pelanggan($db, $nama, $noHp);
                $meja['nama']  = $nama;
                $meja['no_hp'] = $noHp;
                session(['meja' => $meja]);
                set_flash('sukses', 'Data kamu diperbarui.');
            }
            return redirect('akun.php');
        }

        return view('site.akun', [
            'pageTitle' => 'Akun Saya',
            'activeNav' => 'akun',
        ]);
    }
}
