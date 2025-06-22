# Configuration Service

## Overview

QuVel Kit provides a robust **Configuration Service** that manages application settings across different environments with a tiered visibility system. This service handles the secure distribution of configuration values between server-side rendering (SSR) and client-side contexts, ensuring sensitive data is properly protected.

The configuration system supports both **single-tenant** and **multi-tenant** deployments, automatically adapting to the deployment mode without forcing tenant concepts on single-tenant applications.

## Features

- **Tiered Visibility System** – Controls which configuration values are exposed to different environments
- **Multi-Environment Support** – Works seamlessly in SSR, browser, and development environments
- **Flexible Deployment Modes** – Supports both single-tenant and multi-tenant applications
- **Type Safety** – Fully typed configuration values with TypeScript
- **Service Container Integration** – Available through the container as `container.config`
- **Decoupled Architecture** – Config system independent of multi-tenancy

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

1. Backend processes app/tenant configuration and applies visibility controls
2. Configuration values are filtered based on environment (SSR vs browser)
3. Only `public` values are injected into the browser's `window.__APP_CONFIG__`
4. `protected` values are available in SSR context only
5. `private` values remain backend-only

```ts
// Example configuration in single-tenant mode
{
  "apiUrl": "https://api.example.com",
  "appUrl": "https://app.example.com", 
  "appName": "Example App",
  "pusherAppKey": "app-key-123",
  "pusherAppCluster": "us2",
  "socialiteProviders": ["google", "github"]
}

// Example configuration in multi-tenant mode
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

In these examples:

- **Single-tenant**: Only base app configuration without tenant fields
- **Multi-tenant**: Includes `tenantId` and `tenantName` fields
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

#### Multi-tenant Mode (`SSR_MULTI_TENANT=true`)

1. The SSR middleware receives a request with the hostname
2. The `TenantCacheService` retrieves the tenant configuration based on the hostname
3. Backend processes and applies tenant configuration
4. The full configuration (including `PROTECTED` values) is attached to `req.appConfig`
5. The `filterConfig` function removes non-public values
6. Only `PUBLIC` values are injected into `window.__APP_CONFIG__`
7. The ConfigService in SSR context receives the full configuration
8. The client hydrates with only the `PUBLIC` values

#### Single-tenant Mode (`SSR_MULTI_TENANT=false`)

1. The SSR middleware receives a request
2. Configuration is created from environment variables
3. If `VITE_TENANT_ID`/`VITE_TENANT_NAME` exist, creates tenant config; otherwise creates app config
4. The configuration is attached to `req.appConfig`
5. The `filterConfig` function removes non-public values
6. Only `PUBLIC` values are injected into `window.__APP_CONFIG__`
7. The ConfigService in SSR context receives the configuration
8. The client hydrates with the `PUBLIC` values

### Dynamic Configuration Sources

Configuration values come from multiple sources depending on deployment mode:

#### Multi-tenant Mode

1. **Tenant Database Configuration** - Stored in `DynamicTenantConfig`
2. **Backend Processing** - Tenant-specific configuration processing and value resolution
3. **Environment Variables** - Fallback values

#### Single-tenant Mode

1. **Environment Variables** - Primary source (`VITE_*` variables)
2. **Backend Processing** - App-specific configuration processing
3. **Static Configuration** - Default values

### Configuration Priority

The ConfigService uses the following priority order when loading configuration:

1. SSR-provided configuration (highest priority)
2. Browser-injected configuration (`window.__APP_CONFIG__`)
3. Environment variables (lowest priority)

---

## Configuration Types

The configuration system supports multiple configuration types based on deployment mode:

### Base Application Configuration

The base configuration interface used in single-tenant deployments:

```ts
export interface AppConfig {
  apiUrl: string;                    // API endpoint URL
  appUrl: string;                    // Frontend application URL  
  appName: string;                   // Application name
  pusherAppKey?: string;             // Pusher app key for WebSockets
  pusherAppCluster?: string;         // Pusher app cluster
  socialiteProviders?: string[];     // Available OAuth providers
  recaptchaGoogleSiteKey?: string;   // reCAPTCHA site key
  sessionCookie?: string;            // Session cookie name
  assets?: AppAssets;                // Dynamic assets
  meta?: AppMeta;                    // Meta configuration
}
```

### Tenant Configuration

For multi-tenant deployments, the configuration extends the base with tenant-specific fields:

```ts
export interface TenantConfig extends AppConfig {
  tenantId: string;                  // Current tenant ID
  tenantName: string;                // Current tenant name
}
```

### SSR Configuration Types

The SSR context uses extended configuration types that include visibility controls:

```ts
export interface AppConfigProtected extends AppConfig {
  internalApiUrl?: string;
  __visibility: AppConfigVisibilityRecords;
}

