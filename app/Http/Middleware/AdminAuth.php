<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session('admin_id')) {
            if ($request->expectsJson() || $request->is('admin/api/*')) {
                return response()->json(['error' => 'unauthorized'], 401);
            }
            return redirect('admin/login.php');
        }
        return $next($request);
    }
}
