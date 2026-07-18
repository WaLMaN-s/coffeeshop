<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MejaController extends Controller
{
    /** Cek-in QR meja: form nama + no. HP. */
    public function form(Request $request)
    {
        $db   = db();
        $kode = trim($request->input('kode', $request->query('kode', '')));

        // Sudah punya sesi dan tidak sedang pindah meja -> langsung ke menu.
        if ($kode === '' && meja_aktif()) {
            return redirect('index.php');
        }

        $meja  = null;
        $error = '';
        if ($kode !== '') {
            $stmt = $db->prepare("SELECT * FROM meja WHERE kode = ? AND status = 'aktif'");
            $stmt->execute([$kode]);
            $meja = $stmt->fetch();
            if (!$meja) {
                $error = 'Kode QR tidak dikenali atau meja sedang tidak aktif. Coba scan ulang atau panggil staf kami.';
            }
        }

        if ($request->isMethod('post') && $meja) {
            $nama = trim($request->input('nama', ''));
            $noHp = trim($request->input('no_hp', ''));
            if ($nama === '') {
                $error = 'Nama wajib diisi dulu ya.';
            } elseif ($noHp === '' || !preg_match('/^[0-9+ -]{8,20}$/', $noHp)) {
                $error = 'Nomor HP wajib diisi dengan format yang benar.';
            } else {
                $pelangganId = cari_atau_buat_pelanggan($db, $nama, $noHp);
                session()->regenerate();
                session([
                    'meja' => [
                        'meja_id'      => (int) $meja['id'],
                        'nomor_meja'   => $meja['nomor_meja'],
                        'nama'         => $nama,
                        'no_hp'        => $noHp,
                        'pelanggan_id' => $pelangganId,
                        'sesi'         => bin2hex(random_bytes(16)),
                    ],
                    'keranjang' => [],
                ]);
                set_flash('sukses', 'Selamat datang, ' . $nama . '! Kamu di Meja ' . $meja['nomor_meja'] . '.');
                return redirect('index.php');
            }
        }

        $pengaturan = get_pengaturan($db);
        $namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';

        return view('site.meja', compact('kode', 'meja', 'error', 'pengaturan', 'namaToko'));
    }

    /** Terima laporan kendala kamera dari halaman scan (untuk debug perangkat pelanggan). */
    public function kameraLog(Request $request)
    {
        $teks = substr(preg_replace('/[\x00-\x1f\x7f]/', ' ', (string) $request->getContent()), 0, 1000);
        if ($teks !== '') {
            $baris = '[' . date('Y-m-d H:i:s') . '] ' . $request->ip() . ' | ' . $teks . PHP_EOL;
            @file_put_contents(storage_path('logs/kamera-debug.log'), $baris, FILE_APPEND | LOCK_EX);
        }
        return response()->noContent();
    }

    /** Akhiri sesi meja. */
    public function keluar()
    {
        session()->forget(['meja', 'keranjang']);
        set_flash('sukses', 'Sesi meja diakhiri. Sampai jumpa lagi!');
        return redirect('meja.php');
    }
}
