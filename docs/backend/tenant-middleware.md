# Tenant Middleware

## Overview

The QuVel Kit tenant system includes three specialized middleware components that provide secure, isolated multi-tenant request handling. These middleware work together to establish tenant context, prevent cross-tenant security vulnerabilities, and ensure complete isolation between tenants.

## Middleware Components

### 1. TenantMiddleware (Primary Resolution)

- Resolves tenant from request domain
- Applies tenant-specific configuration via pipeline
- Manages path exclusions for system routes

### 2. TenantAwareCsrfToken (CSRF Protection)

- Creates tenant-specific CSRF tokens
- Prevents cross-tenant CSRF attacks
- Maintains session alignment

### 3. ValidateTenantSession (Session Security)

- Prevents cross-tenant session hijacking
- Validates user-tenant relationships
- Automatic session invalidation on mismatch

## Request Processing Flow

Request → TenantMiddleware → Configuration Pipeline → Session Validation → CSRF Protection → Application

1. **Domain Resolution**: Extract tenant from request domain
2. **Configuration Apply**: Run 12 configuration pipes for tenant isolation
3. **Session Validation**: Ensure session belongs to current tenant
4. **CSRF Protection**: Validate tenant-specific CSRF tokens
5. **Application Logic**: Execute with complete tenant context

## TenantMiddleware

The primary middleware that establishes tenant context for each request.

### Core Functionality

**Tenant Resolution**: Automatically resolves tenant from request domain using configurable resolvers.

**Configuration Pipeline**: Applies tenant-specific settings through 12 specialized pipes before any application logic runs.

**Path Exclusions**: Allows certain routes to bypass tenant resolution for webhooks, health checks, and admin functionality.

### Path Exclusion Configuration

#### Static Exclusions (config/tenant.php)

```php
return [
    'excluded_paths' => [
        '/health-check',
        '/webhooks/stripe',
        '/api/system',
    ],
    'excluded_patterns' => [
        'admin/*',           // Admin panel routes
        'api/webhooks/*',    // All webhook endpoints
        'system/*',          // System monitoring
    ],
];
```

#### Dynamic Exclusions (Module Registration)

```php
class YourModuleServiceProvider extends TenantServiceProvider
{
    public function boot(): void
    {
        // Register paths that should bypass tenant resolution
        $this->excludePaths([
            '/your-module/webhook',
            '/your-module/system-status',
        ]);
        
        $this->excludePatterns([
            'your-module/admin/*',
            'api/your-module/internal/*',
        ]);
    }
}
```

### Custom Tenant Resolvers

Replace the default domain-based resolution with custom logic:

```php
// config/tenant.php
return [
    'resolver' => CustomTenantResolver::class,
];
```

#### Example: Header-Based Resolution

```php
class HeaderTenantResolver implements TenantResolver
{
    public function resolveTenant(): Tenant
    {
        $tenantId = request()->header('X-Tenant-ID');
        
        if (!$tenantId) {
            throw new TenantNotFoundException('Missing X-Tenant-ID header');
        }
        
        return Tenant::findOrFail($tenantId);
    }
}
```

#### Example: Subdomain Resolution

```php
class SubdomainResolver implements TenantResolver
{
    public function resolveTenant(): Tenant
    {
        $host = request()->getHost();
        $subdomain = explode('.', $host)[0];
        
        return Tenant::where('subdomain', $subdomain)
            ->firstOrFail();
    }
}
```

## TenantAwareCsrfToken

Extends Laravel's CSRF protection with tenant-specific token management.

### Security Model

**Tenant-Specific Tokens**: Each tenant gets unique CSRF token cookies (`XSRF-TOKEN-{tenant_id}`) that cannot be used across tenants.

**Attack Prevention**: Prevents malicious sites from forging requests to other tenants using stolen CSRF tokens.

**Session Alignment**: CSRF tokens automatically align with tenant-specific session cookies.

## ValidateTenantSession

Prevents cross-tenant session hijacking and user contamination.

### Security Scenarios Prevented

#### 1. Session Cookie Reuse Attack

**Attack**: User copies session cookie from `tenant-a.app.com` to `tenant-b.app.com`
**Protection**: Middleware detects tenant mismatch and invalidates session immediately

#### 2. Domain Switching Attack  

**Attack**: Authenticated user manually changes URL from one tenant domain to another
**Protection**: User is automatically logged out and session is regenerated

#### 3. Cross-Tenant User Contamination

**Attack**: User account exists in multiple tenants with overlapping session data
**Protection**: Each tenant maintains completely separate authentication state

### Session Configuration Examples

#### Isolated Tenants (Database Sessions)

```php
// Each tenant gets dedicated database and sessions
'session_driver' => 'database',
'session_connection' => 'tenant_mysql', // Tenant-specific DB
'session_cookie' => 'tenant_enterprise_session',
'session_domain' => '.enterprise.app.com',
```

#### Shared Infrastructure (Redis Sessions)

```php
// Shared Redis with tenant prefixing
'session_driver' => 'redis',
'session_cookie' => 'tenant_123_session',
'session_prefix' => 'tenant_123:sessions:',
'redis_connection' => 'sessions',
```

## Middleware Registration

### Laravel 12

```php
// bootstrap/app.php
use Modules\Tenant\Providers\TenantMiddlewareProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withMiddleware(function (Middleware $middleware): void {
        // Register all tenant middleware automatically
        TenantMiddlewareProvider::bootstrapMiddleware($middleware);
    });
```

### Laravel with Kernel.php

```php
// app/Http/Kernel.php
protected $middlewareGroups = [
    'web' => [
        // ... other middleware
        \Modules\Tenant\Http\Middleware\TenantMiddleware::class,
        \Modules\Tenant\Http\Middleware\ValidateTenantSession::class,
    ],
];

protected $middleware = [
    // Replace default CSRF with tenant-aware version
    \Modules\Tenant\Http\Middleware\TenantAwareCsrfToken::class,
];
```

### Route-Specific Registration

```php
// routes/web.php
Route::group(['middleware' => ['web', 'tenant', 'tenant.session']], function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::post('/api/data', [ApiController::class, 'store']);
});

// Exclude system routes from tenant resolution
Route::group(['middleware' => ['web']], function () {
    Route::post('/webhooks/{provider}', [WebhookController::class, 'handle']);
    Route::get('/health', [HealthController::class, 'check']);
    Route::get('/metrics', [MetricsController::class, 'show']);
});
```

---

[← Back to Configuration Pipes](./tenant-configuration-pipes.md) | [Next: Tenant Configuration →](./tenant-configuration.md)
