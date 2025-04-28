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

Replace the default service provider with one that extends `App\Providers\ModuleServiceProvider`:

```php
<?php

namespace Modules\YourModule\Providers;

use App\Providers\ModuleServiceProvider;

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

Create a route service provider that extends `App\Providers\ModuleRouteServiceProvider`:

```php
<?php

namespace Modules\YourModule\Providers;

use App\Providers\ModuleRouteServiceProvider;

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

### Using TenantSessionService

The `TenantSessionService` manages tenant information in the session:

```php
use Modules\Tenant\Services\TenantSessionService;

class YourController
{
    public function __construct(private TenantSessionService $tenantSession)
    {
    }
    
    public function index()
    {
        $currentTenant = $this->tenantSession->getTenant();
        
        // Use tenant information
    }
}
```

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

## Additional Resources

For more information on Laravel Modules, refer to the [official documentation](https://laravelmodules.com/docs/12/basic-usage/creating-a-module).

---

[← Back to Backend Documentation](./README.md)
