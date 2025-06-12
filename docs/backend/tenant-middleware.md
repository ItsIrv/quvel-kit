# Tenant Middleware

## Overview

The QuVel Kit tenant system includes three specialized middleware components that work together to provide secure, isolated multi-tenant request handling. These middleware run early in the request lifecycle to establish tenant context, apply tenant-specific configurations, and prevent cross-tenant security vulnerabilities.

## Middleware Components

### 1. TenantMiddleware (Primary)
### 2. TenantAwareCsrfToken (Security)
### 3. ValidateTenantSession (Security)

## TenantMiddleware

The primary middleware responsible for tenant resolution and configuration application.

### Purpose
- Resolves the current tenant from the request
- Applies tenant-specific configuration via the configuration pipeline
- Manages tenant context for the request lifecycle
- Handles path exclusions for non-tenant routes

### Execution Flow

```php
Request → TenantMiddleware → Tenant Resolution → Configuration Pipeline → Next Middleware
```

### Key Functionality

#### 1. Tenant Resolution
```php
public function handle(Request $request, Closure $next): mixed
{
    // Check if route should bypass tenant resolution
    if ($this->shouldBypassTenant($request)) {
        $this->tenantContext->setBypassed(true);
        return $next($request);
    }

    // Resolve tenant using configured resolver
    $tenant = $this->tenantResolver->resolveTenant();
    
    // Set tenant in context
    $this->tenantContext->set($tenant);
    
    // Apply tenant configuration
    $this->configPipeline->apply($tenant, $this->config);
    
    return $next($request);
}
```

#### 2. Path Exclusions
The middleware supports excluding specific paths from tenant resolution:

```php
protected function shouldBypassTenant(Request $request): bool
{
    // Check dynamic exclusions from registry
    $registeredPaths = $this->exclusionRegistry->getExcludedPaths();
    $registeredPatterns = $this->exclusionRegistry->getExcludedPatterns();
    
    // Check static exclusions from config
    $configPaths = $this->config->get('tenant.excluded_paths', []);
    $configPatterns = $this->config->get('tenant.excluded_patterns', []);
    
    // Test all exclusion patterns
    foreach (array_merge($registeredPaths, $configPaths) as $path) {
        if ($request->is($path)) return true;
    }
    
    foreach (array_merge($registeredPatterns, $configPatterns) as $pattern) {
        if ($request->is($pattern)) return true;
    }
    
    return false;
}
```

#### 3. Dependencies
- `TenantResolver` - Resolves tenant from request (default: domain-based)
- `TenantContext` - Manages current tenant state
- `ConfigurationPipeline` - Applies tenant configuration
- `TenantExclusionRegistry` - Dynamic exclusion management

### Configuration

#### Static Exclusions (config/tenant.php)
```php
return [
    'excluded_paths' => [
        '/health-check',
        '/webhooks/stripe',
    ],
    'excluded_patterns' => [
        'admin/*',
        'api/webhooks/*',
        'system/*',
    ],
];
```

#### Dynamic Exclusions (Module Registration)
```php
// In a service provider
class YourModuleServiceProvider extends TenantServiceProvider
{
    public function boot(): void
    {
        $this->excludePaths([
            '/your-module/webhook',
            '/your-module/callback',
        ]);
        
        $this->excludePatterns([
            'your-module/admin/*',
            'api/your-module/system/*',
        ]);
    }
}
```

### Custom Tenant Resolver

Replace the default domain-based resolver:

```php
// config/tenant.php
return [
    'resolver' => MyCustomResolver::class,
];
```

```php
class MyCustomResolver implements TenantResolver
{
    public function resolveTenant(): Tenant
    {
        // Header-based resolution
        $tenantId = request()->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::findOrFail($tenantId);
        }
        
        // Subdomain resolution
        $subdomain = explode('.', request()->getHost())[0];
        return Tenant::where('subdomain', $subdomain)->firstOrFail();
    }
}
```

## TenantAwareCsrfToken

Extends Laravel's CSRF protection with tenant-specific token management to prevent cross-tenant CSRF attacks.

### Purpose
- Creates tenant-specific CSRF token cookies
- Prevents CSRF tokens from being shared between tenants
- Maintains CSRF protection while ensuring tenant isolation

### Key Features

#### 1. Tenant-Specific Cookie Names
```php
protected function getCookieName(): string
{
    if ($this->tenantContext->has() && $tenant = $this->tenantContext->get()) {
        return "XSRF-TOKEN-{$tenant->public_id}";
    }
    
    return 'XSRF-TOKEN'; // Fallback for non-tenant requests
}
```

