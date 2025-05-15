# Tenant Configuration System

The QuVel Kit provides a powerful, dynamic tenant configuration system that allows each tenant to have its own configuration settings. This system enables you to override virtually any Laravel configuration value on a per-tenant basis, making it ideal for multi-tenant applications where different clients may require different settings.

## Overview

The tenant configuration system allows you to:

- Store tenant-specific configuration in the database
- Override Laravel's configuration values at runtime
- Inherit configuration from parent tenants
- Control visibility of configuration values
- Apply configuration dynamically when switching tenants

### Configuration Visibility

Configuration values have different visibility levels defined in the `TenantConfigVisibility` enum:

- `PUBLIC` - Exposed all the way to the browser window level with window.**TENANT_CONFIG**
- `PROTECTED` - Exposed down to the SSR server level, saved in src-ssr/services/TenantCache
- `PRIVATE` - Never exposed, for internal backend use only

## Key Components

### TenantConfig Value Object

The `TenantConfig` value object is the core of the configuration system. It encapsulates all configurable settings for a tenant, including:

- App settings (name, environment, debug mode, etc.)
- Database connection details
- Cache and session configuration
- Mail settings
- AWS credentials
- OAuth providers and credentials
- Redis settings
- Pusher/broadcasting configuration
- Internal QuVel-specific settings

### TenantConfigCast

The `TenantConfigCast` class handles the conversion between the database JSON representation and the `TenantConfig` value object. It implements Laravel's `CastsAttributes` interface to seamlessly integrate with Eloquent models.

### ConfigApplier

The `ConfigApplier` service is responsible for applying tenant-specific configuration at runtime. It maps the values from a `TenantConfig` object to Laravel's configuration system.

### TenantConfigFactory

The `TenantConfigFactory` provides a convenient way to create `TenantConfig` instances for testing and seeding purposes.

## Usage

### Accessing Tenant Configuration

You can access a tenant's configuration through the `config` property on the `Tenant` model:

```php
$tenant = Tenant::find(1);
$config = $tenant->config;

// Access specific configuration values
$appName = $config->appName;
$mailFromAddress = $config->mailFromAddress;
```

### Getting Effective Configuration

Tenants can inherit configuration from parent tenants. To get the effective configuration (including inherited values), use the `getEffectiveConfig()` method:

```php
$tenant = Tenant::find(1);
$effectiveConfig = $tenant->getEffectiveConfig();
```

### Applying Tenant Configuration

To apply a tenant's configuration at runtime, use the `TenantServiceProvider::applyTenantConfig()` method or the helper function `setTenant()`:

```php
// Using the service provider
TenantServiceProvider::applyTenantConfig($tenant);

// Using the helper function
setTenant($tenantId);
```

### Creating Tenant Configuration

You can create a new tenant configuration using the `TenantConfigFactory`:

```php
$config = TenantConfigFactory::create(
    'api.example.com',
    'example-internal',
    'Example Tenant',
    'production'
);

$tenant = Tenant::create([
    'name' => 'Example Tenant',
    'domain' => 'example.com',
    'config' => $config,
]);
```

## Configuration Inheritance

Tenants can inherit configuration from parent tenants. This is useful for creating a hierarchy of tenants with shared configuration. When a configuration value is not set for a tenant, it will fall back to the parent tenant's value.

```php
$parentTenant = Tenant::find(1);
$childTenant = Tenant::create([
    'name' => 'Child Tenant',
    'domain' => 'child.example.com',
    'parent_id' => $parentTenant->id,
]);

// Child tenant will inherit configuration from parent
$effectiveConfig = $childTenant->getEffectiveConfig();
```

## Configuration Structure

The tenant configuration includes the following sections:

### App Settings

- `appName`: The application name
- `appEnv`: The application environment (local, production, etc.)
- `appKey`: The application encryption key
- `appDebug`: Whether debug mode is enabled
- `appTimezone`: The application timezone
- `appUrl`: The application URL
- `frontendUrl`: The frontend URL
- `internalApiUrl`: The internal API URL

### Frontend

- `frontendUrl`: The frontend URL
- `capacitorScheme`: The Capacitor scheme
- `internalApiUrl`: The internal API URL for SSR to use

### Localization

- `appLocale`: The application locale
- `appFallbackLocale`: The fallback locale

### Logging

- `logChannel`: The default logging channel
- `logStack`: The stack driver
- `logDeprecationsChannel`: The deprecations channel
- `logLevel`: The log level

### Database

- `dbConnection`: The database connection
- `dbHost`: The database host
- `dbPort`: The database port
- `dbDatabase`: The database name
- `dbUsername`: The database username
- `dbPassword`: The database password

### Session & Cache

- `sessionDriver`: The session driver
- `sessionLifetime`: The session lifetime
- `sessionEncrypt`: Whether to encrypt sessions
- `sessionPath`: The session path
- `sessionDomain`: The session domain
- `cacheStore`: The cache store
- `cachePrefix`: The cache prefix

### Redis

- `redisClient`: The Redis client
- `redisHost`: The Redis host
- `redisPassword`: The Redis password
- `redisPort`: The Redis port

### Mail

- `mailMailer`: The mail mailer
- `mailScheme`: The mail scheme
- `mailHost`: The mail host
- `mailPort`: The mail port
- `mailUsername`: The mail username
- `mailPassword`: The mail password
- `mailFromAddress`: The from address
- `mailFromName`: The from name

### AWS

- `awsAccessKeyId`: The AWS access key ID
- `awsSecretAccessKey`: The AWS secret access key
- `awsDefaultRegion`: The AWS default region
- `awsBucket`: The AWS bucket
- `awsUsePathStyleEndpoint`: Whether to use path-style endpoints

### OAuth / Socialite

- `socialiteProviders`: The enabled OAuth providers
- `socialiteNonceTtl`: The nonce TTL
- `socialiteTokenTtl`: The token TTL
- `oauthCredentials`: The OAuth credentials

### Pusher

- `pusherAppId`: The Pusher app ID
- `pusherAppKey`: The Pusher app key
- `pusherAppSecret`: The Pusher app secret
- `pusherAppCluster`: The Pusher app cluster
- `pusherPort`: The Pusher port
- `pusherScheme`: The Pusher scheme
