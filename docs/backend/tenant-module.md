# Multi-Tenancy System

## Overview

QuVel Kit provides a comprehensive config-driven multi-tenancy system that allows a single application instance to serve multiple isolated tenants. The system implements domain-based tenant resolution, sophisticated configuration management, and robust security measures to ensure complete tenant isolation.

### Key Features

- **Config-Driven Architecture**: Modules declare tenant integration in `config/tenant.php` files
- **Zero Runtime Overhead**: Configurations loaded once at application boot for optimal performance
- **Dynamic Configuration System**: Flexible key-value configuration with no hard-coded properties
- **Configuration Pipeline**: 12 specialized pipes for comprehensive tenant isolation
- **Multiple Isolation Strategies**: From shared databases to complete database-per-tenant setups
- **Module Independence**: Modules use simple string values without importing tenant dependencies
- **Template System**: Two-tier system (basic and isolated) for different tenant requirements
- **Inheritance**: Child tenants automatically inherit parent configurations
- **Visibility Control**: Three-tier security model (public, protected, private)
- **Security Middleware**: Prevents cross-tenant session hijacking and CSRF attacks

## Quick Start

### 1. Add Tenant Support to Your Module

Create `config/tenant.php` in your module:

```php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => [
                'feature_enabled' => true,
                'api_url' => 'https://api.example.com',
                'max_users' => 50,
            ],
            'visibility' => [
                'feature_enabled' => 'public',
                'api_url' => 'protected', 
                // 'max_users' private by default,
            ],
            'priority' => 50,
        ],
    ],
    
    'pipes' => [
        \Modules\YourModule\Pipes\YourConfigPipe::class,
    ],
    
    'tables' => [
        'your_table' => [
            'after' => 'id',
            'cascade_delete' => true,
        ],
    ],
];
```

### 2. Create a Configuration Pipe (Optional)

```php
namespace Modules\YourModule\Pipes;

use Modules\Tenant\Contracts\ConfigurationPipeInterface;

class YourConfigPipe implements ConfigurationPipeInterface
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        if (isset($tenantConfig['your_api_key'])) {
            $config->set('your_module.api_key', $tenantConfig['your_api_key']);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config, 
            'tenantConfig' => $tenantConfig
        ]);
    }

    public function handles(): array
    {
        return ['your_api_key', 'your_feature_enabled'];
    }

    public function priority(): int
    {
        return 50;
    }
}
```

### 3. Use in Your Application

```php
// Get current tenant
$tenant = getTenant();

// Access tenant configuration
$apiUrl = getTenantConfig('api_url', 'default-url');
$isEnabled = getTenantConfig('feature_enabled', false);

// Set configuration
setTenantConfig('new_setting', 'value', 'public');
```

## Architecture Overview

### Core Components

| Component | Description | Documentation |
|-----------|-------------|---------------|
| **TenantMiddleware** | Primary tenant resolution and configuration | [Tenant Middleware →](./tenant-middleware.md) |
| **Configuration Pipes** | 12 specialized pipes for tenant isolation | [Configuration Pipes →](./tenant-configuration-pipes.md) |
| **DynamicTenantConfig** | Flexible configuration with inheritance | [Configuration System →](./tenant-configuration.md) |
| **TenantContext** | Request-scoped tenant state management | [Configuration System →](./tenant-configuration.md) |
| **Security Middleware** | Cross-tenant protection (CSRF, sessions) | [Tenant Middleware →](./tenant-middleware.md) |

### Request Flow

```
Request → TenantMiddleware → Tenant Resolution → Configuration Pipeline → Security Validation → Application
```

1. **TenantMiddleware** resolves tenant from domain
2. **Configuration Pipeline** applies tenant-specific settings via 12 pipes
3. **Security Middleware** validates sessions and CSRF tokens
4. **Application Logic** executes with tenant context

## Multi-Tenancy Strategies

QuVel Kit supports multiple tenancy strategies that can be combined:

### 1. Database Per Tenant

- **Complete database isolation** via DatabaseConfigPipe
- **Use Case**: Enterprise customers requiring strict data separation
- **Benefits**: Maximum security, independent backups, custom schemas

### 2. Shared Database with Isolation

- **Tenant prefixes and scoping** via Cache, Redis, and Session pipes
- **Use Case**: Standard SaaS tenants
- **Benefits**: Cost-effective, easy maintenance, good performance

### 3. Service-Level Isolation

- **Tenant-specific third-party services** via Services and Mail pipes
- **Use Case**: White-label solutions
- **Benefits**: Complete service branding and isolation

### 4. Hybrid Approaches

- **Mix strategies** based on tenant type and requirements
- **Use Case**: Mixed customer base with different needs
- **Benefits**: Balanced security, cost, and performance

## System Components

The tenant system consists of several integrated components:

