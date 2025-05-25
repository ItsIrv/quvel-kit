# Multi-Tenancy System

## Overview

QuVel Kit provides a powerful multi-tenancy system that allows a single application instance to serve multiple isolated tenants. The system implements domain-based tenant resolution, tenant context management, and a dynamic tenant-specific configuration system with support for parent-child tenant relationships.

### Key Features

- **Dynamic Configuration System**: Flexible key-value configuration with no hard-coded properties
- **Configuration Pipeline**: Modular architecture allowing modules to register configuration processors
- **Tenant Tiers**: Support for different isolation levels (Basic, Standard, Premium, Enterprise)
- **Configuration Providers**: Modules can dynamically add configuration to tenant API responses
- **Inheritance**: Child tenants automatically inherit parent configurations
- **Visibility Control**: Three-tier security model (PUBLIC, PROTECTED, PRIVATE)

## Architecture

### Core Components

| Component | Description | Responsibility |
|-----------|-------------|----------------|
| `TenantContext` | Request-scoped service | Holds the current tenant information and provides access to tenant configuration |
| `TenantMiddleware` | HTTP middleware | Resolves the tenant for each request based on the domain |
| `HostResolver` | Domain resolver | Default implementation that maps domains to tenants |
| `FindService` | Tenant lookup service | Provides methods to find tenants by various criteria |
| `ConfigApplier` | Configuration service | Applies tenant-specific configuration to the application |
| `DynamicTenantConfig` | Value object | Flexible key-value configuration storage with visibility control |
| `DynamicTenantConfigCast` | Eloquent cast | Handles conversion between JSON and `DynamicTenantConfig` objects |
| `ConfigurationPipeline` | Pipeline manager | Orchestrates configuration pipes for processing tenant configs |
| `TenantConfigProviderRegistry` | Provider registry | Manages modules that provide additional tenant configuration |

## Dynamic Tenant Configuration System

### Overview

The tenant configuration system uses a flexible, dynamic approach that allows you to override virtually any Laravel configuration value on a per-tenant basis. Unlike traditional static configuration, this system enables:

- **Dynamic Properties**: No hard-coded configuration fields - add any key-value pairs as needed
- **Module Extensibility**: Modules can register their own configuration processors via pipes
- **Configuration Pipeline**: Chain of processors that apply tenant-specific settings
- **Tiered Isolation**: Different levels of resource isolation based on tenant tier
- **Runtime Overrides**: Override Laravel's configuration values at runtime
- **Inheritance**: Child tenants automatically inherit parent configurations
- **Visibility Control**: Three-tier security model for configuration exposure

### Configuration Architecture

#### Configuration Pipeline

The system uses a pipeline pattern where configuration flows through registered processors:

```
Tenant → ConfigurationPipeline → [CorePipe, DatabasePipe, CachePipe, ...] → Laravel Config
```

Each pipe handles specific configuration domains and can be registered by any module.

#### Core Components

1. **DynamicTenantConfig**: Flexible value object that stores configuration as key-value pairs
2. **ConfigurationPipeline**: Manages and executes configuration pipes
3. **ConfigurationPipeInterface**: Contract for creating configuration pipes
4. **DynamicTenantConfigCast**: Handles database serialization

#### Tenant Tiers (Optional)

The tier system is **disabled by default**. When enabled via `TENANT_ENABLE_TIERS=true`, it provides different isolation levels:

| Tier | Database | Cache | Redis | Description |
|------|----------|-------|-------|-------------|
| **Basic** | Shared | Shared | Shared | Row-level isolation only |
| **Standard** | Shared | Dedicated | Dedicated | Row-level isolation with dedicated cache |
| **Premium** | Dedicated | Dedicated | Dedicated | Full database and cache isolation |
| **Enterprise** | Dedicated | Dedicated | Dedicated | Full isolation with custom infrastructure |

### Configuration Visibility

Configuration values have different visibility levels defined in the `TenantConfigVisibility` enum:

| Level | Description | Access |
|-------|-------------|--------|
| `PUBLIC` | Exposed to the browser | Available via `window.__TENANT_CONFIG__` |
| `PROTECTED` | Exposed to SSR server | Available in SSR context but not browser |
| `PRIVATE` | Backend only | Never exposed outside the backend |

### Working with Dynamic Configuration

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

// Create configuration for a basic tier tenant
$config = new DynamicTenantConfig([
    'app_name' => 'My App',
    'mail_from_address' => 'support@myapp.com',
    'mail_from_name' => 'My App Support',
]);

// Set visibility levels
$config->setVisibility('app_name', TenantConfigVisibility::PUBLIC);
$config->setVisibility('mail_from_address', TenantConfigVisibility::PROTECTED);

// Set tier
$config->setTier('basic');

