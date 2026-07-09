<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KeranjangController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        /* ---------- Aksi keranjang ---------- */
        if ($request->isMethod('post')) {
            $aksi  = $request->input('aksi', '');
            $ajax  = $request->header('X-Requested-With') === 'fetch';
            $pesan = '';
            $krj   = keranjang();

            if ($aksi === 'tambah') {
                $menuId = (int) $request->input('menu_id', 0);
                $jumlah = max(1, min(20, (int) $request->input('jumlah', 1)));
                $cek = $db->prepare("
                    SELECT m.nama, m.tanpa_gula, k.nama kategori FROM menu m
                    JOIN kategori k ON k.id = m.kategori_id
                    WHERE m.id = ? AND m.status = 'aktif'");
                $cek->execute([$menuId]);
                if ($m = $cek->fetch()) {
                    $minuman = in_array($m['kategori'], KATEGORI_MINUMAN, true);
                    $ukuran  = $saji = $gula = null;
                    if ($minuman) {
                        $ukuran = array_key_exists($request->input('ukuran', ''), UKURAN_OPSI) ? $request->input('ukuran') : 'Regular';
                        $saji   = in_array($request->input('saji', ''), SAJI_OPSI, true) ? $request->input('saji') : 'Dingin';
                        // menu tanpa_gula (Espresso, Americano, dll) tidak menyimpan opsi gula
                        $gula   = $m['tanpa_gula'] ? null
                                : (in_array($request->input('gula', ''), GULA_OPSI, true) ? $request->input('gula') : 'Normal Sugar');
                    }
                    $key = kunci_keranjang($menuId, $ukuran, $saji, $gula);
                    if (isset($krj[$key])) {
                        $krj[$key]['jumlah'] += $jumlah;
                    } else {
                        $krj[$key] = [
                            'menu_id' => $menuId, 'jumlah' => $jumlah,
                            'ukuran' => $ukuran, 'saji' => $saji, 'gula' => $gula,
                        ];
                    }
                    $pesan = $m['nama'] . ' masuk keranjang';
                }
            } elseif ($aksi === 'plus') {
                $key = $request->input('key', '');
                if (isset($krj[$key])) $krj[$key]['jumlah']++;
            } elseif ($aksi === 'kurang') {
                $key = $request->input('key', '');
                if (isset($krj[$key]) && --$krj[$key]['jumlah'] <= 0) {
                    unset($krj[$key]);
                }
            } elseif ($aksi === 'hapus') {
                unset($krj[$request->input('key', '')]);
            } elseif ($aksi === 'kosongkan') {
                $krj = [];
            }

            simpan_keranjang($krj);

            if ($ajax) {
                return response()->json(['ok' => true, 'jumlah' => jumlah_item_keranjang(), 'pesan' => $pesan]);
            }
            return redirect('keranjang.php');
        }

        $item  = isi_keranjang($db);
        $total = array_sum(array_column($item, 'subtotal'));

        return view('site.keranjang', [
            'pageTitle' => 'Keranjang',
            'activeNav' => 'keranjang',
            'item'      => $item,
            'total'     => $total,
        ]);
    }
}
