<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $db = db();

        if ($request->isMethod('post') && $request->input('aksi') === 'baca_semua') {
            $db->exec('UPDATE notifikasi SET dibaca = 1 WHERE dibaca = 0');
            return response()->json(['ok' => true]);
        }

        // Tandai satu notifikasi dibaca saat item-nya diklik.
        if ($request->isMethod('post') && $request->input('aksi') === 'baca') {
            $db->prepare('UPDATE notifikasi SET dibaca = 1 WHERE id = ?')
               ->execute([(int) $request->input('id')]);
            return response()->json(['ok' => true]);
        }

        $jumlah = (int) $db->query('SELECT COUNT(*) FROM notifikasi WHERE dibaca = 0')->fetchColumn();
        $rows   = $db->query('SELECT * FROM notifikasi ORDER BY created_at DESC LIMIT 10')->fetchAll();

        $item = array_map(fn ($n) => [
            'id'         => (int) $n['id'],
            'tipe'       => $n['tipe'],
            'pesan'      => $n['pesan'],
            'pesanan_id' => $n['pesanan_id'],
            'dibaca'     => (int) $n['dibaca'],
            'waktu'      => waktu_relatif($n['created_at']),
        ], $rows);

        $antrean = (int) $db->query("SELECT COUNT(*) FROM pesanan WHERE status = 'menunggu'")->fetchColumn();

        return response()->json(['jumlah' => $jumlah, 'antrean' => $antrean, 'item' => $item]);
    }
}
