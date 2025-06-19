# Environment Configuration

## Overview

QuVel Kit uses environment variables to configure various aspects of the application. These variables control features like multi-tenancy, logging, API endpoints, and more. The configuration is split between client-side variables (prefixed with `VITE_`) and server-side variables (prefixed with `SSR_`).

A starter `.env.example` file is included in the frontend directory that you can copy to `.env` and customize.

## Multi-Tenant Mode

QuVel Kit supports both single-tenant and multi-tenant deployments:

```env
# Enable or disable multi-tenant mode
SSR_MULTI_TENANT=true
```

When `SSR_MULTI_TENANT` is set to `true`, the application will resolve tenant configuration based on the hostname. When set to `false`, the application will use the values from environment variables.

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

## Single-Tenant Configuration

When running in single-tenant mode, these variables define the tenant:

```env
# Tenant ID for single-tenant mode
VITE_TENANT_ID=1

# Tenant name for display
VITE_TENANT_NAME=QuVel
```

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
| `VITE_TENANT_ID` | Tenant ID for single-tenant mode | `1` |
| `VITE_TENANT_NAME` | Tenant name | `QuVel` |
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

## Security Considerations

- SSR-only variables are never exposed to the client
- Tenant configuration is filtered based on visibility levels
- Sensitive values like API keys should be kept secure

For more details on how configuration values are filtered and used, see the [Config Service documentation](./frontend-config-service.md).

---

[← Back to Frontend Docs](./README.md)