// Save to tenant
$tenant->config = $config;
$tenant->save();
```

### Configuration Inheritance

Child tenants automatically inherit parent configuration:

```php
$parent = Tenant::find(1);
$parent->config = new DynamicTenantConfig([
    'app_name' => 'Parent App',
    'mail_from_address' => 'parent@example.com',
]);

$child = Tenant::find(2);
$child->parent_id = $parent->id;
$child->config = new DynamicTenantConfig([
    'app_name' => 'Child App', // Overrides parent
    // mail_from_address inherited from parent
]);

$effectiveConfig = $child->getEffectiveConfig();
// app_name = 'Child App' (overridden)
// mail_from_address = 'parent@example.com' (inherited)
```

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

## Configuration Providers

### Overview

Configuration Providers allow modules to dynamically add configuration to tenant API responses without modifying the core tenant structure. This enables modules to expose their settings to the frontend.

### How It Works

When a tenant resource is requested through the API, the system:

1. Loads the tenant's stored configuration
2. Applies all registered configuration providers
3. Merges the configurations respecting visibility rules
4. Returns the enhanced configuration in the response

### Creating a Configuration Provider

```php
namespace Modules\YourModule\Providers;

use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;

class YourModuleTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config' => [
                // Your module's configuration
                'your_module_api_url' => config('your_module.api_url'),
                'your_module_features' => $this->getEnabledFeatures($tenant),
            ],
            'visibility' => [
                // Define visibility for each config key
                'your_module_api_url' => 'public',
                'your_module_features' => 'public',
            ],
        ];
    }

    public function priority(): int
    {
        return 50; // Higher priority runs first
    }
    
    private function getEnabledFeatures(Tenant $tenant): array
    {
        // Logic to determine features based on tenant tier
        $tier = $tenant->config->get('tier', 'basic');
        
        return match($tier) {
            'premium', 'enterprise' => ['basic', 'advanced', 'premium'],
            'standard' => ['basic', 'advanced'],
            default => ['basic'],
        };
    }
}
```

### Registering Configuration Providers

```php
public function boot(): void
{
    parent::boot();

    // Register tenant config provider
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                YourModuleTenantConfigProvider::class
            );
        });
    }
}
```

### Best Practices

1. **Use Appropriate Visibility**: Only expose what's needed at each level
2. **Namespace Your Keys**: Prefix with module name to avoid conflicts
3. **Document Your Configuration**: Comment what each config value does
4. **Consider Performance**: Don't add expensive computations in providers
5. **Handle Missing Config**: Use defaults when config values might not exist

### Advanced Usage

#### Conditional Configuration

You can provide different configuration based on tenant properties:

```php
public function getConfig(Tenant $tenant): array
{
    $config = [
        'base_feature' => true,
    ];
    
    // Add premium features for premium tenants
    if ($tenant->config->get('tier') === 'premium') {
        $config['premium_feature'] = true;
        $config['premium_limit'] = 1000;
    }
    
    return [
        'config' => $config,
        'visibility' => [
            'base_feature' => 'public',
            'premium_feature' => 'public',
            'premium_limit' => 'public',
        ],
    ];
}
```

### Built-in Configuration Providers

| Provider | Module | Provides |
|----------|--------|----------|
| `CoreTenantConfigProvider` | Core | Frontend URLs, API version, supported locales |
| `AuthTenantConfigProvider` | Auth | OAuth providers, authentication settings |

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

### Working with Dynamic Configuration

#### Setting Configuration

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

// Create configuration for a tenant
$config = new DynamicTenantConfig(
    // Configuration data
    [
        'app_name' => 'My Application',
        'mail_from_address' => 'support@app.com',
        'custom_setting' => 'value',
    ],
    // Visibility settings
    [
        'app_name' => TenantConfigVisibility::PUBLIC,
        'mail_from_address' => TenantConfigVisibility::PROTECTED,
        'custom_setting' => TenantConfigVisibility::PRIVATE,
    ],
    // Tenant tier
    'standard'
);

$tenant->config = $config;
$tenant->save();
```

#### Accessing Configuration

```php
$tenant = Tenant::find(1);
$config = $tenant->config;

// Access using get method
$appName = $config->get('app_name');
$mailFrom = $config->get('mail_from_address', 'default@app.com'); // with default

// Or use property access (backward compatible)
$appName = $config->app_name;

// Check if configuration exists
if ($config->has('custom_setting')) {
    $value = $config->get('custom_setting');
}
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

#### Modifying Configuration

```php
// Get existing config
$config = $tenant->config;

// Add or update values
$config->set('new_setting', 'value');
$config->set('nested.setting', ['key' => 'value']);

// Set visibility for new values
$config->setVisibility('new_setting', TenantConfigVisibility::PUBLIC);