#### 2. Enhanced Token Validation
```php
protected function getTokenFromRequest($request): ?string
{
    // Standard Laravel token sources
    $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');
    
    // Check standard XSRF header
    if (!$token && $header = $request->header('X-XSRF-TOKEN')) {
        $token = $this->decryptToken($header);
    }
    
    // Check tenant-specific XSRF cookie
    if (!$token) {
        $cookieName = $this->getCookieName();
        if ($cookieValue = $request->cookie($cookieName)) {
            $token = $this->decryptToken($cookieValue);
        }
    }
    
    return $token;
}
```

#### 3. Tenant-Aware Cookie Creation
```php
protected function newCookie($request, $config)
{
    $cookieName = $this->getCookieName();
    
    return new Cookie(
        $cookieName,
        $request->session()->token(),
        $this->availableAt(60 * $config['lifetime']),
        $config['path'],
        $config['domain'],
        $config['secure'],
        false, // HttpOnly false for XSRF (JavaScript access needed)
        false,
        $config['same_site'] ?? null,
        $config['partitioned'] ?? false
    );
}
```

### Security Benefits
- **Tenant Isolation**: CSRF tokens cannot be used across tenants
- **Cross-Tenant Attack Prevention**: Malicious sites cannot forge requests to other tenants
- **Session Alignment**: CSRF tokens align with tenant-specific sessions

### Frontend Integration
JavaScript needs to read the tenant-specific CSRF token:

```javascript
// Get tenant-specific XSRF token
function getCsrfToken() {
    const cookies = document.cookie.split(';');
    for (let cookie of cookies) {
        const [name, value] = cookie.split('=').map(s => s.trim());
        if (name.startsWith('XSRF-TOKEN')) {
            return decodeURIComponent(value);
        }
    }
    return null;
}

// Use in API requests
axios.defaults.headers.common['X-XSRF-TOKEN'] = getCsrfToken();
```

## ValidateTenantSession

Prevents cross-tenant session hijacking by validating that sessions belong to the current tenant.

### Purpose
- Ensures sessions are scoped to the correct tenant
- Prevents session hijacking across tenant boundaries
- Automatically invalidates cross-tenant sessions
- Protects against authenticated user cross-contamination

### Security Flow

```php
public function handle(Request $request, Closure $next): mixed
{
    // Skip if tenant context is bypassed
    if ($this->tenantContext->isBypassed()) {
        return $next($request);
    }
    
    // Skip if no tenant context
    if (!$this->tenantContext->has()) {
        return $next($request);
    }
    
    $currentTenant = $this->tenantContext->get();
    $session = $request->session();
    
    // Validate session tenant
    $this->validateSessionTenant($session, $currentTenant);
    
    // Validate authenticated user tenant
    $this->validateUserTenant($currentTenant);
    
    return $next($request);
}
```

### Session Validation

#### 1. Session Tenant Check
```php
private function validateSessionTenant($session, $currentTenant): void
{
    if ($session->has('tenant_id')) {
        $sessionTenantId = $session->get('tenant_id');
        
        if ($sessionTenantId !== $currentTenant->id) {
            // Session belongs to different tenant - invalidate
            $session->invalidate();
            $session->regenerateToken();
            
            // Log out any authenticated user
            if (Auth::check()) {
                Auth::logout();
            }
            
            // Store correct tenant ID
            $session->put('tenant_id', $currentTenant->id);
        }
    } else {
        // No tenant ID - store current tenant
        $session->put('tenant_id', $currentTenant->id);
    }
}
```

#### 2. User Tenant Validation
```php
private function validateUserTenant($currentTenant): void
{
    if (Auth::check()) {
        $user = Auth::user();
        
        // Check if user belongs to current tenant
        if ($user->tenant_id !== $currentTenant->id) {
            // User from different tenant - log out
            Auth::logout();
            
            $session = request()->session();
            $session->invalidate();
            $session->regenerateToken();
            
            // Store correct tenant ID
            $session->put('tenant_id', $currentTenant->id);
        }
    }
}
```

### Attack Scenarios Prevented

#### 1. Session Cookie Reuse
**Scenario**: User copies session cookie from Tenant A to Tenant B
**Protection**: Middleware detects tenant mismatch and invalidates session

#### 2. URL Manipulation
**Scenario**: Authenticated user changes domain while keeping session
**Protection**: User is logged out and session is invalidated

