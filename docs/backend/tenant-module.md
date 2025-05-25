# Multi-Tenancy System

## Overview

QuVel Kit provides a powerful multi-tenancy system that allows a single application instance to serve multiple isolated tenants. The system implements domain-based tenant resolution, tenant context management, and a dynamic tenant-specific configuration system with support for parent-child tenant relationships.

## Architecture

### Core Components

| Component | Description | Responsibility |
|-----------|-------------|----------------|
| `TenantContext` | Request-scoped service | Holds the current tenant information and provides access to tenant configuration |
| `TenantMiddleware` | HTTP middleware | Resolves the tenant for each request based on the domain |
| `HostResolver` | Domain resolver | Default implementation that maps domains to tenants |
| `FindService` | Tenant lookup service | Provides methods to find tenants by various criteria |
| `ConfigApplier` | Configuration service | Applies tenant-specific configuration to the application |
| `TenantConfig` | Value object | Encapsulates all configurable settings for a tenant |
| `TenantConfigCast` | Eloquent cast | Handles conversion between JSON and `TenantConfig` objects |
| `TenantConfigFactory` | Factory service | Creates `TenantConfig` instances with sensible defaults |

## Tenant Configuration System

### Overview

The tenant configuration system allows you to override virtually any Laravel configuration value on a per-tenant basis. This enables different clients to have their own settings while running on the same application instance.

Key capabilities include:

- **Dynamic Configuration**: Store tenant-specific configuration in the database
- **Runtime Overrides**: Override Laravel's configuration values at runtime
- **Inheritance**: Child tenants can inherit configuration from parent tenants
- **Visibility Control**: Configuration values have different visibility levels

### Configuration Visibility

Configuration values have different visibility levels defined in the `TenantConfigVisibility` enum:

| Level | Description | Access |
|-------|-------------|--------|
| `PUBLIC` | Exposed to the browser | Available via `window.TENANT_CONFIG` |
| `PROTECTED` | Exposed to SSR server | Available in `src-ssr/services/TenantCache.ts` |
| `PRIVATE` | Backend only | Never exposed outside the backend |

### Environment Variables

| Variable | Description | Default |
|----------|-------------|--------|
| `TENANT_SSR_PRELOAD_TENANTS` | Preload all tenants at SSR boot time | `true` |
| `TENANT_SSR_RESOLVER_TTL` | Domain resolution cache TTL (seconds) | `300` |
| `TENANT_SSR_CACHE_TTL` | Full tenant cache response TTL (seconds) | `300` |
| `TENANT_PRIVACY_SSR_API_KEY` | Secret key for SSR tenant data access | - |
| `TENANT_PRIVACY_TRUSTED_INTERNAL_IPS` | Allowed IPs for internal endpoints | `127.0.0.1,::1` |
| `TENANT_PRIVACY_DISABLE_KEY_CHECK` | Disable API key verification (dev only) | `false` |
| `TENANT_PRIVACY_DISABLE_IP_CHECK` | Disable IP verification (dev only) | `false` |

### Tenant Table Configuration

The multi-tenancy system automatically adds tenant scoping to specified tables. Configure this in your `config/tenant.php` file:

```php
// Example configuration for tenant-aware tables
'tables' => [
    'users' => [
        'after' => 'id',                      // Column after which to add tenant_id
        'cascade_delete' => true,             // Delete records when tenant is deleted
        'drop_uniques' => [['email']],        // Unique constraints to drop
        'tenant_unique_constraints' => [      // Tenant-scoped unique constraints
            ['email'],
        ],
    ],
],
```

## Tenant Resolution

### How Tenant Resolution Works

By default, QuVel Kit uses domain-based tenant resolution. When a request comes in, the system:

1. Extracts the domain from the request
2. Looks up the tenant associated with that domain
3. Sets the tenant in the TenantContext
4. Applies tenant-specific configuration
5. Scopes all database queries to that tenant

### Customizing Tenant Resolution

The multi-tenancy system uses a pluggable resolution architecture. You can replace the default `HostResolver` with your own implementation:

