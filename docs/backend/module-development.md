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

QuVel Kit modules can integrate with the multi-tenancy system using a simple config-driven approach. For full details, see the [Multi-Tenancy System](./tenant-module.md) documentation.

### Quick Integration

1. **Create a tenant config file** in your module at `config/tenant.php`:

```php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => [
                'your_module_enabled' => true,
                'your_module_api_url' => 'https://api.example.com',
            ],
            'visibility' => [
                'your_module_enabled' => 'public',
                'your_module_api_url' => 'protected',
            ],
            'priority' => 50,
        ],
    ],
    
    'tables' => [
        'your_models' => [
            'after' => 'id',
            'cascade_delete' => true,
        ],
    ],
];
```

2. **Make your models tenant-aware** using the `TenantScopedModel` trait:

```php
use Modules\Tenant\Traits\TenantScopedModel;

class YourModel extends Model
{
    use TenantScopedModel;
    // Automatically handles tenant scoping
}
```

3. **Access tenant context** in your services:

```php
use Modules\Tenant\Contexts\TenantContext;

class YourController
{
    public function __construct(private TenantContext $tenantContext)
    {
    }
    
    public function index()
    {
        $tenant = $this->tenantContext->get();
        $config = $tenant->config;
    }
}
```

For advanced topics including configuration pipes, template systems, inheritance, and complete examples, see the [Multi-Tenancy System documentation](./tenant-module.md).

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

- Register services as **scoped** when they need request context
- Use **singleton** for stateless services that can be shared across requests
- Follow dependency injection principles for testability

### 2. Configuration Management

- Keep module-specific configuration in your module's `config` directory
- Use environment variables for sensitive or deployment-specific settings
- Document all configuration options in your module

### 3. Testing

- Group your tests with PHPUnit's `#[Group]` attribute for easy execution
- Create comprehensive unit and feature tests for your module
- Use factories for consistent test data generation

### 4. Frontend Integration

Your module can register frontend services and components following the same patterns as backend modules. Configuration is automatically available to the frontend through the tenant system.


## Additional Resources

- [Laravel Modules Documentation](https://laravelmodules.com/docs/12/basic-usage/creating-a-module)
- [Multi-Tenancy System](./tenant-module.md)

---

[← Back to Backend Documentation](./README.md)
