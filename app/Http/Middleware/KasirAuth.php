<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KasirAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('kasir_id')) {
            if ($request->expectsJson() || $request->is('kasir/api/*')) {
                return response()->json(['error' => 'unauthorized'], 401);
            }
            return redirect('kasir/login.php');
        }
        return $next($request);
    }
}
