<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di produksi aplikasi berada di belakang Cloudflare Tunnel + nginx;
        // percayai header X-Forwarded-* supaya redirect memakai https.
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'meja'  => \App\Http\Middleware\MejaAktif::class,
            'kasir' => \App\Http\Middleware\KasirAuth::class,
            'admin' => \App\Http\Middleware\AdminAuth::class,
        ]);
        // Form & fetch hasil port dari versi non-framework belum membawa token
        // CSRF; dinonaktifkan agar perilaku identik dengan versi lama.
        $middleware->validateCsrfTokens(except: ['*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