```php
// In config/tenant.php
return [
    // ...
    'resolver' => MyCustomResolver::class,
    // ...
];
```

The resolver is registered in the service container as a scoped dependency:

```php
$this->app->scoped(
    TenantResolver::class,
    fn (): TenantResolver => app(config('tenant.resolver'))
);
```

### Custom Resolution Strategies

You can implement various tenant resolution strategies:

| Strategy | Example | Implementation |
|----------|---------|----------------|
| Subdirectory | `example.com/tenant1` | Parse URL path |
| Header | `X-Tenant: tenant1` | Read request header |
| Query parameter | `?tenant=tenant1` | Read query string |
| JWT token | Token payload | Extract from auth token |
| Subdomain | `tenant1.example.com` | Parse hostname |

Your custom resolver just needs to implement the `TenantResolver` interface:

```php
class MyCustomResolver implements TenantResolver
{
    public function resolveTenant(): Tenant
    {
        // Example: Header-based resolution
        $tenantId = request()->header('X-Tenant-ID');
        return Tenant::findOrFail($tenantId);
    }
}
```

## Using the Tenant System

### Accessing Current Tenant

In controllers or services, you can access the current tenant using dependency injection:

```php
// Using dependency injection (recommended)
public function index(TenantContext $tenantContext)
{
    $tenant = $tenantContext->get();
    $tenantName = $tenant->name;
    $tenantDomain = $tenant->domain;
    
    // Access tenant configuration
    $theme = $tenantContext->getConfigValue('theme', 'default');
}

// Or using the global helper function
public function show()
{
    $tenant = getTenant();
    return response()->json([
        'name' => $tenant->name,
        'domain' => $tenant->domain,
    ]);
}
```

### Working with Tenant Configuration

#### Accessing Configuration

You can access a tenant's configuration through the `config` property on the `Tenant` model:

```php
$tenant = Tenant::find(1);
$config = $tenant->config;

// Access specific configuration values
$appName = $config->appName;
$mailFromAddress = $config->mailFromAddress;
```

#### Getting Effective Configuration

Tenants can inherit configuration from parent tenants. To get the effective configuration (including inherited values), use the `getEffectiveConfig()` method:

```php
$tenant = Tenant::find(1);
$effectiveConfig = $tenant->getEffectiveConfig();
```

#### Applying Tenant Configuration

To apply a tenant's configuration at runtime, use the `TenantServiceProvider::applyTenantConfig()` method or the helper function `setTenant()`:

```php
// Using the service provider
TenantServiceProvider::applyTenantConfig($tenant);

// Using the helper function
setTenant($tenantId);
```

#### Setting Configuration Values

```php
// Set configuration values
$tenant->config = new TenantConfig([
    'appName' => 'Custom App Name',
    'mailFromAddress' => 'support@example.com',
    'mailFromName' => 'Support Team',
]);
$tenant->save();
```

### Configuration Structure

The `TenantConfig` value object includes the following configurable sections:

#### App Settings

```php
$config = new TenantConfig([
    'appName' => 'My Application',        // Application name
    'appEnv' => 'production',            // Environment (local, production, etc.)
    'appDebug' => false,                 // Debug mode
    'appUrl' => 'https://example.com',   // Application URL
    'appTimezone' => 'UTC',              // Application timezone
]);
```

#### Frontend Settings

```php
$config = new TenantConfig([
    'frontendUrl' => 'https://example.com',      // Frontend URL
    'capacitorScheme' => 'myapp',               // Capacitor deep link scheme
    'internalApiUrl' => 'https://api.internal', // Internal API URL for SSR
]);
```

#### Database Settings

```php
$config = new TenantConfig([
    'dbConnection' => 'mysql',
    'dbHost' => 'db.example.com',
    'dbPort' => 3306,
    'dbDatabase' => 'tenant_db',
    'dbUsername' => 'tenant_user',
    'dbPassword' => 'secure_password',
]);
```

#### Mail Settings

