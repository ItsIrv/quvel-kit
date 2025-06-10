# Multi-Tenancy System

## Overview

QuVel Kit provides a powerful config-driven multi-tenancy system that allows a single application instance to serve multiple isolated tenants. The system implements domain-based tenant resolution, tenant context management, and automatic configuration loading from module config files with support for parent-child tenant relationships.

### Key Features

- **Config-Driven Architecture**: Modules declare tenant integration in `config/tenant.php` files
- **Zero Runtime Overhead**: Configurations loaded once at application boot for optimal performance
- **Dynamic Configuration System**: Flexible key-value configuration with no hard-coded properties
- **Configuration Pipeline**: Modular architecture with automatic pipe registration from config files
- **Module Independence**: Modules use simple string values without importing tenant dependencies
- **Template System**: Two-tier system (basic and isolated) for different tenant requirements
- **Inheritance**: Child tenants automatically inherit parent configurations
- **Visibility Control**: Three-tier security model (public, protected, private)

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
            ],
            'visibility' => [
                'feature_enabled' => 'public',
                'api_url' => 'protected',
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
        
        return $next(['tenant' => $tenant, 'config' => $config, 'tenantConfig' => $tenantConfig]);
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
```

## Architecture

### Core Components

| Component | Description | Responsibility |
|-----------|-------------|----------------|
| `TenantContext` | Request-scoped service | Holds the current tenant information and provides access to tenant configuration |
| `TenantMiddleware` | HTTP middleware | Resolves the tenant for each request based on the domain |
| `TenantModuleConfigLoader` | Config loader service | Scans and loads tenant configurations from module config files |
| `HostResolver` | Domain resolver | Default implementation that maps domains to tenants |
| `FindService` | Tenant lookup service | Provides methods to find tenants by various criteria |
| `DynamicTenantConfig` | Value object | Flexible key-value configuration storage with visibility control |
| `ConfigurationPipeline` | Pipeline manager | Orchestrates configuration pipes for processing tenant configs |
| `TenantConfigSeederRegistry` | Registry service | Manages and evaluates configuration seeders with lazy loading |

### Configuration Flow

The system uses a pipeline pattern where configuration flows through registered processors:

```
Module Config Files → TenantModuleConfigLoader → TenantConfigSeederRegistry → ConfigurationPipeline → Laravel Config + Frontend
```

Each pipe handles specific configuration domains and is automatically registered from module config files.

## Module Integration

### Complete Config File Structure

The `config/tenant.php` file supports four main sections:

```php
<?php

return [
    // Configuration seeders for different tenant templates
    'seeders' => [
        'basic' => [
            'config' => [/* configuration values */],
            'visibility' => [/* visibility settings */],
            'priority' => 50,
        ],
        'isolated' => [
            'config' => function(string $template, array $config): array {
                // Dynamic configuration logic
                return [/* configuration values */];
            },
            'visibility' => [/* visibility settings */],
            'priority' => 50,
        ],
    ],
    
    // Shared seeders that apply to all templates
    'shared_seeders' => [
        'common_config' => [
            'config' => [/* configuration values */],
            'visibility' => [/* visibility settings */],
            'priority' => 15,
        ],
    ],
    
    // Configuration pipes for processing tenant configs
    'pipes' => [
        \Modules\YourModule\Pipes\YourConfigPipe::class,
    ],
    
    // Tables that need tenant_id column and scoping
    'tables' => [
        'your_table' => [
            'after' => 'id',
            'cascade_delete' => true,
            'drop_uniques' => [['unique_column']],
            'tenant_unique_constraints' => [['unique_column']],
        ],
    ],
    
    // Paths and patterns excluded from tenant resolution
    'exclusions' => [
        'paths' => ['/webhook/callback'],
        'patterns' => ['admin/*', 'api/webhooks/*'],
    ],
];
```

### Template System

QuVel Kit provides two tenant templates with different resource allocation:

| Template | Use Case | Features |
|----------|----------|----------|
| `basic` | Shared resources, simple tenants | Minimal configuration, shared infrastructure |
| `isolated` | Enterprise tenants, full isolation | Dedicated databases, cache, sessions, unique resource identifiers |

### Configuration Seeders

Seeders provide default configuration values for tenants. They support both static values and dynamic functions:

#### Static Configuration

```php
'seeders' => [
    'basic' => [
        'config' => [
            'mail_driver' => 'smtp',
            'mail_host' => 'smtp.example.com',
            'session_lifetime' => 120,
        ],
        'visibility' => [
            'mail_driver' => 'protected',
            'session_lifetime' => 'public',
        ],
        'priority' => 50,
    ],
],
```

#### Dynamic Configuration

```php
'seeders' => [
    'isolated' => [
        'config' => function(string $template, array $config): array {
            // Generate unique values for isolated tenants
            $tenantId = substr(uniqid(), -8);
            
            return [
                'cache_prefix' => "tenant_{$tenantId}_",
                'session_cookie' => "session_{$tenantId}",
                'db_database' => "tenant_{$tenantId}_db",
            ];
        },
        'visibility' => [
            'cache_prefix' => 'private',
            'session_cookie' => 'protected',
        ],
        'priority' => 20,
    ],
],
```

### Visibility System

Configuration values have three visibility levels using simple strings:

| Level | Description | Access |
|-------|-------------|--------|
| `'public'` | Exposed to browser via `window.__TENANT_CONFIG__` | Backend + SSR + Browser |
| `'protected'` | Available to SSR server but not browser | Backend + SSR |
| `'private'` | Backend only, never exposed | Backend only |

### Configuration Pipes

Pipes process tenant configuration and apply it to Laravel's config system:

```php
namespace Modules\YourModule\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

class YourModuleConfigPipe implements ConfigurationPipeInterface
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply tenant configuration to Laravel config
        if (isset($tenantConfig['your_api_key'])) {
            $config->set('services.your_service.key', $tenantConfig['your_api_key']);
        }
        
        if (isset($tenantConfig['your_feature_enabled'])) {
            $config->set('your_module.enabled', $tenantConfig['your_feature_enabled']);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return ['your_api_key', 'your_feature_enabled'];
    }

    public function priority(): int
    {
        return 50; // Higher numbers run first
    }
}
```

### Tenant Tables

Tables that need tenant isolation are automatically configured:

```php
'tables' => [
    'products' => [
        'after' => 'id',                      // Column after which to add tenant_id
        'cascade_delete' => true,             // Delete records when tenant is deleted
        'drop_uniques' => [['sku']],          // Unique constraints to drop
        'tenant_unique_constraints' => [      // Tenant-scoped unique constraints
            ['sku'],
            ['name', 'category_id'],
        ],
    ],
],
```

### Path Exclusions

Some paths should bypass tenant resolution:

```php
'exclusions' => [
    'paths' => [
        '/webhook/stripe',
        '/health-check',
    ],
    'patterns' => [
        'admin/*',
        'api/webhooks/*',
        'system/*',
    ],
],
```

## Configuration System

### Dynamic Tenant Configuration

The system uses `DynamicTenantConfig` value objects that store configuration as flexible key-value pairs:

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

// Create configuration
$config = new DynamicTenantConfig([
    'app_name' => 'My App',
    'mail_from_address' => 'support@myapp.com',
    'custom_feature' => true,
], [
    'app_name' => 'public',
    'mail_from_address' => 'protected',
    'custom_feature' => 'private',
]);

$tenant->config = $config;
$tenant->save();
```

