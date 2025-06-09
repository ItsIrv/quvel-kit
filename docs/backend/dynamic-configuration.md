# Dynamic Tenant Configuration System

## Overview

QuVel Kit's tenant configuration system provides a flexible, pipeline-based approach to managing tenant-specific settings. The system addresses key requirements:

- **Dynamic Configuration**: No hard-coded configuration properties - add any key-value pairs as needed
- **Module Extensibility**: Modules can register configuration pipes to process and expose settings
- **Security Controls**: Three-tier visibility system (public, protected, private) for configuration exposure
- **Configuration Inheritance**: Child tenants automatically inherit parent configurations
- **Partial Overrides**: Tenants only need to override what they actually use

## Architecture

### Configuration Pipeline

The system uses a pipeline pattern where configuration flows through registered processors:

```
Tenant → ConfigurationPipeline → [CorePipe, AuthPipe, YourPipe, ...] → Laravel Config + Frontend
```

Each pipe handles specific configuration domains and can be registered by any module.

### Core Components

| Component | Description |
|-----------|-------------|
| **DynamicTenantConfig** | Flexible value object that stores configuration as key-value pairs |
| **ConfigurationPipeline** | Manages and executes configuration pipes |
| **ConfigurationPipeInterface** | Contract for creating configuration pipes |
| **DynamicTenantConfigCast** | Handles database serialization with backward compatibility |

### Configuration Flow

Configuration pipes implement two key methods:

1. **`handle()`**: Applies configuration to Laravel's config repository for backend use
2. **`resolve()`**: Returns configuration values with visibility controls for frontend exposure

## Creating Configuration Pipes

### 1. Implement the Interface

Create a class that implements `ConfigurationPipeInterface`:

```php
namespace Modules\YourModule\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

class YourModuleConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Resolve configuration for frontend exposure.
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $values = [];
        $visibility = [];
        
        // Only add values you want exposed to frontend
        if (isset($tenantConfig['your_module_setting'])) {
            $values['yourModuleSetting'] = $tenantConfig['your_module_setting'];
            $visibility['yourModuleSetting'] = 'public';  // or 'protected'
        }
        
        return ['values' => $values, 'visibility' => $visibility];
    }

    /**
     * Apply configuration to Laravel config.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply to Laravel config for backend use
        if (isset($tenantConfig['your_module_api_key'])) {
            $config->set('your_module.api_key', $tenantConfig['your_module_api_key']);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return ['your_module_setting', 'your_module_api_key'];
    }

    public function priority(): int
    {
        return 50; // Higher priority runs first
    }
}
```

### 2. Register Pipe and Configuration Seeders

In your module's service provider, register both the pipe and default configuration values:

```php
public function boot(): void
{
    parent::boot();

    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            // Register the configuration pipe
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                \Modules\YourModule\Pipes\YourModuleConfigPipe::class
            );
            
            // Register default configuration for all tenants
            $this->registerYourModuleConfigSeeders();
        });
    }
}

private function registerYourModuleConfigSeeders(): void
{
    \Modules\Tenant\Providers\TenantServiceProvider::registerConfigSeederForAllTiers(
        function (string $tier, array $config) {
            return [
                'your_module_setting' => 'default_value',
                'your_module_enabled' => true,
            ];
        },
        50, // Priority
        function (string $tier, array $visibility) {
            return [
                'your_module_setting' => 'protected',
                'your_module_enabled' => 'public',
            ];
        }
    );
}
```

## Configuration Visibility

Configuration values have three visibility levels:

| Level | Description | Access |
|-------|-------------|--------|
| **`public`** | Available in browser via `window.__TENANT_CONFIG__` | Backend + SSR + Browser |
| **`protected`** | Available in SSR server but not browser | Backend + SSR |
| **`private`** | Backend only (not included in API responses) | Backend only |

## Working with Dynamic Configuration

### Setting Tenant Configuration

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

// Create configuration
$config = new DynamicTenantConfig([
    'app_name' => 'My App',
    'mail_from_address' => 'support@myapp.com',
    'mail_from_name' => 'My App Support',
], [
    'app_name' => TenantConfigVisibility::PUBLIC,
]);

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

## Configuration Seeders

Configuration seeders allow modules to provide default values for all tenants:

```php
// Callback receives current tier and existing config
TenantServiceProvider::registerConfigSeederForAllTiers(
    function (string $tier, array $config) {
        // Return configuration values for this module
        return [
            'module_setting' => 'default_value',
            'module_api_url' => env('MODULE_API_URL', 'https://api.example.com'),
        ];
    },
    50, // Priority (higher runs first)
    function (string $tier, array $visibility) {
        // Return visibility settings
        return [
            'module_setting' => TenantConfigVisibility::PUBLIC,
            'module_api_url' => TenantConfigVisibility::PROTECTED,
        ];
    }
);
```

## Real Example: Auth Module

The Auth module demonstrates both pipe registration and configuration seeders:

**AuthConfigPipe.php** - Handles OAuth provider configuration:
```php
public function resolve(Tenant $tenant, array $tenantConfig): array
{
    $values = [];
    $visibility = [];

    if ($this->hasValue($tenantConfig, 'socialite_providers')) {
        $values['socialiteProviders'] = $tenantConfig['socialite_providers'];
        $visibility['socialiteProviders'] = 'public';
    }

    return ['values' => $values, 'visibility' => $visibility];
}
```

**AuthServiceProvider.php** - Registers defaults for all tenants:
```php
private function registerAuthConfigSeeders(): void
{
    TenantServiceProvider::registerConfigSeederForAllTiers(
        function (string $tier, array $config) {
            $authConfig = [
                'session_cookie' => 'quvel_session',
                'socialite_providers' => ['google'],
                'oauth_credentials' => [
                    'google' => [
                        'client_id' => env('GOOGLE_CLIENT_ID'),
                        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                    ],
                ],
            ];

            // Higher tiers get more providers
            if (in_array($tier, ['premium', 'enterprise'])) {
                $authConfig['socialite_providers'][] = 'microsoft';
            }

            return $authConfig;
        },
        20, // High priority
        function (string $tier, array $visibility) {
            return [
                'session_cookie' => TenantConfigVisibility::PROTECTED,
                'socialite_providers' => TenantConfigVisibility::PUBLIC,
            ];
        }
    );
}
```

## Migration Guide

### From TenantConfig to DynamicTenantConfig

The system maintains backward compatibility during migration:

1. **Existing TenantConfig** objects are automatically converted to DynamicTenantConfig
2. **Property access** works the same: `$config->appName` or `$config->get('app_name')`
3. **No immediate changes required** to existing code

## Best Practices

1. **Use Appropriate Visibility**: Only expose what's needed at each level
2. **Namespace Your Keys**: Prefix with module name to avoid conflicts  
3. **Register Seeders**: Provide sensible defaults for all tenants
4. **Handle Missing Config**: Use defaults when config values might not exist
5. **Module Isolation**: Keep module configurations in separate pipes
6. **Test Inheritance**: Ensure child tenants properly inherit parent configurations

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
2. Avoid excessive configuration lookups in loops
3. Use appropriate pipe priorities

---

[← Back to Backend Documentation](./README.md)