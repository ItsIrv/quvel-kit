# Tenant Module

## Overview

The Tenant module provides multi-tenancy capabilities for QuVel Kit, allowing a single application instance to serve multiple isolated tenants. This module implements domain-based tenant resolution, tenant context management, and tenant-specific configuration.

## Key Components

- [Tenant Configuration](./tenant-configuration.md) - Tenant configuration details

### TenantServiceProvider

The `TenantServiceProvider` bootstraps the Tenant module by registering services, middleware, and configuration.

- Registers tenant-specific configuration
- Binds the tenant middleware to the router
- Loads tenant-specific helpers

### TenantContext

The `TenantContext` class is a request-scoped service that holds the current tenant information. This context is scoped to the current request, ensuring that each request has its own isolated tenant context.

### TenantMiddleware

The `TenantMiddleware` resolves the tenant for each request based on the domain. This middleware is automatically applied to routes that require tenant context.

### HostResolver

The `HostResolver` handles the logic for resolving a tenant from a request.

Resolution process:

1. Checks if a tenant is already in the session
2. If not, looks up the tenant by domain
3. Stores the resolved tenant in the session for future requests
4. Throws an exception if no tenant is found

### Helper Functions

The Tenant module provides global helper functions for easy access to tenant information.

**These should not be used in the HTTP layer, let the middleware handle it**:

```php
/**
 * Set the current tenant by ID.
 */
function setTenant(int $tenantId): void
{
    app(TenantContext::class)->set(
        app(FindService::class)->findById($tenantId)
        ?? throw new TenantNotFoundException('Tenant not found'),
    );
}

/**
 * Get the current tenant.
 */
function getTenant(): Tenant
{
    return app(TenantContext::class)->get();
}
```

These helpers simplify tenant operations throughout the application.

## Multi-Tenancy Implementation

### Domain-Based Routing

QuVel Kit uses domain-based routing to identify tenants. Each tenant is associated with a unique domain:

```text
tenant1.example.com -> Tenant 1
tenant2.example.com -> Tenant 2
```

The `TenantMiddleware` automatically resolves the tenant based on the request domain.

### Tenant Database Structure

The Tenant module uses the following database structure based on the actual migrations:

```php
Schema::create('tenants', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('tenants')->cascadeOnDelete();
    $table->char('public_id', 26)->unique();
    $table->string('name')->unique();
    $table->string('domain')->unique();
    $table->json('config')->nullable();
    $table->timestamps();
});
```

Additionally, the Tenant module adds tenant_id to other tables through the main `config.php` file.

```php
return [
    'name' => 'Tenant',
    'tables' => [
        'users' => [
            'after' => 'id',
            'cascadeDelete' => true,
            'dropUnique' => [
                'email',
                'provider_id',
            ],
            'compoundUnique' => [
                'email',
                'provider_id',
            ],
        ],
        'catalog_items' => [
            'after' => 'id',
            'cascadeDelete' => true,
        ],
    ],
];
```

## Using the Tenant Module

### Protecting Routes with Tenant Middleware

Apply the tenant middleware globally in the `TenantServiceProvider`.

### Accessing Tenant Information

Access the current tenant in your controllers:

```php
public function index()
{
    $tenant = getTenant();
    
    return response()->json([
        'tenant' => $tenant->name,
        'domain' => $tenant->domain,
    ]);
}
```

### Tenant-Aware Models

The Tenant module provides a `TenantScopedModel` trait that automatically scopes models to the current tenant:

The `TenantScope` class automatically filters queries by the current tenant:

### Using Tenant-Aware Models

To make a model tenant-aware, simply add the `TenantScopedModel` trait:

```php
use Modules\Tenant\Traits\TenantScopedModel;

class Resource extends Model
{
    use TenantScopedModel;
    
    // Rest of your model...
}
```

This automatically:

- Adds the tenant_id when creating new records
- Filters queries to only include records for the current tenant
- Prevents cross-tenant operations (saves, updates, deletes)

## Tenant Management

### Creating a Tenant

```php
$tenant = Tenant::create([
    'name' => 'Example Tenant',
    'domain' => 'example.quvel.127.0.0.1.nip.io',
    'status' => 'active',
]);

// Add tenant configuration
$tenant->configs()->create([
    'key' => 'theme',
    'value' => 'dark',
    'visibility' => 'public',
]);
```

## Security Considerations

The Tenant module implements several security measures:

1. **Tenant Isolation**: Ensures data is isolated between tenants
2. **Domain Validation**: Validates domains to prevent tenant spoofing
3. **Configuration Visibility**: Controls access to sensitive configuration
4. **Error Handling**: Provides secure error responses for tenant resolution failures

---

[← Back to Backend Documentation](./README.md)
