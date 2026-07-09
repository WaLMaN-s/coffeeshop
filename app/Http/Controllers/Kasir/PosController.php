<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class PosController extends Controller
{
    /* Opsi minuman — sama dengan sisi pelanggan. */
    public const POS_UKURAN  = ['Regular' => 0, 'Large' => 5000];
    public const POS_SAJI    = ['Dingin', 'Panas'];
    public const POS_GULA    = ['Normal Sugar', 'Less Sugar', 'No Sugar'];
    public const POS_MINUMAN = ['Coffee', 'Non Coffee', 'Tea'];

    public function index(Request $request)
    {
        $db = db();

        /* ---------- Simpan pesanan dari kasir ---------- */
        if ($request->isMethod('post')) {
            $namaTamu = trim($request->input('nama', ''));
            $mejaId   = (int) $request->input('meja_id', 0);
            $metode   = $request->input('metode', 'cash') === 'qris' ? 'qris' : 'cash';
            $lunas    = (bool) $request->input('lunas');
            $catatan  = trim($request->input('catatan', ''));
            $itemJson = json_decode($request->input('item', '[]'), true);

            if ($namaTamu === '') $namaTamu = 'Pelanggan Kasir';

            if (!is_array($itemJson) || !$itemJson) {
                set_flash('gagal', 'Belum ada item yang dipilih.');
                return redirect('kasir/pesanan_baru.php');
            }
            $db->beginTransaction();
            try {
                // Validasi item + hitung harga di server (jangan percaya harga dari browser)
                $baris = [];
                $total = 0;
                $cek = $db->prepare("
                    SELECT m.id, m.nama, m.harga, m.tanpa_gula, k.nama kategori FROM menu m
                    JOIN kategori k ON k.id = m.kategori_id
                    WHERE m.id = ? AND m.status = 'aktif'");
                foreach ($itemJson as $it) {
                    $cek->execute([(int) ($it['menu_id'] ?? 0)]);
                    $m = $cek->fetch();
                    if (!$m) continue;
                    $jumlah  = max(1, min(50, (int) ($it['jumlah'] ?? 1)));
                    $minuman = in_array($m['kategori'], self::POS_MINUMAN, true);
                    $opsi    = null;
                    $harga   = (float) $m['harga'];
                    if ($minuman) {
                        $ukuran = array_key_exists($it['ukuran'] ?? '', self::POS_UKURAN) ? $it['ukuran'] : 'Regular';
                        $saji   = in_array($it['saji'] ?? '', self::POS_SAJI, true) ? $it['saji'] : 'Dingin';
                        $harga += self::POS_UKURAN[$ukuran];
                        if ($m['tanpa_gula']) {
                            // Espresso, Americano, dll — tidak pakai opsi gula
                            $opsi = $ukuran . ' · ' . $saji;
                        } else {
                            $gula = in_array($it['gula'] ?? '', self::POS_GULA, true) ? $it['gula'] : 'Normal Sugar';
                            $opsi = $ukuran . ' · ' . $saji . ' · ' . $gula;
                        }
                    }
                    $baris[] = ['menu_id' => (int) $m['id'], 'opsi' => $opsi, 'jumlah' => $jumlah, 'harga' => $harga];
                    $total  += $harga * $jumlah;
                }
                if (!$baris) throw new RuntimeException('Item tidak valid.');

                // Pesanan kasir cukup dikenali dari nomor pesanan — tidak pakai no. HP.
                $pelangganId = null;

                $cekMeja = null;
                if ($mejaId > 0) {
                    $q = $db->prepare("SELECT id FROM meja WHERE id = ? AND status = 'aktif'");
                    $q->execute([$mejaId]);
                    $cekMeja = $q->fetchColumn() ?: null;
                }

                $nomor = buat_nomor_pesanan($db);
                $db->prepare('INSERT INTO pesanan (nomor_pesanan, pelanggan_id, meja_id, nama_tamu, sesi_kode, total, status, catatan)
                              VALUES (?,?,?,?,?,?,?,?)')
                   ->execute([$nomor, $pelangganId, $cekMeja, $namaTamu, null, $total, 'diproses', $catatan ?: null]);
                $pesananId = (int) $db->lastInsertId();

                $stmtItem = $db->prepare('INSERT INTO pesanan_item (pesanan_id, menu_id, opsi, jumlah, harga) VALUES (?,?,?,?,?)');
                foreach ($baris as $b) {
                    $stmtItem->execute([$pesananId, $b['menu_id'], $b['opsi'], $b['jumlah'], $b['harga']]);
                }

                $db->prepare('INSERT INTO pembayaran (pesanan_id, metode, jumlah, status, tanggal_bayar) VALUES (?,?,?,?,?)')
                   ->execute([$pesananId, $metode, $total, $lunas ? 'sudah_dibayar' : 'belum_dibayar', $lunas ? date('Y-m-d H:i:s') : null]);

                tambah_notifikasi($db, 'pesanan_baru', 'Pesanan baru ' . $nomor . ' dibuat di kasir (' . $namaTamu . ').', $pesananId);
                $db->commit();

                set_flash('sukses', 'Pesanan ' . $nomor . ' dibuat' . ($lunas ? ' & lunas' : '') . '.');
                return redirect('kasir/pesanan_detail.php?id=' . $pesananId);
            } catch (Throwable $e) {
                $db->rollBack();
                set_flash('gagal', 'Pesanan gagal disimpan, coba lagi.');
                return redirect('kasir/pesanan_baru.php');
            }
        }

        /* ---------- Data untuk tampilan ---------- */
        $menu = $db->query("
            SELECT m.id, m.nama, m.harga, m.foto, m.tanpa_gula, k.nama kategori
            FROM menu m JOIN kategori k ON k.id = m.kategori_id
            WHERE m.status = 'aktif'
            ORDER BY k.nama, m.nama")->fetchAll();
        $kategori   = array_values(array_unique(array_column($menu, 'kategori')));
        $daftarMeja = $db->query("SELECT id, nomor_meja FROM meja WHERE status='aktif' ORDER BY CAST(nomor_meja AS UNSIGNED), nomor_meja")->fetchAll();

        return view('kasir.pesanan_baru', [
            'pageTitle' => 'Pesanan Baru (Kasir)',
            'active'    => 'pesanan_baru',
            'menu' => $menu, 'kategori' => $kategori, 'daftarMeja' => $daftarMeja,
        ]);
    }
}