export interface TenantConfigProtected extends TenantConfig {
  internalApiUrl?: string;
  __visibility: TenantConfigVisibilityRecords;
}

export type ConfigVisibility = 'public' | 'protected';
```

---

## Working with Dynamic Configuration

### Accessing Configuration in Frontend

The ConfigService handles all configuration access regardless of deployment mode:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { config } = useContainer();

// Get standard configuration values
const apiUrl = config.get('apiUrl');
const appName = config.get('appName');

// Get tenant-specific values (only available in multi-tenant mode)
const tenantId = config.getTenantId(); // Returns string | null
const tenantName = config.getTenantName(); // Returns string | null

// Check if running in tenant mode
if (config.isTenantConfig()) {
  // This is a multi-tenant deployment
  console.log('Tenant:', config.getTenantName());
} else {
  // This is a single-tenant deployment
  console.log('Single-tenant app:', config.get('appName'));
}

// Get any configuration value
const customSetting = config.get('your_module_setting');
```

### Type Safety with Configuration

The configuration system provides type safety through proper typing:

```ts
// The ConfigService is generic and can be typed
import { ConfigService } from 'src/modules/Core/services/ConfigService';
import type { AppConfig, TenantConfig } from 'src/modules/Core/types/tenant.types';

// For single-tenant applications
const appConfigService = new ConfigService<AppConfig>();

// For multi-tenant applications  
const tenantConfigService = new ConfigService<TenantConfig>();

// Runtime type checking
if (config.isTenantConfig()) {
  // TypeScript knows this has tenant fields
  const tenantId = config.getTenantId(); // string | null
  const tenantName = config.getTenantName(); // string | null
}

// Define your module's expected configuration
interface MyModuleConfig {
  my_module_api_key?: string;
  my_module_features?: string[];
}

// Create a typed getter that works with both modes
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
- **[configUtil.ts](../../frontend/src/modules/Core/utils/configUtil.ts)** – Configuration utility functions
- **[SSRRequestHandler.ts](../../frontend/src-ssr/services/SSRRequestHandler.ts)** – SSR request handler with config resolution
- **[configUtil.ts](../../frontend/src-ssr/utils/configUtil.ts)** – SSR configuration utilities
- **[tenant.types.ts](../../frontend/src-ssr/types/tenant.types.ts)** – SSR configuration type definitions

### Backend

- **[DynamicTenantConfig.php](../../backend/Modules/Tenant/app/ValueObjects/DynamicTenantConfig.php)** – Dynamic configuration value object
- **[ConfigurationPipeInterface.php](../../backend/Modules/Tenant/app/Contracts/ConfigurationPipeInterface.php)** – Pipe interface
- **[CoreConfigPipe.php](../../backend/Modules/Tenant/app/Pipes/CoreConfigPipe.php)** – Core configuration pipe
- **[AuthConfigPipe.php](../../backend/Modules/Auth/app/Pipes/AuthConfigPipe.php)** – Auth configuration pipe
- **[ConfigurationPipeline.php](../../backend/Modules/Tenant/app/Services/ConfigurationPipeline.php)** – Pipeline service

---

[← Back to Frontend Docs](./README.md)
