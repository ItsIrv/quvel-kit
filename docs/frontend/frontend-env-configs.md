# Environment Configuration

## Overview

QuVel Kit uses environment variables to configure various aspects of the application. These variables control features like multi-tenancy, logging, API endpoints, and more. The configuration is split between client-side variables (prefixed with `VITE_`) and server-side variables (prefixed with `SSR_`).

A starter `.env.example` file is included in the frontend directory that you can copy to `.env` and customize.

## Deployment Modes

QuVel Kit supports both single-tenant and multi-tenant deployments with a decoupled configuration system:

```env
# Enable or disable multi-tenant mode
SSR_MULTI_TENANT=true
```

### Multi-Tenant Mode (`SSR_MULTI_TENANT=true`)

- Configuration is resolved based on the hostname
- Tenant data is fetched from the database
- Includes `tenantId` and `tenantName` in the configuration
- Uses `TenantConfig` interface

### Single-Tenant Mode (`SSR_MULTI_TENANT=false`)

- Configuration comes from environment variables
- Uses `AppConfig` interface (no tenant fields)
- Can optionally include tenant fields if `VITE_TENANT_ID`/`VITE_TENANT_NAME` are set
- Suitable for dedicated single-tenant deployments

## Core Configuration

### API and Application URLs

```env
# Public API endpoint for browser requests
VITE_API_URL="https://api.example.com"

# Public-facing application URL
VITE_APP_URL="https://app.example.com"

# Internal API URL used by SSR (defaults to VITE_API_URL if not set)
VITE_INTERNAL_API_URL="https://api.internal.example.com"
```

### Application Information

```env
# Application name used in titles and metadata
VITE_APP_NAME=QuVel

# Runtime environment (local, development, staging, production)
VITE_APP_ENV=local
```

## Logging Configuration

QuVel Kit includes a robust logging system that can be configured via environment variables:

```env
# Enable or disable debug mode
VITE_DEBUG=true

# Logger implementation (console, null)
VITE_LOGGER=console

# Minimum log level to display (debug, info, notice, warning, error, critical, alert, emergency)
VITE_LOG_LEVEL=debug
```

## WebSocket Configuration

For real-time features, QuVel Kit uses Pusher:

```env
# Pusher application key
VITE_PUSHER_APP_KEY=your_pusher_key

# Pusher cluster
VITE_PUSHER_APP_CLUSTER=us3
```

## Tenant Configuration (Optional)

When running in single-tenant mode, you can optionally include tenant information:

```env
# Optional: Tenant ID for single-tenant mode with tenant context
VITE_TENANT_ID=1

# Optional: Tenant name for display
VITE_TENANT_NAME=QuVel
```

> **Note**: These variables are **optional** in single-tenant mode. When provided, the configuration will include tenant fields (`tenantId`, `tenantName`) and use the `TenantConfig` interface. When omitted, the configuration uses the base `AppConfig` interface without tenant fields.

### When to Use Tenant Variables in Single-Tenant Mode

- **Include them** if your single-tenant app needs tenant context (e.g., branding, tenant-specific features)
- **Omit them** for pure single-tenant apps that don't need tenant concepts
- **Multi-tenant mode** always includes tenant data from the database regardless of these variables

## SSR-Only Configuration

These variables are only used in the server-side rendering (SSR) environment and should not be prefixed with `VITE_`:

```env
# Whether to preload all tenants at startup
SSR_TENANT_SSR_PRELOAD_TENANTS=true

# TTL for tenant resolver cache in seconds
SSR_TENANT_SSR_RESOLVER_TTL=300

# TTL for tenant data cache in seconds
SSR_TENANT_SSR_CACHE_TTL=300

# API URL for SSR tenant resolution
SSR_TENANT_SSR_API_URL="https://api.internal.example.com"

# API key for secure SSR communication
SSR_API_KEY=your_secure_ssr_api_key
```

## Authentication and Integration

```env
# Comma-separated list of enabled OAuth providers
VITE_SOCIALITE_PROVIDERS="github,google"

# Google reCAPTCHA site key
VITE_RECAPTCHA_KEY=your_recaptcha_key
```

## Variable Reference

### Client and Universal Variables

| Variable | Description | Default |
|----------|-------------|--------|
| `VITE_API_URL` | Public API endpoint | - |
| `VITE_APP_URL` | Public application URL | - |
| `VITE_APP_NAME` | Application name | `QuVel` |
| `VITE_APP_ENV` | Runtime environment | `local` |
| `VITE_DEBUG` | Enable debug mode | `false` |
| `VITE_LOGGER` | Logger implementation | `null` |
| `VITE_LOG_LEVEL` | Minimum log level | `info` |
| `VITE_PUSHER_APP_KEY` | Pusher application key | - |
| `VITE_PUSHER_APP_CLUSTER` | Pusher cluster | `us3` |
| `VITE_TENANT_ID` | Optional tenant ID for single-tenant mode | - |
| `VITE_TENANT_NAME` | Optional tenant name | - |
| `VITE_SOCIALITE_PROVIDERS` | Enabled OAuth providers | - |
| `VITE_INTERNAL_API_URL` | Internal API URL for SSR | Same as `VITE_API_URL` |
| `VITE_RECAPTCHA_KEY` | Google reCAPTCHA site key | - |

### SSR-Only Variables

| Variable | Description | Default |
|----------|-------------|--------|
| `SSR_TENANT_SSR_PRELOAD_TENANTS` | Preload all tenants at startup | `false` |
| `SSR_TENANT_SSR_RESOLVER_TTL` | Tenant resolver cache TTL (seconds) | `300` |
| `SSR_TENANT_SSR_CACHE_TTL` | Tenant data cache TTL (seconds) | `300` |
| `SSR_TENANT_SSR_API_URL` | API URL for SSR tenant resolution | - |
| `SSR_API_KEY` | API key for secure SSR communication | - |
| `SSR_MULTI_TENANT` | Enables multi-tenant mode | `false` |

## Configuration Examples

### Pure Single-Tenant Setup

```env
SSR_MULTI_TENANT=false
VITE_API_URL="https://api.myapp.com"
VITE_APP_URL="https://myapp.com"
VITE_APP_NAME="My Application"
# No VITE_TENANT_ID or VITE_TENANT_NAME - uses AppConfig
```

### Single-Tenant with Tenant Context

```env
SSR_MULTI_TENANT=false
VITE_API_URL="https://api.myapp.com"
VITE_APP_URL="https://myapp.com"
VITE_APP_NAME="My Application"
VITE_TENANT_ID="main-tenant"
VITE_TENANT_NAME="Main Organization"
# Uses TenantConfig due to tenant variables
```

### Multi-Tenant Setup

```env
SSR_MULTI_TENANT=true
VITE_API_URL="https://api.platform.com"
SSR_TENANT_SSR_API_URL="https://internal-api.platform.com"
SSR_API_KEY="secure-api-key"
# Tenant data comes from database based on hostname
```

## Security Considerations

- SSR-only variables are never exposed to the client
- Configuration is filtered based on visibility levels (`public`, `protected`, `private`)
- Sensitive values like API keys should be kept secure
- The configuration system automatically adapts to deployment mode

For more details on how configuration values are filtered and used, see the [Config Service documentation](./frontend-config-service.md).

---

[← Back to Frontend Docs](./README.md)
