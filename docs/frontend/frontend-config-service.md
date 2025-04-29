# Configuration Service

## Overview

QuVel Kit provides a robust **Configuration Service** that manages application settings across different environments with a tiered visibility system. This service handles the secure distribution of configuration values between server-side rendering (SSR) and client-side contexts, ensuring sensitive data is properly protected.

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

### How Visibility Works

1. Configuration values are stored in the database with a special `__visibility` property
2. The `__visibility` property maps each configuration key to its visibility level
3. During SSR, the middleware filters configuration values based on visibility
4. Only `PUBLIC` values are injected into the browser's `window.__TENANT_CONFIG__`

```ts
// Example configuration with visibility
{
  "api_url": "https://api.example.com",
  "api_key": "secret-key-123",
  "tenant_name": "Example Tenant",
  "__visibility": {
    "api_url": "public",
    "api_key": "protected",
    "tenant_name": "public"
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
const apiUrl = config.get('api_url');

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
3. The full configuration (including `PROTECTED` values) is attached to the request
4. The `filterTenantConfig` function removes non-public values
5. Only `PUBLIC` values are injected into `window.__TENANT_CONFIG__`
6. The ConfigService in SSR context receives the full configuration
7. The client hydrates with only the `PUBLIC` values

### Configuration Priority

The ConfigService uses the following priority order when loading configuration:

1. SSR-provided configuration (highest priority)
2. Browser-injected configuration (`window.__TENANT_CONFIG__`)
3. Environment variables (lowest priority)

```ts
// From ConfigService.ts
constructor(ssrConfig?: TenantConfig) {
  // Check if running in browser and `window.__TENANT_CONFIG__` is available
  const clientConfig =
    typeof window !== 'undefined' && window.__TENANT_CONFIG__ ? window.__TENANT_CONFIG__ : null;

  // Prefer SSR config > Client Hydrated Config > Environment Variables
  this.config = ssrConfig ??
    clientConfig ?? {
      api_url: import.meta.env.VITE_API_URL ?? '',
      app_url: import.meta.env.VITE_APP_URL ?? '',
      app_name: import.meta.env.VITE_APP_NAME ?? 'QuVel',
      tenant_id: import.meta.env.VITE_TENANT_ID ?? '',
      tenant_name: import.meta.env.VITE_TENANT_NAME ?? '',
    };
}
```

---

## Tenant Configuration

### Structure

The tenant configuration includes essential application settings:

```ts
export interface TenantConfig {
  api_url: string;         // API endpoint URL
  app_url: string;         // Frontend application URL
  app_name: string;        // Application name
  internal_api_url?: string; // Internal API URL (for SSR)
  tenant_id: string;       // Current tenant ID
  tenant_name: string;     // Current tenant name
}
```

### SSR Configuration

The SSR context uses an extended configuration type that includes the visibility property:

```ts
export interface TenantConfigProtected {
  api_url: string;
  app_url: string;
  app_name: string;
  internal_api_url?: string;
  __visibility?: Partial<Record<keyof TenantConfigProtected, TenantConfigVisibility>>;
  tenant_id: string;
  tenant_name: string;
}

export type TenantConfigVisibility = 'public' | 'protected';
```

---

## Source Files

- **[ConfigService.ts](../../frontend/src/modules/Core/services/ConfigService.ts)** – Core configuration service
- **[tenant.types.ts](../../frontend/src/modules/Core/types/tenant.types.ts)** – Configuration type definitions
- **[render.ts](../../frontend/src-ssr/middlewares/render.ts)** – SSR middleware for configuration filtering
- **[TenantCache.ts](../../frontend/src-ssr/services/TenantCache.ts)** – Tenant configuration cache service

---

[← Back to Frontend Docs](./README.md)
