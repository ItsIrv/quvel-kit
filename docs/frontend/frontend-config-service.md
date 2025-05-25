# Configuration Service

## Overview

QuVel Kit provides a robust **Configuration Service** that manages application settings across different environments with a tiered visibility system. This service handles the secure distribution of configuration values between server-side rendering (SSR) and client-side contexts, ensuring sensitive data is properly protected.

With the new dynamic tenant configuration system, modules can now dynamically contribute configuration values through backend providers, eliminating the need for hard-coded configuration structures.

## Features

- **Tiered Visibility System** – Controls which configuration values are exposed to different environments
- **Multi-Environment Support** – Works seamlessly in SSR, browser, and development environments
- **Tenant-Aware Configuration** – Supports multi-tenant applications with tenant-specific settings
- **Type Safety** – Fully typed configuration values with TypeScript
- **Service Container Integration** – Available through the container as `container.config`

---

## Configuration Visibility System

QuVel Kit implements a security-focused visibility system for configuration values:

| Visibility Level | Description | Access |
|------------------|-------------|--------|
| `PRIVATE` (default) | Not exposed outside backend | Backend only |
| `PROTECTED` | Available in SSR context | Backend + SSR |
| `PUBLIC` | Available in browser context | Backend + SSR + Browser |

> **Note**: The visibility system now works with dynamic configuration. Backend modules can register configuration providers that specify visibility for each configuration key they contribute.

### How Visibility Works

1. Backend modules register configuration providers that specify visibility for their config keys
2. Configuration values are stored dynamically in the database using `DynamicTenantConfig`
3. When the API returns tenant data, it merges configuration from all registered providers
4. The `__visibility` property maps each configuration key to its visibility level
5. During SSR, the middleware filters configuration values based on visibility
6. Only `PUBLIC` values are injected into the browser's `window.__TENANT_CONFIG__`

```ts
// Example configuration with visibility
{
  "apiUrl": "https://api.example.com",
  "appUrl": "https://app.example.com",
  "appName": "Example App",
  "tenantId": "1",
  "tenantName": "Example Tenant",
  "pusherAppKey": "app-key-123",
  "pusherAppCluster": "us2",
  "socialiteProviders": ["google", "github"],
  "sessionCookie": "quvel_session",
  "recaptchaGoogleSiteKey": "recaptcha-key-123",
  "__visibility": {
    "apiUrl": "public",
    "appUrl": "public",
    "appName": "public",
    "tenantId": "public",
    "tenantName": "public",
    "pusherAppKey": "public",
    "pusherAppCluster": "public",
    "socialiteProviders": "public",
    "sessionCookie": "protected",
    "recaptchaGoogleSiteKey": "public"
  }
}
```

In this example:

- `api_url` and `tenant_name` are available in both SSR and browser contexts
- `api_key` is only available in SSR context
- Any property without a visibility setting is considered private

---

## Using the Configuration Service

### In Vue Components

Access the configuration service through the service container:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { config } = useContainer();

// Get a specific config value
const apiUrl = config.get('apiUrl');

// Get all available config values
const allConfig = config.getAll();
```

### In Pinia Stores

The configuration service is available in Pinia stores via the container:

```ts
import { defineStore } from 'pinia';

export const useAppStore = defineStore('app', {
  state: () => ({
    appName: '',
  }),
  actions: {
    initialize() {
      // Access config through the container
      this.appName = this.$container.config.get('appName');
    }
  }
});
```

---

## Configuration Flow

### SSR Request Flow

1. The SSR middleware receives a request with the hostname
2. The `TenantCacheService` retrieves the tenant configuration based on the hostname
3. Backend applies all registered configuration providers and pipes
4. The full configuration (including `PROTECTED` values) is attached to the request
5. The `filterTenantConfig` function removes non-public values
6. Only `PUBLIC` values are injected into `window.__TENANT_CONFIG__`
7. The ConfigService in SSR context receives the full configuration
8. The client hydrates with only the `PUBLIC` values

### Dynamic Configuration Sources

Configuration values come from multiple sources:

1. **Tenant Database Configuration** - Stored in `DynamicTenantConfig`
2. **Configuration Pipes** - Apply tenant-specific Laravel config overrides
3. **Configuration Providers** - Modules contribute additional config values
4. **Environment Variables** - Fallback values

### Configuration Priority

The ConfigService uses the following priority order when loading configuration:

1. SSR-provided configuration (highest priority)
2. Browser-injected configuration (`window.__TENANT_CONFIG__`)
3. Environment variables (lowest priority)

---

## Tenant Configuration

### Dynamic Configuration

With the dynamic configuration system, tenant configuration is no longer limited to a fixed structure. Configuration keys can be added by any module through configuration providers:

```ts
// Core configuration provided by CoreTenantConfigProvider
export interface CoreTenantConfig {
  apiUrl: string;                    // API endpoint URL
  appUrl: string;                    // Frontend application URL  
  appName: string;                   // Application name
  tenantId: string;                  // Current tenant ID
  tenantName: string;                // Current tenant name
  api_version: string;               // API version
  supported_locales: string[];       // Supported locales
  default_locale: string;            // Default locale
}

