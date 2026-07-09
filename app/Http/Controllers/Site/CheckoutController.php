<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class CheckoutController extends Controller
{
    public function index(Request $request)
    {
        $db    = db();
        $item  = isi_keranjang($db);
        $total = array_sum(array_column($item, 'subtotal'));

        if (!$item) {
            return redirect('keranjang.php');
        }

        /* ---------- Buat pesanan ---------- */
        if ($request->isMethod('post')) {
            $metode  = $request->input('metode') === 'qris' ? 'qris' : 'cash';
            $catatan = trim($request->input('catatan', ''));
            $meja    = session('meja');

            $db->beginTransaction();
            try {
                $nomor = buat_nomor_pesanan($db);
                $db->prepare('INSERT INTO pesanan (nomor_pesanan, pelanggan_id, meja_id, nama_tamu, sesi_kode, total, status, catatan) VALUES (?,?,?,?,?,?,?,?)')
                   ->execute([
                       $nomor,
                       $meja['pelanggan_id'] ?? null,
                       $meja['meja_id'],
                       $meja['nama'],
                       $meja['sesi'],
                       $total, 'menunggu', $catatan ?: null,
                   ]);
                $pesananId = (int) $db->lastInsertId();

                $stmtItem = $db->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, opsi, jumlah, harga) VALUES (?,?,?,?,?)');
                foreach ($item as $it) {
                    $stmtItem->execute([$pesananId, $it['menu_id'], $it['opsi_label'] ?: null, $it['jumlah'], $it['harga_satuan']]);
                }

                $db->prepare('INSERT INTO pembayaran (pesanan_id, metode, jumlah, status) VALUES (?,?,?,?)')
                   ->execute([$pesananId, $metode, $total, 'belum_dibayar']);

                tambah_notifikasi($db, 'pesanan_baru', 'Pesanan baru ' . $nomor . ' dari Meja ' . $meja['nomor_meja'] . ' (' . $meja['nama'] . ').', $pesananId);
                $db->commit();

                simpan_keranjang([]);
                set_flash('sukses', 'Pesanan ' . $nomor . ' berhasil dibuat!');
                return redirect('pesanan_lihat.php?id=' . $pesananId);
            } catch (Throwable $e) {
                $db->rollBack();
                set_flash('gagal', 'Pesanan gagal dibuat, coba lagi.');
                return redirect('checkout.php');
            }
        }

        return view('site.checkout', [
            'pageTitle' => 'Checkout',
            'activeNav' => 'keranjang',
            'item'      => $item,
            'total'     => $total,
        ]);
    }
}
