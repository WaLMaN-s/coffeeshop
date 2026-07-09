<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $db = db();

        if (session('admin_id')) {
            return redirect('admin/index.php');
        }

        $error = '';
        if ($request->isMethod('post')) {
            $username = trim($request->input('username', ''));
            $password = $request->input('password', '');

            $stmt = $db->prepare('SELECT * FROM admin WHERE username = ?');
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            if ($admin && password_verify($password, $admin['password'])) {
                session()->regenerate();
                session([
                    'admin_id'   => $admin['id'],
                    'admin_nama' => $admin['nama'],
                ]);
                return redirect('admin/index.php');
            }
            $error = 'Username atau password salah.';
        }

        $pengaturan = get_pengaturan($db);
        $namaToko   = $pengaturan['nama_toko'] ?? 'Lorong Kopi';

        return view('admin.login', compact('error', 'pengaturan', 'namaToko'));
    }

    public function logout()
    {
        // Hanya akhiri sesi admin — sesi meja & keranjang di browser yang sama tetap utuh.
        session()->forget(['admin_id', 'admin_nama']);
        return redirect('admin/login.php');
    }
}
