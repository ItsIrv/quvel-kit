<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Symfony\Component\HttpFoundation\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(TenantMiddleware::class);

        // Only trust proxy headers when behind a reverse proxy like Nginx
        // Do NOT trust headers like X-Forwarded-Host if Swoole is exposed directly
        if (env('OCTANE_SERVER') === 'swoole') {
            $middleware->trustProxies(
                [
                    '127.0.0.1',
                    'localhost',
                ],
                Request::HEADER_X_FORWARDED_TRAEFIK,
            );
        }
    })
    ->withBroadcasting('')
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
