# Tenant & SSR Configuration

## Overview

QuVel Kit supports **multi-tenant SaaS applications** using either:

- **On-demand tenant loading** via individual `/tenant?domain=` requests, or  
- **Full preloading** via the `/tenant/cache` endpoint.

The behavior is controlled via `.env` variables, giving you flexibility to optimize for **speed** or **scale** depending on your needs.

If `VITE_MULTI_TENANT` is set to `false`, tenant config is loaded directly from `.env` at runtime. In multi-tenant mode, tenant configuration is resolved from the backend, either per-request or via preload.

A starter `.env.example` file is included in the frontend directory.

---

## Switching Strategies

To switch between preload and on-demand tenant resolution:

### Preload Mode

```env
VITE_SSR_PRELOAD_TENANTS=true
```

This will fetch `/tenant/cache` once on boot and refresh it periodically using `VITE_SSR_TENANT_REFRESH_INTERVAL_MS`.

Recommended for:

- Local development
- Projects with fewer than 500 tenants

### On-Demand Mode

```env
VITE_SSR_PRELOAD_TENANTS=false
```

This will resolve tenants dynamically using:

```http
GET /tenant?domain=your-host.com
```

Cached in memory using `VITE_SSR_TENANT_TTL_MS` (default: 60s).

Recommended for:

- Production setups with large or dynamic tenant sets

---

## Security Notes

Tenant config is filtered server-side using a `__visibility` map, which ensures only approved fields are exposed to SSR or injected into the frontend.

Sensitive configuration (e.g., API secrets, tokens) is **never** exposed.

For details, see: [Frontend Config Service](./frontend-config-service.md).

---

## Example `.env`

There is an example `.env` file in the frontend directory.

---

## Variable Breakdown

### Tenant Mode

| Variable                | Description                                                                 |
|-------------------------|-----------------------------------------------------------------------------|
| `VITE_MULTI_TENANT`     | Enables multi-tenant resolution. If false, SSR uses `.env` values instead. |

---

### Core URLs

| Variable                   | Description                                                                 |
|----------------------------|-----------------------------------------------------------------------------|
| `VITE_API_URL`             | Backend API URL for use in the browser.                                     |
| `VITE_APP_URL`             | Public-facing UI URL for the tenant, used in both single- and multi-tenant modes. |
| `VITE_INTERNAL_API_URL`    | Internal URL used by SSR to make backend calls (defaults to `VITE_API_URL`).|

---

### Single-Tenant Defaults

| Variable             | Description                                      |
|----------------------|--------------------------------------------------|
| `VITE_TENANT_ID`     | Tenant ID used when not in multi-tenant mode.   |
| `VITE_TENANT_NAME`   | Tenant name shown in UI (non-multitenant).      |

---

### SSR Behavior

| Variable                            | Description                                                                                          |
|-------------------------------------|------------------------------------------------------------------------------------------------------|
| `VITE_SSR_API_URL`                  | API base URL used by SSR middleware. Should point to a container-local backend (e.g. `127.0.0.1`).   |
| `VITE_SSR_PRELOAD_TENANTS`         | When `true`, preloads and caches all tenants from `/tenant/cache`. When `false`, resolves per request.|
| `VITE_SSR_TENANT_TTL`           | TTL in seconds for individual domain cache entries (on-demand mode). Default: 300             |
| `VITE_SSR_TENANT_REFRESH_INTERVAL` | Interval for refreshing all tenants (preload mode). Default: same as TTL                            |

---

### Realtime / OAuth

| Variable                   | Description                                                  |
|----------------------------|--------------------------------------------------------------|
| `VITE_PUSHER_APP_KEY`      | Pusher key used for realtime features.                      |
| `VITE_PUSHER_APP_CLUSTER`  | Pusher cluster ID (e.g. `us3`).                             |
| `VITE_SOCIALITE_PROVIDERS` | Comma-separated list of OAuth providers exposed to frontend.|

---

### Debugging & Environment

| Variable          | Description                                 |
|-------------------|---------------------------------------------|
| `VITE_APP_ENV`    | Runtime environment (e.g. `local`, `prod`). |
| `VITE_APP_NAME`   | App name shown in titles and metadata.      |
| `VITE_DEBUG`      | Enables verbose logging. Disable in prod.   |

---

[← Back to Frontend Docs](./README.md)