#### 3. Cross-Tenant User Access
**Scenario**: User account exists in multiple tenants with same session
**Protection**: Each tenant maintains separate authentication state

### Configuration Considerations

#### Session Driver Selection
```php
// For isolated tenants - use database with tenant-specific connection
'session_driver' => 'database',
'session_connection' => 'tenant_db',

// For shared tenants - use Redis with tenant prefixing
'session_driver' => 'redis',
'session_prefix' => 'tenant_123_session:',
```

#### Session Cookie Configuration
```php
// Tenant-specific session cookies
'session_cookie' => 'tenant_123_session',
'session_domain' => '.tenant123.app.com',
'session_lifetime' => 120,
```

## Middleware Registration

### Application-Wide Registration
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
Route::group([
    'middleware' => ['web', 'tenant', 'tenant.session']
], function () {
    // Tenant-protected routes
});

// Exclude specific routes
Route::group([
    'middleware' => ['web']
], function () {
    Route::post('/webhooks/stripe', [WebhookController::class, 'stripe']);
    Route::get('/health', [HealthController::class, 'check']);
});
```

## Security Best Practices

### 1. Middleware Order
```php
// Correct order is crucial
[
    'tenant',           // Resolve tenant first
    'tenant.session',   // Validate session belongs to tenant
    'tenant.csrf',      // Tenant-aware CSRF protection
    'auth',             // Authentication (after tenant context)
]
```

### 2. Path Exclusions
```php
// Always exclude system paths
'excluded_patterns' => [
    'admin/*',          // Admin panel
    'system/*',         // System endpoints  
    'api/webhooks/*',   // Webhooks
    'health',           // Health checks
    'metrics',          // Monitoring
]
```

### 3. Logging and Monitoring
```php
// Log tenant security events
\Log::warning('Cross-tenant session detected', [
    'session_tenant_id' => $sessionTenantId,
    'current_tenant_id' => $currentTenant->id,
    'user_id' => Auth::id(),
    'ip' => $request->ip(),
]);
```

### 4. Session Security
```php
// Configure secure session settings
'session' => [
    'secure' => true,           // HTTPS only
    'http_only' => true,        // No JavaScript access
    'same_site' => 'strict',    // CSRF protection
    'encrypt' => true,          // Encrypt session data
];
```

## Troubleshooting

### Common Issues

#### 1. Sessions Not Isolated
**Problem**: Users see data from other tenants
**Check**: 
- `ValidateTenantSession` middleware is registered
- Session configuration includes tenant prefixes
- Session driver supports isolation

#### 2. CSRF Token Mismatches
**Problem**: Forms fail with CSRF token errors
**Check**:
- `TenantAwareCsrfToken` replaces default CSRF middleware
- Frontend reads tenant-specific CSRF cookies
- Token validation includes tenant-specific sources

#### 3. Cross-Tenant Authentication
**Problem**: Users stay logged in across tenant switches
**Check**:
- `ValidateTenantSession` runs after `TenantMiddleware`
- User model has `tenant_id` field
- Authentication guards are tenant-aware

### Debugging Tools

#### 1. Tenant Context Inspection
```php
// Check tenant context state
$context = app(\Modules\Tenant\Contexts\TenantContext::class);
dd([
    'has_tenant' => $context->has(),
    'is_bypassed' => $context->isBypassed(),
    'tenant' => $context->get(),
]);
```

#### 2. Session Debugging
```php
// Check session tenant association
dd([
    'session_tenant_id' => session('tenant_id'),
    'current_tenant_id' => getTenant()?->id,
    'auth_user_tenant' => Auth::user()?->tenant_id,
]);
```

#### 3. CSRF Token Inspection
```php
// Check CSRF token configuration
dd([
    'csrf_cookie_name' => app(\Modules\Tenant\Http\Middleware\TenantAwareCsrfToken::class)->getCookieName(),
    'session_token' => session()->token(),
    'request_token' => request()->header('X-CSRF-TOKEN'),
]);
```

## Performance Considerations

### 1. Middleware Efficiency
- Tenant resolution is cached per request
- Configuration pipeline runs once per request
- Session validation uses minimal database queries

### 2. Exclusion Optimization
- Static exclusions are faster than dynamic ones
- Use specific paths over broad patterns when possible
- Cache exclusion registry results

### 3. Session Storage
- Use Redis for better session performance
- Consider session prefixing over separate databases
- Implement session cleanup for deleted tenants

---

[← Back to Configuration Pipes](./tenant-configuration-pipes.md) | [Next: Tenant Configuration →](./tenant-configuration.md)