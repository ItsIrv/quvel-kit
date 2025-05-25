# Module Development

## Overview

QuVel Kit includes Laravel Modules integration for organizing the backend codebase. This documentation covers how to work with the modular structure.

## Creating a New Module

To create a new module, use the Laravel Modules artisan command:

```bash
php artisan module:make ModuleName
```

For a simpler module structure without default resources:

```bash
php artisan module:make ModuleName --plain
```

## Module Structure

A typical module structure looks like this:

```text
Modules/ModuleName/
├── app/                  # Module application code
│   ├── Actions/          # Business logic actions
│   ├── Console/          # Console commands
│   ├── Contracts/        # Interfaces
│   ├── Events/           # Event classes
│   ├── Http/             # Controllers, middleware, requests
│   ├── Models/           # Eloquent models
│   ├── Providers/        # Service providers
│   │   ├── ModuleNameServiceProvider.php
│   │   └── RouteServiceProvider.php
│   └── Services/         # Service classes
├── config/               # Module configuration
├── database/             # Migrations, factories, seeders
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── lang/                 # Translations
├── resources/            # Views, assets
├── routes/               # Route definitions
│   ├── api.php           # API routes
│   ├── channels.php      # Broadcast channels
│   └── web.php           # Web routes
└── tests/                # Module tests
```

## Custom Service Providers

QuVel Kit extends the default Laravel Modules functionality with custom service providers. When creating a new module, you should use these providers instead of the default ones.

### Module Service Provider

Replace the default service provider with one that extends `Modules\Core\Providers\ModuleServiceProvider`:

```php
<?php

namespace Modules\YourModule\Providers;

use Modules\Core\Providers\ModuleServiceProvider;


class YourModuleServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'YourModule';
    
    protected string $nameLower = 'yourmodule';
    
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register your services
        // $this->app->singleton(YourService::class);
    }
}
```

### Route Service Provider

Create a route service provider that extends `Modules\Core\Providers\ModuleRouteServiceProvider`:

```php
<?php

namespace Modules\YourModule\Providers;

use Modules\Core\Providers\ModuleRouteServiceProvider;

class RouteServiceProvider extends ModuleRouteServiceProvider
{
    protected string $name = 'YourModule';
}
```

## Module Configuration

### Publishing Module Assets

To publish module configuration, views, and other assets:

```bash
php artisan module:publish YourModule
```

### Module-Specific Configuration

Create configuration files in the `config` directory of your module:

```php
// config/config.php
return [
    'name' => 'YourModule',
    'options' => [
        // Module-specific configuration
    ],
];
```

Access configuration values using:

```php
config('yourmodule.options');
```

## Multi-Tenancy Integration

QuVel Kit modules can integrate with the multi-tenancy system. The `Tenant` module provides services for tenant management:

### Using TenantContext

The `TenantContext` is a request-scoped service that holds the current tenant:

```php
use Modules\Tenant\Contexts\TenantContext;

class YourController
{
    public function __construct(private TenantContext $tenantContext)
    {
    }
    
    public function index()
    {
        $currentTenant = $this->tenantContext->get();
        
        // Use tenant information
        $tenantName = $currentTenant->name;
        $tenantConfig = $currentTenant->config;
    }
}
```

### Registering Configuration Pipes

Modules can register configuration pipes to process tenant-specific settings:

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
        
        if (isset($tenantConfig['your_module_api_key'])) {
            $config->set('your_module.api_key', $tenantConfig['your_module_api_key']);
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
            'your_module_enabled',
            'your_module_api_key',
            'your_module_settings',
        ];
    }

    public function priority(): int
    {
        return 50; // Higher priority runs first
    }
}
```

Register the pipe in your service provider:

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

### Registering Tenant Configuration Providers

To expose your module's configuration to the frontend, create a configuration provider:

```php
namespace Modules\YourModule\Providers;

use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;

class YourModuleTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        // Determine what configuration to expose based on tenant
        $features = $this->getEnabledFeatures($tenant);
        
        return [
            'config' => [
                'your_module_features' => $features,
                'your_module_api_url' => config('your_module.api_url'),
                'your_module_version' => config('your_module.version', '1.0'),
            ],
            'visibility' => [
                'your_module_features' => 'public',    // Available in browser
                'your_module_api_url' => 'protected',  // SSR only
                'your_module_version' => 'public',     // Available in browser
            ],
        ];
    }

    public function priority(): int
    {
        return 50;
    }
    
    private function getEnabledFeatures(Tenant $tenant): array
    {
        $tier = $tenant->config->get('tier', 'basic');
        
        // Different features based on tenant tier
        return match($tier) {
            'premium', 'enterprise' => ['basic', 'advanced', 'premium'],
            'standard' => ['basic', 'advanced'],
            default => ['basic'],
        };
    }
}
```

Register the provider in your service provider:

```php
public function boot(): void
{
    parent::boot();

    // Register tenant config provider
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                \Modules\YourModule\Providers\YourModuleTenantConfigProvider::class
            );
        });
    }
}
```

### Creating Tenant-Aware Models

Make your models tenant-aware by using the `TenantScopedModel` trait:

```php
namespace Modules\YourModule\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Traits\TenantScopedModel;

class YourModel extends Model
{
    use TenantScopedModel;
    
    protected $fillable = [
        'name',
        'description',
        // tenant_id is automatically handled
    ];
}
```

This automatically:
- Filters queries by current tenant
- Sets tenant_id when creating records
- Prevents cross-tenant operations
- Configures tenant-aware broadcast channels

## Authentication Integration

The `Auth` module provides authentication services that can be used in your modules:

```php
use Modules\Auth\Services\UserAuthenticationService;

class YourController
{
    public function __construct(private UserAuthenticationService $authService)
    {
    }
    
    public function protectedAction()
    {
        $user = $this->authService->getCurrentUser();
        
        // Use authenticated user
    }
}
```

## Module Testing

Laravel Modules provides commands for creating module-specific tests:

```bash
php artisan module:make-test YourTest YourModule
```

You can run tests for specific modules using PHPUnit filters:

```bash
php artisan test --filter=YourModuleTest
```

All modules provided by Quvel also use the Group attribute to group their tests. You can run tests for specific modules using PHPUnit filters:

```bash
php artisan test --group=auth-module
php artisan test --group=auth-actions

php artisan test --group=tenant-module --testsuite=Unit
php artisan test --group=tenant-module --testsuite=Feature
```

## Best Practices

### 1. Service Registration

- Register services as **scoped** when they need tenant context
- Use **singleton** only for stateless services
- Always check if the Tenant module exists before registering pipes/providers

### 2. Configuration Management

- Use configuration pipes for runtime Laravel config overrides
- Use configuration providers to expose settings to frontend
- Follow visibility guidelines (PUBLIC, PROTECTED, PRIVATE)

### 3. Testing

- Use `Modules\Tenant\Tests\TestCase` for tenant-aware tests
- Group your tests with PHPUnit's `#[Group]` attribute
- Test with multiple tenant configurations

### 4. Frontend Integration

Your module's configuration will be available in the frontend:

```javascript
// In Vue components (PUBLIC visibility only)
const features = window.__TENANT_CONFIG__.your_module_features;

// In SSR context (PUBLIC and PROTECTED)
const apiUrl = req.tenantConfig.your_module_api_url;
```

## Example: Complete Module Integration

Here's a complete example of a module with tenant integration:

```php
// Modules/Billing/Providers/BillingServiceProvider.php
namespace Modules\Billing\Providers;

use Modules\Core\Providers\ModuleServiceProvider;

class BillingServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Billing';
    protected string $nameLower = 'billing';
    
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        
        // Register scoped services
        $this->app->scoped(BillingService::class);
        $this->app->scoped(InvoiceService::class);
    }
    
    public function boot(): void
    {
        parent::boot();
        
        if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
            $this->app->booted(function () {
                // Register configuration pipe
                \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                    \Modules\Billing\Pipes\BillingConfigPipe::class
                );
                
                // Register configuration provider
                \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                    \Modules\Billing\Providers\BillingTenantConfigProvider::class
                );
            });
        }
    }
}
```

## Additional Resources

- [Laravel Modules Documentation](https://laravelmodules.com/docs/12/basic-usage/creating-a-module)
- [Tenant Module Documentation](./tenant-module.md)
- [Dynamic Configuration Guide](./dynamic-configuration.md)
- [Configuration Providers Guide](./tenant-config-providers.md)
- [Tenant Configuration Examples](./tenant-configuration-examples.md)

---

[← Back to Backend Documentation](./README.md)
