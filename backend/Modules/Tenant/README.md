# QuVel Tenant Module

Multi-tenancy module for QuVel Kit - Provides domain-based tenant resolution and configuration management.

## Installation

You can install the package via composer:

```bash
composer require itsirv/quvel-tenant
```

## Dependencies

This module requires:
- `itsirv/quvel-core` - Core module for base functionality

## Usage

This module is automatically registered by Laravel Modules. It provides:

### Features
- **Domain-based tenant resolution** - Automatically resolves tenants based on request domain
- **Dynamic configuration management** - Per-tenant configuration with inheritance
- **Configuration pipes** - Modular configuration system for database, cache, mail, etc.
- **Global scopes** - Automatic tenant isolation for Eloquent models
- **Helper functions** - Convenient tenant utilities

### Models
- `Tenant` - Main tenant model
- `TenantConfig` - Configuration management

### Services
- `HostResolver` - Domain-based tenant resolution
- `TenantResolver` - Main tenant resolution service

### Middleware
- `TenantMiddleware` - Applies tenant context to requests

### Configuration Pipes
- `DatabaseConfigPipe` - Database configuration per tenant
- `CacheConfigPipe` - Cache configuration per tenant
- `MailConfigPipe` - Mail configuration per tenant
- `SessionConfigPipe` - Session configuration per tenant

### Traits
- `TenantScopedModel` - Automatic tenant scoping for models

### Helpers
- `tenant()` - Get current tenant
- `tenant_config()` - Get tenant configuration

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Modules\Tenant\Providers\TenantServiceProvider" --tag="config"
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.