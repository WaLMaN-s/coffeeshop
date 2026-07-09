<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Halaman pelanggan hanya bisa diakses setelah scan QR meja + isi nama & no. HP. */
class MejaAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!meja_aktif()) {
            return redirect('meja.php');
        }
        return $next($request);
    }
}