// Auth configuration provided by AuthTenantConfigProvider
export interface AuthTenantConfig {
  auth_providers: string[];          // Available OAuth providers
  auth_verify_email: boolean;        // Email verification requirement
  pusherAppKey: string;              // Pusher app key for WebSockets
  pusherAppCluster: string;          // Pusher app cluster
  sessionCookie: string;             // Session cookie name
  recaptchaGoogleSiteKey: string;    // Google reCAPTCHA site key
}

// Your module can add its own configuration
export interface YourModuleTenantConfig {
  your_module_features: string[];    // Module-specific features
  your_module_api_url: string;       // Module API endpoint
}
```

### Backend Configuration Providers

Modules register configuration providers to contribute to tenant configuration:

```php
// Example from CoreTenantConfigProvider
class CoreTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config' => [
                'api_version' => config('app.api_version', 'v1'),
                'supported_locales' => config('app.supported_locales', ['en']),
                // ... more configuration
            ],
            'visibility' => [
                'api_version' => 'public',
                'supported_locales' => 'public',
                // ... visibility settings
            ],
        ];
    }
}
```

### SSR Configuration

The SSR context uses an extended configuration type that includes the visibility property:

```ts
export interface TenantConfigProtected extends TenantConfig {
  internalApiUrl?: string;
  __visibility: TenantConfigVisibilityRecords;
}

export type TenantConfigVisibility = 'public' | 'protected';
```

---

## Working with Dynamic Configuration

### Accessing Configuration in Frontend

The ConfigService handles all configuration access:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { config } = useContainer();

// Get any configuration value
const apiVersion = config.get('api_version');
const authProviders = config.get('auth_providers');
const customSetting = config.get('your_module_setting');

// Check if a configuration exists
if (config.has('premium_feature')) {
  // Enable premium feature
}
```

### Type Safety with Dynamic Configuration

While the configuration is dynamic, you can still maintain type safety:

```ts
// Define your module's expected configuration
interface MyModuleConfig {
  my_module_api_key?: string;
  my_module_features?: string[];
}

// Create a typed getter
function getMyModuleConfig(config: ConfigService): MyModuleConfig {
  return {
    my_module_api_key: config.get('my_module_api_key'),
    my_module_features: config.get('my_module_features') || [],
  };
}
```

## Source Files

### Frontend
- **[ConfigService.ts](../../frontend/src/modules/Core/services/ConfigService.ts)** – Core configuration service
- **[tenant.types.ts](../../frontend/src/modules/Core/types/tenant.types.ts)** – Configuration type definitions
- **[render.ts](../../frontend/src-ssr/middlewares/render.ts)** – SSR middleware for configuration filtering
- **[TenantCache.ts](../../frontend/src-ssr/services/TenantCache.ts)** – Tenant configuration cache service

### Backend
- **[DynamicTenantConfig.php](../../backend/Modules/Tenant/app/ValueObjects/DynamicTenantConfig.php)** – Dynamic configuration value object
- **[TenantConfigProviderInterface.php](../../backend/Modules/Tenant/app/Contracts/TenantConfigProviderInterface.php)** – Provider interface
- **[CoreTenantConfigProvider.php](../../backend/Modules/Core/app/Providers/CoreTenantConfigProvider.php)** – Core configuration provider
- **[AuthTenantConfigProvider.php](../../backend/Modules/Auth/app/Providers/AuthTenantConfigProvider.php)** – Auth configuration provider

---

[← Back to Frontend Docs](./README.md)
