# Tenant Configuration Providers

## Overview

The Tenant module allows other modules to dynamically add configuration to tenant API responses through Configuration Providers. This enables modules to expose their specific settings to the frontend without modifying the core tenant structure.

## How It Works

When a tenant resource is requested through the API, the system:

1. Loads the tenant's stored configuration
2. Applies all registered configuration providers
3. Merges the configurations respecting visibility rules
4. Returns the enhanced configuration in the response

## Creating a Configuration Provider

### 1. Implement the Interface

Create a class that implements `TenantConfigProviderInterface`:

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
                'your_module_setting' => config('your_module.setting'),
                'your_module_feature' => true,
            ],
            'visibility' => [
                // Define visibility for each config key
                'your_module_setting' => 'public',     // Available to browser
                'your_module_feature' => 'protected',  // Available to SSR only
            ],
        ];
    }

    public function priority(): int
    {
        return 50; // Higher priority runs first
    }
}
```

### 2. Register in Your Service Provider

In your module's service provider, register the configuration provider:

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

## Visibility Levels

Configuration values have three visibility levels:

- **`public`**: Available in browser via `window.__TENANT_CONFIG__`
- **`protected`**: Available in SSR server but not browser
- **`private`**: Backend only (not included in API responses)

## Example: Core Module Provider

The Core module provides frontend service configuration:

```php
class CoreTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config' => [
                'frontend_service_url' => config('frontend.url'),
                'frontend_internal_api_url' => config('frontend.internal_api_url'),
                'api_version' => config('app.api_version', 'v1'),
                'supported_locales' => config('app.supported_locales', ['en']),
            ],
            'visibility' => [
                'frontend_service_url' => 'protected',
                'frontend_internal_api_url' => 'protected',
                'api_version' => 'public',
                'supported_locales' => 'public',
            ],
        ];
    }

    public function priority(): int
    {
        return 100; // High priority as Core module
    }
}
```

## Best Practices

1. **Use Appropriate Visibility**: Only expose what's needed at each level
2. **Namespace Your Keys**: Prefix with module name to avoid conflicts
3. **Document Your Configuration**: Comment what each config value does
4. **Consider Performance**: Don't add expensive computations in providers
5. **Handle Missing Config**: Use defaults when config values might not exist

## Testing Your Provider

```php
// In your test
$tenant = Tenant::factory()->create();
$provider = new YourModuleTenantConfigProvider();

$config = $provider->getConfig($tenant);

$this->assertArrayHasKey('your_module_setting', $config['config']);
$this->assertEquals('public', $config['visibility']['your_module_setting']);
```

## Advanced Usage

### Conditional Configuration

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

### Dynamic Visibility

You can also set visibility dynamically:

```php
public function getConfig(Tenant $tenant): array
{
    $isProduction = $tenant->config->get('app_env') === 'production';
    
    return [
        'config' => [
            'debug_info' => $this->getDebugInfo(),
        ],
        'visibility' => [
            // Only expose debug info in non-production
            'debug_info' => $isProduction ? 'private' : 'public',
        ],
    ];
}
```

## Integration with Frontend

The configuration added by providers is automatically available in the frontend through the standard tenant configuration channels:

### In SSR (Server-Side Rendering)

```typescript
// Access in SSR context
const tenantConfig = req.tenantConfig;
const apiVersion = tenantConfig.api_version;
const supportedLocales = tenantConfig.supported_locales;
```

### In Browser (SPA)

```javascript
// Access public configuration
const config = window.__TENANT_CONFIG__;
const apiVersion = config.api_version;
const authProviders = config.auth_providers;
```

### In Vue Components

```vue
<script setup>
import { inject } from 'vue';
import { ConfigService } from 'src/modules/Core/services/ConfigService';

const configService = inject(ConfigService);
const apiVersion = configService.get('api_version');
</script>
```

## Troubleshooting

### Configuration Not Appearing

1. Verify your provider is registered in the service provider
2. Check the visibility setting (must be 'public' or 'protected')
3. Ensure the provider's `getConfig()` returns the correct structure
4. Check provider priority if overriding existing keys

### Performance Issues

1. Cache expensive computations in your provider
2. Consider using Laravel's config caching
3. Avoid database queries in providers when possible