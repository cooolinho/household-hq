<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Hinter einem Reverse Proxy (z.B. das Docker-All-in-One-Image hinter
        // Traefik/nginx) muss Laravel dem Proxy vertrauen, um X-Forwarded-*
        // auszuwerten - sonst hält es die Verbindung für http und erzeugt
        // Mixed-Content-URLs. Ohne TRUSTED_PROXIES bleibt das Verhalten wie
        // zuvor unverändert (kein Proxy vertraut).
        $middleware->trustProxies(at: env('TRUSTED_PROXIES'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