```php
$config = new TenantConfig([
    'mailMailer' => 'smtp',
    'mailHost' => 'smtp.example.com',
    'mailPort' => 587,
    'mailUsername' => 'user@example.com',
    'mailPassword' => 'mail_password',
    'mailFromAddress' => 'no-reply@example.com',
    'mailFromName' => 'Example App',
]);
```

#### Cache & Session

```php
$config = new TenantConfig([
    'cacheStore' => 'redis',
    'cachePrefix' => 'tenant1',
    'sessionDriver' => 'redis',
    'sessionLifetime' => 120,
    'sessionDomain' => '.example.com',
]);
```

#### AWS Settings

```php
$config = new TenantConfig([
    'awsAccessKeyId' => 'your-access-key',
    'awsSecretAccessKey' => 'your-secret-key',
    'awsDefaultRegion' => 'us-west-2',
    'awsBucket' => 'tenant-bucket',
]);
```

#### OAuth Settings

```php
$config = new TenantConfig([
    'oauthProviders' => [
        'google' => [
            'clientId' => 'google-client-id',
            'clientSecret' => 'google-client-secret',
            'redirectUrl' => 'https://example.com/auth/callback/google',
        ],
    ],
]);
```

## Tenant-Aware Models

### How the Tenant Scoping Works

The `TenantScopedModel` trait provides automatic tenant scoping for Eloquent models. Here's how it works:

1. **Global Scope**: Adds a `TenantScope` that automatically filters all queries to only include records for the current tenant

2. **Creating Hook**: Automatically sets the tenant_id when creating new records

3. **Safety Guards**: Prevents cross-tenant operations by checking tenant_id on save, update, and delete operations

4. **Broadcast Channels**: Configures tenant-aware broadcast channels for notifications

### Using Tenant-Aware Models

To make a model tenant-aware, add the `TenantScopedModel` trait:

```php
use Modules\Tenant\Traits\TenantScopedModel;

class Product extends Model
{
    use TenantScopedModel;
    
    // Rest of your model definition...
}
```

This automatically applies tenant scoping to all operations:

```php
// Queries are automatically filtered by current tenant
$products = Product::all();  // Only returns products for current tenant
$product = Product::find(1); // Only finds if product belongs to current tenant

// Creating automatically sets tenant_id
$product = Product::create([
    'name' => 'Example Product',
    'price' => 99.99,
]);

// Prevents cross-tenant operations
try {
    $product->tenant_id = 999; // Different tenant
    $product->save();           // Throws TenantMismatchException
} catch (TenantMismatchException $e) {
    // Handle error
}
```

### Tenant-Aware Relationships

Relationships between tenant-aware models automatically maintain tenant isolation:

```php
// Define relationship
class Product extends Model
{
    use TenantScopedModel;
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}

class Review extends Model
{
    use TenantScopedModel;
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

// Usage - tenant scoping is automatically applied
$product = Product::find(1);
$reviews = $product->reviews; // Only returns reviews for current tenant
```

## Disabling Multi-Tenancy

QuVel Kit is designed to make multi-tenancy optional. You can disable it in several ways:

### Method 1: Disable the Tenant Module

The simplest approach is to disable the entire Tenant module:

1. Remove or comment out the Tenant module from your `config/modules.php`:

```php
return [
    // ...
    // 'Tenant', // Comment out to disable
    // ...
];
```

2. Remove tenant-related middleware from your HTTP kernel.

### Method 2: Remove Tenant Traits

If you want to keep some tenant functionality but don't need automatic scoping:

1. Remove the `TenantScopedModel` trait from your models:

```php
// Before
class User extends Model
{
    use TenantScopedModel;
    // ...
}

// After
class User extends Model
{
    // TenantScopedModel trait removed
    // ...
}
```

The Tenant module supports hierarchical tenant relationships:

```php
// Create a parent tenant
$parentTenant = Tenant::create([
    'name' => 'Parent Company',
    'domain' => 'parent.example.com',
]);

// Create a child tenant that inherits configuration
$childTenant = Tenant::create([
    'name' => 'Child Division',
    'domain' => 'child.example.com',
    'parent_id' => $parentTenant->id,
]);

// Get effective configuration (inherited from parent if available)
$config = $childTenant->getEffectiveConfig();
```

