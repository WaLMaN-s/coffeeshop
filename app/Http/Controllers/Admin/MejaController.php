<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MejaController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post')) {
            $aksi = $request->input('aksi', '');
            $id   = (int) $request->input('id', 0);

            if ($aksi === 'tambah') {
                $nomor = trim($request->input('nomor_meja', ''));
                if ($nomor === '') {
                    set_flash('gagal', 'Nomor meja wajib diisi.');
                } else {
                    $kode = bin2hex(random_bytes(8));
                    $db->prepare('INSERT INTO meja (nomor_meja, kode, status) VALUES (?,?,?)')
                       ->execute([$nomor, $kode, 'aktif']);
                    set_flash('sukses', 'Meja ' . $nomor . ' ditambahkan. QR-nya otomatis dibuat.');
                }
            }

            if ($aksi === 'simpan') {
                $nomor = trim($request->input('nomor_meja', ''));
                if ($nomor === '') {
                    set_flash('gagal', 'Nomor meja wajib diisi.');
                } else {
                    $db->prepare('UPDATE meja SET nomor_meja = ? WHERE id = ?')->execute([$nomor, $id]);
                    set_flash('sukses', 'Meja diperbarui.');
                }
            }

            if ($aksi === 'toggle') {
                $db->prepare("UPDATE meja SET status = IF(status='aktif','nonaktif','aktif') WHERE id = ?")->execute([$id]);
                set_flash('sukses', 'Status meja diubah.');
            }

            if ($aksi === 'ulang_qr') {
                $db->prepare('UPDATE meja SET kode = ? WHERE id = ?')->execute([bin2hex(random_bytes(8)), $id]);
                $lama = public_path('uploads/qrcode/meja-' . $id . '.png');
                if (is_file($lama)) @unlink($lama);
                set_flash('sukses', 'Kode QR meja diperbarui — QR lama tidak berlaku lagi.');
            }

            if ($aksi === 'hapus') {
                $cek = $db->prepare('SELECT COUNT(*) FROM pesanan WHERE meja_id = ?');
                $cek->execute([$id]);
                if ($cek->fetchColumn() > 0) {
                    set_flash('gagal', 'Meja punya riwayat pesanan dan tidak bisa dihapus. Nonaktifkan saja.');
                } else {
                    $db->prepare('DELETE FROM meja WHERE id = ?')->execute([$id]);
                    $file = public_path('uploads/qrcode/meja-' . $id . '.png');
                    if (is_file($file)) @unlink($file);
                    set_flash('sukses', 'Meja dihapus.');
                }
            }

            return redirect('admin/meja.php');
        }

        $daftar = $db->query('SELECT * FROM meja ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja')->fetchAll();

        return view('admin.meja', [
            'pageTitle' => 'Manajemen Meja & QR',
            'active'    => 'meja',
            'daftar'    => $daftar,
        ]);
    }

    public function cetak()
    {
        $db     = db();
        $daftar = $db->query("SELECT * FROM meja WHERE status='aktif' ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja")->fetchAll();

        return view('admin.meja_cetak', [
            'pageTitle' => 'Cetak QR Meja',
            'active'    => 'meja',
            'daftar'    => $daftar,
        ]);
    }

    public function scanTest()
    {
        return view('admin.scan_test', [
            'pageTitle' => 'Uji Scan QR (Kamera)',
            'active'    => 'meja',
        ]);
    }

    /** Simpan PNG QR (dikirim base64 dari qrcode.js) ke public/uploads/qrcode/. */
    public function simpanQr(Request $request)
    {
        $db      = db();
        $id      = (int) $request->input('id', 0);
        $dataUrl = $request->input('data', '');

        $stmt = $db->prepare('SELECT id FROM meja WHERE id = ?');
        $stmt->execute([$id]);
        if (!$stmt->fetch() || !preg_match('#^data:image/png;base64,([a-zA-Z0-9+/=]+)$#', $dataUrl, $m)) {
            return response()->json(['ok' => false]);
        }

        $isi    = base64_decode($m[1]);
        $folder = public_path('uploads/qrcode');
        if (!is_dir($folder)) mkdir($folder, 0775, true);
        file_put_contents($folder . '/meja-' . $id . '.png', $isi);

        return response()->json(['ok' => true, 'url' => 'uploads/qrcode/meja-' . $id . '.png']);
    }
}
