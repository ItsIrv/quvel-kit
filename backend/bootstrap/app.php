<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Modules\Tenant\Providers\TenantMiddlewareProvider;
use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        TenantMiddlewareProvider::bootstrapMiddleware($middleware);

        // Trust proxy headers when enabled via environment
        // Note: env() works here but Log facade may not be fully initialized yet
        $trustProxies = env('TRUST_PROXIES', false);

        error_log('Trust Proxies: ' . json_encode($trustProxies));

        if ($trustProxies) {
            $trustedProxyIps = env('TRUSTED_PROXY_IPS', '127.0.0.1,localhost');
            $proxyIps        = array_map('trim', explode(',', $trustedProxyIps));

            error_log('Trust Proxies Enabled: ' . json_encode($proxyIps));

            $middleware->trustProxies(
                $proxyIps,
                Request::HEADER_X_FORWARDED_TRAEFIK,
            );
        }
    })
    ->withBroadcasting('')
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception handling is configured in CoreServiceProvider
    })->create();