### Configuration Inheritance

Child tenants automatically inherit parent configuration:

```php
$parent = Tenant::find(1);
$parent->config = new DynamicTenantConfig([
    'mail_host' => 'smtp.parent.com',
    'mail_from_name' => 'Parent Corp',
]);

$child = Tenant::find(2);
$child->parent_id = $parent->id;
$child->config = new DynamicTenantConfig([
    'app_name' => 'Child App', // Overrides parent
    // mail_host and mail_from_name inherited from parent
]);

$effectiveConfig = $child->getEffectiveConfig();
// Contains both parent and child configurations
```

### Working with Configuration

#### Getting Configuration Values

```php
// Get current tenant
$tenant = getTenant();

// Access configuration
$config = $tenant->config;
$appName = $config->get('app_name');
$mailFrom = $config->get('mail_from_address', 'default@app.com');

// Check if configuration exists
if ($config->has('custom_setting')) {
    $value = $config->get('custom_setting');
}

// Get effective configuration (including inherited values)
$effectiveConfig = $tenant->getEffectiveConfig();
```

#### Setting Configuration Values

```php
// Get existing config
$config = $tenant->config;

// Add or update values
$config->set('new_setting', 'value');
$config->set('nested.setting', ['key' => 'value']);

// Set visibility for new values
$config->setVisibility('new_setting', 'public');

// Remove a configuration
$config->forget('old_setting');

// Save changes
$tenant->config = $config;
$tenant->save();
```

