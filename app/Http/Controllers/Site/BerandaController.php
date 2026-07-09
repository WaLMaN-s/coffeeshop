<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    public function index(Request $request)
    {
        $db   = db();
        $q    = trim($request->query('q', ''));
        $fkat = (int) $request->query('kategori', 0);

        $where  = ["m.status = 'aktif'"];
        $params = [];
        if ($q !== '')  { $where[] = 'm.nama LIKE ?';     $params[] = "%$q%"; }
        if ($fkat > 0)  { $where[] = 'm.kategori_id = ?'; $params[] = $fkat; }

        $stmt = $db->prepare('
            SELECT m.*, k.nama kategori FROM menu m
            JOIN kategori k ON k.id = m.kategori_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY k.nama, m.nama');
        $stmt->execute($params);
        $daftarMenu = $stmt->fetchAll();

        $daftarKategori = $db->query("
            SELECT k.* FROM kategori k
            WHERE EXISTS (SELECT 1 FROM menu m WHERE m.kategori_id = k.id AND m.status = 'aktif')
            ORDER BY k.nama")->fetchAll();

        return view('site.beranda', [
            'pageTitle' => 'Beranda',
            'activeNav' => 'beranda',
            'q'          => $q,
            'fkat'       => $fkat,
            'daftarMenu' => $daftarMenu,
            'daftarKategori' => $daftarKategori,
        ]);
    }
}
