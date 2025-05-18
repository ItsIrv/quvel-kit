# Core Module

## Overview

The Core module provides essential functionality and utilities that serve as the foundation for other modules in QuVel Kit. It implements cross-cutting concerns, middleware, service providers, and common services that can be used throughout the application.

## Key Components

### Service Providers

The Core module includes several service providers that bootstrap core functionality:

1. **CoreServiceProvider**: Registers core services and dependencies
2. **ModuleServiceProvider**: Base service provider that other module providers extend
3. **ModuleRouteServiceProvider**: Base route service provider for module routing

### Middleware

The Core module provides middleware for common concerns:

#### Config Middleware

- **CheckValue**: Validates configuration values and redirects or returns error responses when conditions aren't met

### Services

The Core module includes several utility services:

#### FrontendService

Handles communication with the frontend, including redirects and response formatting.

#### Other Services

- Logging services
- Configuration services
- Utility services

### Traits

The Core module provides reusable traits:

- Common model traits
- Service traits
- Controller traits

## Using the Core Module

### Extending Service Providers

When creating new modules, extend the base service providers:

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
    }
}
```

### Using Core Middleware

Apply Core middleware in your routes:

```php
// In your route file
Route::middleware([
    \Modules\Core\Http\Middleware\Config\CheckValue::class . ':app.debug,true'
])->group(function () {
    // Routes that require debug mode to be enabled
});
```

### Using Core Services

Inject Core services into your controllers and services:

```php
use Modules\Core\Services\FrontendService;

class YourController
{
    public function __construct(private FrontendService $frontendService)
    {
    }
    
    public function redirectToFrontend()
    {
        return $this->frontendService->redirect('dashboard', [
            'message' => 'Redirected successfully'
        ]);
    }
}
```

## Testing

The Core module includes comprehensive tests:

```bash
# Run Core module tests
php artisan test --group=core-module
```

---

[← Back to Backend Documentation](./README.md)
