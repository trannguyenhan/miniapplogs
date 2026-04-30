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
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust only the reverse proxy in front of this app.
        // Set TRUSTED_PROXIES=* in .env ONLY if you are behind a load balancer you trust fully.
        $trustedProxies = env('TRUSTED_PROXIES', null);
        if ($trustedProxies) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
