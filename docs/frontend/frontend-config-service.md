# Configuration Service

## Overview

QuVel Kit provides a robust **Configuration Service** that manages application settings across different environments with a tiered visibility system. This service handles the secure distribution of configuration values between server-side rendering (SSR) and client-side contexts, ensuring sensitive data is properly protected.

With the pipeline-based tenant configuration system, modules register configuration pipes that resolve frontend-safe values with explicit visibility controls.

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

> **Note**: The visibility system works with dynamic configuration. Backend processes configuration and provides filtered values to the frontend based on visibility controls.

### How Visibility Works

1. Backend processes tenant configuration and applies visibility controls
2. Configuration values are filtered based on environment (SSR vs browser)
3. Only `public` values are injected into the browser's `window.__TENANT_CONFIG__`
4. `protected` values are available in SSR context only
5. `private` values remain backend-only

```ts
// Example configuration available to frontend
{
  "apiUrl": "https://api.example.com",
  "appUrl": "https://app.example.com", 
  "appName": "Example App",
  "tenantId": "1",
  "tenantName": "Example Tenant",
  "pusherAppKey": "app-key-123",
  "pusherAppCluster": "us2",
  "socialiteProviders": ["google", "github"]
}
```

In this example:

- All values have `public` visibility and are available in the browser
- Values are provided by the backend's configuration system
- The frontend ConfigService accesses these values through standard methods

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
3. Backend processes and applies tenant configuration
4. The full configuration (including `PROTECTED` values) is attached to the request
5. The `filterTenantConfig` function removes non-public values
6. Only `PUBLIC` values are injected into `window.__TENANT_CONFIG__`
7. The ConfigService in SSR context receives the full configuration
8. The client hydrates with only the `PUBLIC` values

### Dynamic Configuration Sources

Configuration values come from multiple sources:

1. **Tenant Database Configuration** - Stored in `DynamicTenantConfig`
2. **Backend Processing** - Tenant-specific configuration processing and value resolution
3. **Environment Variables** - Fallback values

### Configuration Priority

The ConfigService uses the following priority order when loading configuration:

1. SSR-provided configuration (highest priority)
2. Browser-injected configuration (`window.__TENANT_CONFIG__`)
3. Environment variables (lowest priority)

---

## Tenant Configuration

### Dynamic Configuration

With the dynamic configuration system, tenant configuration is no longer limited to a fixed structure. The backend provides flexible configuration that can include any key-value pairs:

```ts
// Example configuration interface
export interface TenantConfig {
  apiUrl: string;                    // API endpoint URL
  appUrl: string;                    // Frontend application URL  
  appName: string;                   // Application name
  tenantId: string;                  // Current tenant ID
  tenantName: string;                // Current tenant name
  pusherAppKey?: string;             // Pusher app key for WebSockets
  pusherAppCluster?: string;         // Pusher app cluster
  socialiteProviders?: string[];     // Available OAuth providers
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
- **[ConfigurationPipeInterface.php](../../backend/Modules/Tenant/app/Contracts/ConfigurationPipeInterface.php)** – Pipe interface
- **[CoreConfigPipe.php](../../backend/Modules/Tenant/app/Pipes/CoreConfigPipe.php)** – Core configuration pipe
- **[AuthConfigPipe.php](../../backend/Modules/Auth/app/Pipes/AuthConfigPipe.php)** – Auth configuration pipe
- **[ConfigurationPipeline.php](../../backend/Modules/Tenant/app/Services/ConfigurationPipeline.php)** – Pipeline service

---

[← Back to Frontend Docs](./README.md)