#### Helper Functions

```php
// Set tenant and apply configuration
setTenant($tenantId);           // By ID
setTenant('domain.com');        // By domain
setTenant($tenantInstance);     // By instance

// Set tenant context without applying configuration
setTenantContext($tenantId);

// Get current tenant
$tenant = getTenant();

// Get tenant configuration
$config = getTenantConfig();                    // Get all config
$value = getTenantConfig('app_name');           // Get specific value
$value = getTenantConfig('missing', 'default'); // With default

// Set tenant configuration (in memory only)
setTenantConfig('key', 'value');
setTenantConfig('key', 'value', 'public');      // With visibility
```

## Complete Examples

### Example 1: Auth Module Integration

```php
// Modules/Auth/config/tenant.php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => [
                'session_cookie' => 'quvel_session',
                'socialite_providers' => [],
                'oauth_credentials' => [],
            ],
            'visibility' => [
                'session_cookie' => 'protected',
                'socialite_providers' => 'public',
            ],
            'priority' => 20,
        ],
        'isolated' => [
            'config' => function (string $template, array $config): array {
                // Generate unique session cookie for isolated tenants
                $sessionCookie = 'quvel_session';
                if (isset($config['cache_prefix'])) {
                    if (preg_match('/tenant_([a-z0-9]+)_?/i', $config['cache_prefix'], $matches)) {
                        $sessionCookie = "quvel_{$matches[1]}";
                    }
                }

                return [
                    'session_cookie' => $sessionCookie,
                    'socialite_providers' => ['google', 'microsoft'],
                    'oauth_credentials' => [
                        'google' => [
                            'client_id' => env('GOOGLE_CLIENT_ID'),
                            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                        ],
                        'microsoft' => [
                            'client_id' => env('MICROSOFT_CLIENT_ID'),
                            'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
                        ],
                    ],
                    'session_lifetime' => 240, // 4 hours for isolated tenants
                ];
            },
            'visibility' => [
                'session_cookie' => 'protected',
                'socialite_providers' => 'public',
                'oauth_credentials' => 'private',
                'session_lifetime' => 'protected',
            ],
            'priority' => 20,
        ],
    ],

    'pipes' => [
        \Modules\Auth\Pipes\AuthConfigPipe::class,
    ],
];
```

### Example 2: Core Module Integration