## Tenant Configuration

### Setting Tenant Configuration

```php
// Set configuration values
$tenant->config = new TenantConfig([
    'theme' => 'dark',
    'features' => ['chat', 'notifications'],
    'limits' => [
        'users' => 50,
        'storage' => 5120,
    ],
]);
$tenant->save();

// Access configuration values
$theme = $tenant->config->theme;
$hasChat = in_array('chat', $tenant->config->features);
$userLimit = $tenant->config->limits['users'] ?? 10;
```

## Frontend Integration

### SSR Integration

The multi-tenancy system integrates with the frontend through the `TenantCacheService` in `src-ssr/services/TenantCache.ts`. This service:

- Caches tenant configurations for performance
- Normalizes configuration between backend and frontend formats
- Handles tenant resolution by domain
- Supports configuration inheritance from parent tenants

```typescript
// src-ssr/services/TenantCache.ts
import { TenantCache } from './TenantCache';

// In your SSR middleware
export default defineEventHandler(async (event) => {
  const domain = getRequestHost(event);
  const tenant = await TenantCache.resolveTenant(domain);
  
  // Apply tenant configuration to SSR context
  event.context.tenant = tenant;
  
  // Make tenant config available to the frontend
  setResponseHeader(event, 'X-Tenant-ID', tenant.id);
});
```

The multi-tenancy system provides secure endpoints for SSR tenant resolution:

```typescript
// Fetch tenant configuration for the current domain
const response = await fetch('https://api.example.com/tenant', {
  headers: {
    'X-API-Key': process.env.SSR_API_KEY,
  },
});
const tenantConfig = await response.json();

// Or preload all tenants for faster resolution
const allTenants = await fetch('https://api.example.com/tenant/cache', {
  headers: {
    'X-API-Key': process.env.SSR_API_KEY,
  },
});
```

### Client-Side Access

On the client side, tenant configuration with `PUBLIC` visibility is available via the global `window.TENANT_CONFIG` object:

```javascript
// Access tenant configuration in Vue components
const appName = window.TENANT_CONFIG.appName;
const theme = window.TENANT_CONFIG.theme || 'default';
const features = window.TENANT_CONFIG.features || [];

// Use in component setup
const setup = () => {
  const tenantConfig = window.TENANT_CONFIG;
  return {
    appName: tenantConfig.appName,
    logoUrl: tenantConfig.logoUrl,
  };
};
```

### WebSocket Integration

The multi-tenancy system provides tenant-scoped broadcast channels for real-time features:

```javascript
// In a Vue component
Echo.private(`tenant.${tenantId}.User.${userId}`)
  .notification((notification) => {
    // Handle user-specific notification
  });

// For presence channels with user information
Echo.join(`tenant.${tenantId}.chat`)
  .here((users) => {
    // Handle users currently in the channel
  })
  .joining((user) => {
    // Handle user joining
  })
  .leaving((user) => {
    // Handle user leaving
  });
```

These channels ensure that users can only access data from their own tenant.

## Development and Testing

### Tenant Factories and Seeders

The Tenant module includes factories and seeders to help you quickly set up a multi-tenant environment for development and testing.

#### TenantFactory

The `TenantFactory` creates basic tenant records with random data:

```php
// Create a tenant with random data
$tenant = Tenant::factory()->create();

// Create a tenant with specific attributes
$tenant = Tenant::factory()->create([
    'name' => 'Custom Tenant',
    'domain' => 'custom.example.com',
]);
```

#### TenantConfigFactory

The `TenantConfigFactory` creates tenant configuration with sensible defaults based on your local environment variables:

```php
// Create a tenant with configuration
$config = TenantConfigFactory::create(
    apiDomain: 'api.tenant1.example.com',
    appName: 'My Application',
    appEnv: 'local',
    mailFromName: 'Support Team',
    mailFromAddress: 'support@example.com',
    capacitorScheme: 'myapp',  // For mobile deep linking
);

$tenant = Tenant::factory()->create([
    'config' => $config,
]);
```