- **[Configuration Pipes →](./tenant-configuration-pipes.md)** - 12 specialized pipes for comprehensive tenant isolation (database per tenant, cache isolation, etc.)
- **[Security Middleware →](./tenant-middleware.md)** - Three middleware components for tenant resolution and cross-tenant protection
- **[Configuration System →](./tenant-configuration.md)** - Flexible key-value storage with inheritance and visibility controls
- **[Integration Examples →](./tenant-examples.md)** - Real-world patterns and complete tenant setups

## Module Integration Patterns

### Template System

QuVel Kit provides two tenant templates:

| Template | Use Case | Features |
|----------|----------|----------|
| **basic** | Standard SaaS tenants | Shared resources, basic isolation, cost-effective |
| **isolated** | Enterprise customers | Dedicated resources, maximum isolation, premium features |

### Configuration Seeders

Modules provide default configurations through seeders:

```php
'seeders' => [
    'basic' => [
        'config' => [
            'feature_enabled' => true,
            'max_users' => 50,
        ],
        'visibility' => [
            'feature_enabled' => 'public',
            'max_users' => 'protected',
        ],
        'priority' => 50,
    ],
    'isolated' => [
        'config' => function(string $template, array $config): array {
            return [
                'feature_enabled' => true,
                'max_users' => -1, // Unlimited
                'premium_features' => true,
            ];
        },
        'priority' => 20,
    ],
],
```

## Complete Examples

### Basic SaaS Tenant

```php
$tenant = Tenant::create([
    'name' => 'Small Business Inc',
    'domain' => 'smallbiz.example.com',
    'template' => 'basic',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Small Business App',
    'max_users' => 25,
    'features' => ['basic_reporting' => true],
], [
    'app_name' => 'public',
    'max_users' => 'protected',
    'features' => 'public',
    // Other keys private by default
]);

$tenant->config = $config;
$tenant->save();
```

### Enterprise Isolated Tenant

```php
$tenant = Tenant::create([
    'name' => 'Enterprise Corp',
    'domain' => 'enterprise.example.com', 
    'template' => 'isolated',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Enterprise Portal',
    // Dedicated database
    'db_host' => 'enterprise-db.example.com',
    'db_database' => 'enterprise_tenant_db',
    // Dedicated cache
    'redis_host' => 'enterprise-redis.example.com',
    // Premium features
    'max_users' => -1,
    'features' => ['advanced_reporting' => true, 'api_access' => true],
], [
    'app_name' => 'public',
    'features' => 'public',
    // Database and Redis configs private by default
]);

$tenant->config = $config;
$tenant->save();
```

**More Examples**: [Complete Examples →](./tenant-examples.md)

## Advanced Topics

### Custom Tenant Resolution

Replace the default domain-based resolver:

```php
// config/tenant.php
return [
    'resolver' => MyCustomResolver::class,
];
```

### Tenant-Aware Models

Use automatic tenant scoping:

```php
use Modules\Tenant\Traits\TenantScopedModel;

class Product extends Model
{
    use TenantScopedModel;
    // Automatically scoped to current tenant
}
```

### Configuration Inheritance

Parent-child tenant relationships:

```php
$childTenant = Tenant::create([
    'name' => 'Regional Office',
    'parent_id' => $parentTenant->id, // Inherits parent config
]);

$effectiveConfig = $childTenant->getEffectiveConfig();
// Contains both parent and child configurations
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `TENANT_SSR_PRELOAD_TENANTS` | Preload all tenants at SSR boot | `true` |
| `TENANT_SSR_RESOLVER_TTL` | Domain resolution cache TTL (seconds) | `300` |
| `TENANT_SSR_CACHE_TTL` | Full tenant cache TTL (seconds) | `300` |

## Testing with Tenants

```php
namespace Modules\YourModule\Tests;

use Modules\Tenant\Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_products_belong_to_tenant()
    {
        // Tenant is automatically seeded and set
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $this->assertEquals($this->tenant->id, $product->tenant_id);
    }
}
```

## Troubleshooting

### Configuration Not Applied

```php
// Check configuration
$config = getTenantConfig();
dd($config->all());

// Check effective configuration (with inheritance)
$effectiveConfig = getTenant()->getEffectiveConfig();
dd($effectiveConfig->all());
```

### Debugging Visibility

```php
$config = getTenantConfig();
$publicConfig = $config->getPublicConfig();
$protectedConfig = $config->getProtectedConfig();
dd(compact('publicConfig', 'protectedConfig'));
```

## Documentation Navigation

- **[Configuration Pipes →](./tenant-configuration-pipes.md)** - Deep dive into all 12 configuration pipes and multi-tenancy strategies
- **[Tenant Middleware →](./tenant-middleware.md)** - Security middleware and tenant resolution details  
- **[Configuration System →](./tenant-configuration.md)** - DynamicTenantConfig, inheritance, and helper functions
- **[Complete Examples →](./tenant-examples.md)** - Real-world integration patterns and advanced usage

---

[← Back to Backend Documentation](./README.md)