// Remove a configuration
$config->forget('old_setting');

// Save changes
$tenant->config = $config;
$tenant->save();
```

## Configuration Pipes

### Creating a Configuration Pipe

Modules can register configuration pipes to process tenant settings:

```php
namespace Modules\YourModule\Pipes;

use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

class YourModuleConfigPipe implements ConfigurationPipeInterface
{
    public function handle(
        Tenant $tenant, 
        ConfigRepository $config, 
        array $tenantConfig, 
        callable $next
    ): mixed {
        // Apply your module's configuration
        if (isset($tenantConfig['your_module_enabled'])) {
            $config->set('your_module.enabled', $tenantConfig['your_module_enabled']);
        }
        
        // Pass to next pipe
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        // List of configuration keys this pipe handles
        return [
            'your_module_enabled',
            'your_module_api_key',
        ];
    }

    public function priority(): int
    {
        return 50; // Higher priority runs first
    }
}
```

### Registering Configuration Pipes

In your module's service provider:

```php
public function boot(): void
{
    parent::boot();

    // Register configuration pipe
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                \Modules\YourModule\Pipes\YourModuleConfigPipe::class
            );
        });
    }
}
```

### Built-in Configuration Pipes

QuVel Kit includes several built-in pipes:

| Pipe | Priority | Handles | Description |
|------|----------|---------|-------------|
| `CoreConfigPipe` | 100 | App settings, timezone, locale | Core application configuration |
| `DatabaseConfigPipe` | 90 | Database connection settings | Tenant-specific database (Premium+ tiers) |
| `CacheConfigPipe` | 80 | Cache store, prefix | Tenant-specific cache configuration |
| `RedisConfigPipe` | 75 | Redis connection settings | Tenant-specific Redis (Standard+ tiers) |
| `MailConfigPipe` | 70 | Mail driver, SMTP settings | Tenant-specific mail configuration |
| `SessionConfigPipe` | 60 | Session driver, lifetime | Tenant-specific session settings |
| `AuthConfigPipe` | 50 | OAuth providers, auth settings | Authentication configuration |

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

#### DynamicTenantConfigFactory

The `DynamicTenantConfigFactory` creates tenant configuration based on tier:

```php
// Create basic tier configuration (minimal)
$config = DynamicTenantConfigFactory::createBasicTier(
    appName: 'My Application',
    mailFromName: 'Support Team',
    mailFromAddress: 'support@example.com'
);

// Create standard tier configuration
$config = DynamicTenantConfigFactory::createStandardTier(
    appName: 'My Application',
    cachePrefix: 'tenant_123',
    redisDatabase: 1
);

// Create premium tier configuration
$config = DynamicTenantConfigFactory::createPremiumTier(
    appName: 'My Application',
    dbDatabase: 'tenant_123_db',
    dbUsername: 'tenant_123_user',
    dbPassword: 'secure_password'
);

// Or create from environment with custom settings
$config = DynamicTenantConfigFactory::createFromEnv('premium', [
    'custom_api_key' => 'abc123',
    'feature_flags' => ['new_ui', 'advanced_search'],
]);

$tenant = Tenant::factory()->create([
    'config' => $config,
]);
```

The factory automatically sets appropriate visibility levels and only includes configuration relevant to each tier.

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

### Helper Functions

The Tenant module provides several helper functions for working with tenants:

```php
// Set tenant and apply configuration pipeline
setTenant($tenantId);           // By ID
setTenant('domain.com');        // By domain
setTenant($tenantInstance);     // By instance

// Set tenant context without applying configuration
// Useful for seeders and tests where you don't want to change connections
setTenantContext($tenantId);    // By ID
setTenantContext('domain.com'); // By domain
setTenantContext($tenant);      // By instance

// Get current tenant
$tenant = getTenant();

// Get tenant configuration
$config = getTenantConfig();                    // Get all config
$value = getTenantConfig('app_name');           // Get specific value
$value = getTenantConfig('missing', 'default'); // With default

// Set tenant configuration (in memory only)
setTenantConfig('key', 'value');
setTenantConfig('key', 'value', 'public');      // With visibility

// Create configuration for new tenants
$config = createTenantConfig([
    'app_name' => 'My App',
    'cache_prefix' => 'myapp',
], [
    'app_name' => 'public',
    'cache_prefix' => 'private',
], 'premium');

// Tier-related helpers (when tiers are enabled)
$tier = getTenantTier();                        // Get current tier
$hasFeature = tenantHasFeature('custom_domain');
$meetsTier = tenantMeetsMinimumTier('premium');
$features = getTenantFeatures();
$limits = getTenantLimits();
$userLimit = getTenantLimit('users', 5);       // With default
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
