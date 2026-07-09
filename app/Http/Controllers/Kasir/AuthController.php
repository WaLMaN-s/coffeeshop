<?php

namespace App\Http\Controllers\Kasir;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $db = db();

        if (session('kasir_id')) {
            return redirect('kasir/index.php');
        }

        $error = '';
        if ($request->isMethod('post')) {
            $username = trim($request->input('username', ''));
            $password = $request->input('password', '');

            $stmt = $db->prepare('SELECT * FROM kasir WHERE username = ?');
            $stmt->execute([$username]);
            $kasir = $stmt->fetch();
            if ($kasir && password_verify($password, $kasir['password'])) {
                session()->regenerate();
                session([
                    'kasir_id'   => $kasir['id'],
                    'kasir_nama' => $kasir['nama'],
                ]);
                return redirect('kasir/index.php');
            }
            $error = 'Username atau password salah.';
        }

        $pengaturan = get_pengaturan($db);
        $namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';

        return view('kasir.login', compact('error', 'pengaturan', 'namaToko'));
    }

    public function logout()
    {
        session()->forget(['kasir_id', 'kasir_nama']);
        return redirect('kasir/login.php');
    }
}
