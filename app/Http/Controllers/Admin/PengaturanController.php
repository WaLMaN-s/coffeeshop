<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PengaturanController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post')) {
            $nama      = trim($request->input('nama_toko', ''));
            $alamat    = trim($request->input('alamat', ''));
            $wa        = preg_replace('/[^0-9+]/', '', $request->input('whatsapp', ''));
            $jam       = trim($request->input('jam_operasional', ''));
            $deskripsi = trim($request->input('deskripsi', ''));
            $wifiSsid  = trim($request->input('wifi_ssid', ''));
            $wifiPass  = trim($request->input('wifi_password', ''));

            if ($nama === '') {
                set_flash('gagal', 'Nama toko wajib diisi.');
            } else {
                $err1 = $err2 = null;
                $logo   = upload_gambar('logo', 'toko', $err1);
                $banner = upload_gambar('banner', 'toko', $err2);
                if ($err1 || $err2) {
                    set_flash('gagal', $err1 ?: $err2);
                } else {
                    $lama = $db->query('SELECT logo, banner FROM pengaturan WHERE id = 1')->fetch();
                    if ($logo)   hapus_gambar($lama['logo'] ?? null, 'toko');
                    if ($banner) hapus_gambar($lama['banner'] ?? null, 'toko');

                    $db->prepare('
                        UPDATE pengaturan SET nama_toko = ?, alamat = ?, whatsapp = ?, jam_operasional = ?, deskripsi = ?,
                               wifi_ssid = ?, wifi_password = ?,
                               logo = COALESCE(?, logo), banner = COALESCE(?, banner)
                        WHERE id = 1')
                       ->execute([$nama, $alamat, $wa, $jam, $deskripsi, $wifiSsid ?: null, $wifiPass ?: null, $logo, $banner]);
                    set_flash('sukses', 'Pengaturan toko berhasil disimpan.');
                }
            }
            return redirect('admin/pengaturan.php');
        }

        $p = $db->query('SELECT * FROM pengaturan WHERE id = 1')->fetch();

        return view('admin.pengaturan', [
            'pageTitle' => 'Pengaturan Toko',
            'active'    => 'pengaturan',
            'p'         => $p,
        ]);
    }
}