```php
// Modules/Core/config/tenant.php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => function (string $template, array $config): array {
                $domain = $config['domain'] ?? 'example.com';
                $apiUrl = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                return [
                    'app_name' => $config['_seed_app_name'] ?? 'QuVel',
                    'app_url' => $apiUrl,
                    'frontend_url' => $frontendUrl,
                    'mail_from_name' => $config['_seed_mail_from_name'] ?? 'QuVel Support',
                    'mail_from_address' => $config['_seed_mail_from_address'] ?? "support@{$domain}",
                ];
            },
            'visibility' => [
                'app_name' => 'public',
                'app_url' => 'public',
                'frontend_url' => 'protected',
                'mail_from_name' => 'private',
                'mail_from_address' => 'private',
            ],
            'priority' => 10, // Run very early
        ],
        'isolated' => [
            'config' => function (string $template, array $config): array {
                $domain = $config['domain'] ?? 'example.com';
                $apiUrl = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                $coreConfig = [
                    'app_name' => $config['_seed_app_name'] ?? 'QuVel',
                    'app_url' => $apiUrl,
                    'frontend_url' => $frontendUrl,
                    'mail_from_name' => $config['_seed_mail_from_name'] ?? 'QuVel Support',
                    'mail_from_address' => $config['_seed_mail_from_address'] ?? "support@{$domain}",
                ];

                // Add internal API URL for isolated template
                if (!isset($config['internal_api_url'])) {
                    $internalDomain = str_replace(['https://', 'http://'], '', $apiUrl);
                    $coreConfig['internal_api_url'] = "http://{$internalDomain}:8000";
                }

                return $coreConfig;
            },
            'visibility' => [
                'app_name' => 'public',
                'app_url' => 'public',
                'frontend_url' => 'protected',
                'mail_from_name' => 'private',
                'mail_from_address' => 'private',
                'internal_api_url' => 'protected',
            ],
            'priority' => 10,
        ],
    ],

    'shared_seeders' => [
        'recaptcha' => [
            'config' => function (string $template, array $config): array {
                $recaptchaConfig = [];

                if (isset($config['_seed_recaptcha_site_key'])) {
                    $recaptchaConfig['recaptcha_site_key'] = $config['_seed_recaptcha_site_key'];
                    $recaptchaConfig['recaptcha_secret_key'] = $config['_seed_recaptcha_secret_key'] ?? '';
                } elseif (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
                    $recaptchaConfig['recaptcha_site_key'] = env('RECAPTCHA_GOOGLE_SITE_KEY');
                    $recaptchaConfig['recaptcha_secret_key'] = env('RECAPTCHA_GOOGLE_SECRET', '');
                }

                return $recaptchaConfig;
            },
            'visibility' => [
                'recaptcha_site_key' => 'public',
                'recaptcha_secret_key' => 'private',
            ],
            'priority' => 15,
        ],
    ],
];
```

### Example 3: Complete Tenant Configuration

```php
// Create a basic tenant
$tenant = Tenant::create([
    'name' => 'Small Business Inc',
    'domain' => 'smallbiz.example.com',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Small Business App',
    'app_timezone' => 'America/New_York',
    'mail_from_name' => 'Small Business Support',
    'mail_from_address' => 'support@smallbiz.example.com',
    'queue_connection' => 'database',
    'log_level' => 'info',
], [
    'app_name' => 'public',
    'app_timezone' => 'public',
    'mail_from_name' => 'protected',
    'mail_from_address' => 'private',
]);

$tenant->config = $config;
$tenant->save();
```

```php
// Create an isolated tenant with dedicated resources
$tenant = Tenant::create([
    'name' => 'Enterprise Solutions',
    'domain' => 'enterprise.example.com',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Enterprise Portal',
    'app_timezone' => 'America/Los_Angeles',
    
    // Dedicated database
    'db_connection' => 'mysql',
    'db_host' => 'enterprise-db.example.com',
    'db_database' => 'enterprise_tenant',
    'db_username' => 'enterprise_user',
    'db_password' => 'secure_password',
    
    // Dedicated cache
    'cache_store' => 'redis',
    'cache_prefix' => 'enterprise',
    'redis_host' => 'enterprise-redis.example.com',
    
    // Premium mail service
    'mail_mailer' => 'smtp',
    'mail_host' => 'smtp.enterprise.example.com',
    'mail_from_name' => 'Enterprise Support',
    'mail_from_address' => 'support@enterprise.example.com',
], [
    'app_name' => 'public',
    'app_timezone' => 'public',
    'mail_from_name' => 'protected',
    'mail_from_address' => 'private',
    'db_password' => 'private',
]);

$tenant->config = $config;
$tenant->save();
```

## Advanced Topics

### Custom Tenant Resolution

Replace the default domain-based resolver with your own implementation:

```php
// In config/tenant.php
return [
    'resolver' => MyCustomResolver::class,
];
```

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

### Tenant-Aware Models

Use the `TenantScopedModel` trait to automatically scope models to the current tenant:

```php
use Modules\Tenant\Traits\TenantScopedModel;

class Product extends Model
{
    use TenantScopedModel;
    
    // All queries automatically filtered by current tenant
    // New records automatically get tenant_id set
    // Prevents cross-tenant operations
}
```

### Testing with Tenants

Use the tenant TestCase for multi-tenant tests:

```php
namespace Modules\YourModule\Tests;

use Modules\Tenant\Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_products_belong_to_tenant()
    {
        // Tenant is already seeded and set in context
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        $this->assertEquals($this->tenant->id, $product->tenant_id);
    }
    
    public function test_tenant_isolation()
    {
        $tenant2 = Tenant::factory()->create();
        
        // Create product for current tenant
        Product::factory()->create(['name' => 'Tenant 1 Product']);
        
        // Switch to tenant 2
        setTenant($tenant2->id);
        Product::factory()->create(['name' => 'Tenant 2 Product']);
        
        // Verify isolation
        $this->assertCount(1, Product::all());
        $this->assertEquals('Tenant 2 Product', Product::first()->name);
    }
}
```

## Environment Variables

| Variable | Description | Default |
|----------|-------------|--------|
| `TENANT_SSR_PRELOAD_TENANTS` | Preload all tenants at SSR boot time | `true` |
| `TENANT_SSR_RESOLVER_TTL` | Domain resolution cache TTL (seconds) | `300` |
| `TENANT_SSR_CACHE_TTL` | Full tenant cache response TTL (seconds) | `300` |
| `QUVEL_DEFAULT_PASSWORD` | Default password for seeded users | `12345678` |
| `QUVEL_API_DOMAIN` | Primary API domain for development | `api.quvel.127.0.0.1.nip.io` |
| `QUVEL_LAN_DOMAIN` | LAN-accessible domain for development | `quvel.192.168.86.21.nip.io` |

## Built-in Components

### Configuration Pipes

QuVel Kit includes several built-in pipes:

| Pipe | Priority | Handles | Description |
|------|----------|---------|-------------|
| `CoreConfigPipe` | 100 | App settings, timezone, locale | Core application configuration |
| `DatabaseConfigPipe` | 90 | Database connection settings | Tenant-specific database (isolated) |
| `CacheConfigPipe` | 80 | Cache store, prefix | Tenant-specific cache configuration |
| `RedisConfigPipe` | 75 | Redis connection settings | Tenant-specific Redis (isolated) |
| `MailConfigPipe` | 70 | Mail driver, SMTP settings | Tenant-specific mail configuration |
| `SessionConfigPipe` | 60 | Session driver, lifetime | Tenant-specific session settings |
| `AuthConfigPipe` | 50 | OAuth providers, auth settings | Authentication configuration |

### Template Features

| Template | Features |
|----------|----------|
| **Basic** | Shared database, shared cache, basic session configuration, simple mail setup |
| **Isolated** | Dedicated database, Redis cache, unique session cookies, dedicated mail servers, resource isolation |

## Security Considerations

The tenant system implements several security measures:

1. **Tenant Isolation**: All database queries automatically scoped to current tenant
2. **Domain Validation**: Tenants resolved based on request domain
3. **Configuration Visibility**: Sensitive configuration filtered before client exposure
4. **Context Hydration**: Tenant context maintained across asynchronous operations
5. **Module Independence**: No cross-module dependencies for tenant integration

## Troubleshooting

### Configuration Not Applied

```php
// Check if configuration exists
$config = getTenantConfig();
dd($config->all()); // Show all configuration

// Check effective configuration (with inheritance)
$effectiveConfig = getTenant()->getEffectiveConfig();
dd($effectiveConfig->all());

// Verify pipe is handling the key
$pipeline = app(\Modules\Tenant\Services\ConfigurationPipeline::class);
$pipes = $pipeline->getPipes();
```

### Debugging Visibility

```php
// Check visibility settings
$config = getTenantConfig();
$publicConfig = $config->getPublicConfig();
$protectedConfig = $config->getProtectedConfig();
dd(compact('publicConfig', 'protectedConfig'));
```

---

[← Back to Backend Documentation](./README.md)
