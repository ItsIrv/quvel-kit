# Dynamic Tenant Configuration System

## Overview

The QuVel Kit tenant system has been refactored to support dynamic, extensible configuration with tiered isolation levels. This new architecture addresses several limitations of the previous system:

- **Dynamic Configuration**: No more hard-coded configuration properties
- **Module Extensibility**: Modules can register their own tenant configurations
- **Tiered Isolation**: Support for different levels of resource isolation
- **Partial Overrides**: Tenants only need to override what they actually use

## Architecture

### Configuration Pipeline

The new system uses a pipeline pattern for applying tenant configurations:

```php
// Configuration flows through registered pipes
Tenant → ConfigurationPipeline → [Pipe1, Pipe2, ..., PipeN] → Laravel Config
```

Each pipe handles specific configuration domains (core, database, cache, mail, etc.) and can be registered by any module.

### Core Components

1. **DynamicTenantConfig**: Flexible value object that stores configuration as key-value pairs
2. **ConfigurationPipeline**: Manages and executes configuration pipes
3. **ConfigurationPipeInterface**: Contract for creating configuration pipes
4. **DynamicTenantConfigCast**: Handles database serialization with backward compatibility

## Tenant Tiers

The system supports different isolation levels:

| Tier | Database | Cache | Description |
|------|----------|-------|-------------|
| **Basic** | Shared | Shared | Row-level isolation only |
| **Standard** | Shared | Dedicated | Row-level isolation with dedicated cache |
| **Premium** | Dedicated | Dedicated | Full database and cache isolation |
| **Enterprise** | Dedicated | Dedicated | Full isolation with custom infrastructure |

## Creating Configuration Pipes

### 1. Implement the Interface

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
        if (isset($tenantConfig['your_module_setting'])) {
            $config->set('your_module.setting', $tenantConfig['your_module_setting']);
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
        return [
            'your_module_setting',
            'your_module_other_setting',
        ];
    }

    public function priority(): int
    {
        return 50; // Higher runs first
    }
}
```

### 2. Register in Your Service Provider

```php
public function boot(): void
{
    parent::boot();

    // Register your configuration pipe
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                \Modules\YourModule\Pipes\YourModuleConfigPipe::class
            );
        });
    }
}
```

## Working with Dynamic Configuration

### Setting Tenant Configuration

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

// Create configuration for a basic tier tenant
$config = new DynamicTenantConfig([
    'app_name' => 'My App',
    'mail_from_address' => 'support@myapp.com',
    'mail_from_name' => 'My App Support',
], [
    'app_name' => TenantConfigVisibility::PUBLIC,
], 'basic');

$tenant->config = $config;
$tenant->save();
```

### Adding Module-Specific Configuration

```php
// Get existing config
$config = $tenant->config;

// Add your module's settings
$config->set('your_module.api_key', 'secret-key');
$config->set('your_module.enabled_features', ['feature1', 'feature2']);

// Set visibility
$config->setVisibility('your_module.enabled_features', TenantConfigVisibility::PUBLIC);

// Save
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

## Migration Guide

### From TenantConfig to DynamicTenantConfig

The system maintains backward compatibility during migration:

1. **Existing TenantConfig** objects are automatically converted to DynamicTenantConfig
2. **Property access** works the same: `$config->appName` or `$config->get('app_name')`
3. **No immediate changes required** to existing code

### Updating Seeders

Old approach (all configs required):
```php
TenantConfigFactory::create(
    apiDomain: $domain,
    appName: 'App',
    // ... 50+ required parameters
);
```

New approach (only what you need):
```php
// Basic tier - minimal config
DynamicTenantConfigFactory::createBasicTier(
    appName: 'App',
    mailFromName: 'Support',
    mailFromAddress: 'support@app.com'
);

// Or create from environment
DynamicTenantConfigFactory::createFromEnv('premium', [
    'custom_setting' => 'value'
]);
```

## Best Practices

1. **Use Tiers**: Choose the appropriate tier based on customer needs
2. **Minimal Overrides**: Only override what's necessary for each tenant
3. **Module Isolation**: Keep module configurations in separate pipes
4. **Visibility Control**: Mark configurations appropriately (PUBLIC, PROTECTED, PRIVATE)
5. **Test Inheritance**: Ensure child tenants properly inherit parent configurations

## Example: Complete Module Integration

```php
// 1. Create your configuration pipe
class PaymentConfigPipe implements ConfigurationPipeInterface
{
    public function handle($tenant, $config, $tenantConfig, $next): mixed
    {
        if (isset($tenantConfig['payment_gateway'])) {
            $config->set('payment.gateway', $tenantConfig['payment_gateway']);
        }
        if (isset($tenantConfig['payment_api_key'])) {
            $config->set('payment.api_key', $tenantConfig['payment_api_key']);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }
    
    public function handles(): array
    {
        return ['payment_gateway', 'payment_api_key'];
    }
    
    public function priority(): int
    {
        return 60;
    }
}

// 2. Register in service provider
public function boot(): void
{
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                PaymentConfigPipe::class
            );
        });
    }
}

// 3. Configure a tenant
$tenant->config->set('payment_gateway', 'stripe');
$tenant->config->set('payment_api_key', 'sk_test_...');
$tenant->config->setVisibility('payment_gateway', TenantConfigVisibility::PROTECTED);
$tenant->save();
```

## Troubleshooting

### Configuration Not Applied

1. Check pipe priority - higher priority runs first
2. Verify pipe is registered in service provider
3. Ensure configuration key exists in tenant config
4. Check for typos in configuration keys

### Inheritance Issues

1. Verify parent-child relationship is set
2. Check `getEffectiveConfig()` is being used
3. Ensure child config properly merges with parent

### Performance Concerns

1. Configuration is cached per request
2. Use appropriate tier for tenant needs
3. Avoid excessive configuration lookups in loops