The factory automatically sets up visibility levels for configuration properties, ensuring sensitive data is only exposed to appropriate contexts.

#### TenantSeeder

The `TenantSeeder` creates a complete multi-tenant environment for development:

```php
// Run the seeder
php artisan db:seed --class=\Modules\Tenant\database\seeders\TenantSeeder
```

The seeder creates:

1. API tenants for backend services
2. Frontend tenants for user interfaces
3. Internal Docker tenants for container communication
4. Test users for each tenant

### Environment Variables for Seeding

The tenant seeding process uses several environment variables to create a consistent development environment:

| Variable | Description | Default |
|----------|-------------|--------|
| `QUVEL_DEFAULT_PASSWORD` | Default password for seeded users | `12345678` |
| `QUVEL_API_DOMAIN` | Primary API domain for development | `api.quvel.127.0.0.1.nip.io` |
| `QUVEL_LAN_DOMAIN` | LAN-accessible domain for development | `quvel.192.168.86.21.nip.io` |

These variables are defined in your `.env` file and accessed through the `config/quvel.php` configuration.

### Testing with the Tenant TestCase

The Tenant module provides a specialized `TestCase` class that makes it easy to write tests for multi-tenant applications. This class automatically sets up the tenant context and provides helper methods for working with tenants in tests.

```php
namespace Modules\YourModule\Tests;

use Modules\Tenant\Tests\TestCase;
use Modules\YourModule\Models\Product;

class ProductTest extends TestCase
{
    public function test_products_belong_to_tenant()
    {
        // The tenant is already seeded and set in the context
        $product = Product::factory()->create([
            'name' => 'Test Product',
        ]);
        
        // The product is automatically associated with the current tenant
        $this->assertEquals($this->tenant->id, $product->tenant_id);
    }
    
    public function test_with_custom_tenant_config()
    {
        // Create a custom tenant configuration for testing
        $config = $this->createTenantConfig();
        
        // Update the tenant with the custom config
        $this->tenant->config = $config;
        $this->tenant->save();
        
        // Test with the custom configuration
        // ...
    }
    
    public function test_with_tenant_context_mock()
    {
        // Set up a mock tenant context
        $this->seedMock();
        
        // Configure the mock
        $this->tenantContextMock->shouldReceive('get')
            ->andReturn($this->tenant);
            
        // Test code that uses the tenant context
        // ...
    }
}
```

### Testing with Multiple Tenants

You can also test scenarios involving multiple tenants by creating additional tenants and switching contexts:

```php
// In your test
public function test_tenant_isolation()
{
    // Create a second tenant (first one is already seeded)
    $tenant2 = Tenant::factory()->create();
    
    // Create a record for tenant 1 (current tenant)
    $product1 = Product::factory()->create(['name' => 'Tenant 1 Product']);
    
    // Switch tenant context
    setTenant($tenant2->id);
    
    // Create a record for tenant 2
    $product2 = Product::factory()->create(['name' => 'Tenant 2 Product']);
    
    // Verify tenant isolation
    $this->assertCount(1, Product::all());
    $this->assertEquals('Tenant 2 Product', Product::first()->name);
    
    // Switch back to tenant 1
    setTenant($this->tenant->id);
    
    // Verify tenant 1 only sees its own data
    $this->assertCount(1, Product::all());
    $this->assertEquals('Tenant 1 Product', Product::first()->name);
}
```

## Security Considerations

The Tenant module implements several security measures:

1. **Tenant Isolation**: All database queries are automatically scoped to the current tenant
2. **Domain Validation**: Tenants are resolved based on the request domain
3. **Configuration Visibility**: Sensitive configuration is filtered before being sent to the client
4. **Internal Request Validation**: Tenant endpoints are secured with IP and API key verification
5. **Context Hydration**: Tenant context is maintained across asynchronous operations

---

[← Back to Backend Documentation](./README.md)
