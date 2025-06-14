<?php

namespace Modules\Tenant\Providers;

use Illuminate\Foundation\Configuration\Middleware;
use Modules\Tenant\Http\Middleware\TenantAwareCsrfToken;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Http\Middleware\ValidateTenantSession;

class TenantMiddlewareBootstrapper
{
    public static function bootstrapMiddleware(Middleware $middleware): void
    {
        $middleware->prepend(TenantMiddleware::class);

        // Remove the default CSRF middleware from both global and web groups
        $middleware->remove([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        $middleware->web(remove: [
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
        ]);

        // Add our tenant-aware middlewares to web group
        $middleware->web(append: [
            ValidateTenantSession::class,
            TenantAwareCsrfToken::class,
        ]);
    }
}
