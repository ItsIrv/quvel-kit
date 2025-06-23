# Express and Quasar Service Containers

## Two Container Architecture

QuVel Kit uses **two separate service containers**:

1. **Express Container** (`src-ssr/`) - Framework-level services running with Express server
2. **Quasar Container** (`src/modules/Core/`) - Application services running inside Quasar SSR requests

> **Warning**: Most developers should only modify the Quasar container. The Express container is for framework internals.

## Express Container Architecture

### Initialization Flow

1. **Server Startup**: Express server starts and creates the container
2. **Middleware Registration**: SSR middleware registers request handlers  
3. **Request Handling**: Middleware uses container services to process requests

```ts
// server.ts - Express server startup
export const create = defineSsrCreate(() => {
  // Initialize Express container singleton
  getSSRContainer();
  const app = express();
  return app;
});
```

```ts
// render.ts - Express middleware
export default defineSsrMiddleware(({ app, resolve, render }) => {
  app.get(resolve.urlPath('*'), async (req, res) => {
    // Get Express container and handle request
    const container = getSSRContainer();
    const handler = container.get(SSRRequestHandler);
    await handler.handleRequest(req, res, render);
  });
});
```

### Express Container Services

**Location**: `frontend/src-ssr/services/`

| Service | Purpose |
|---------|---------|
| `SSRLogService` | Server-side logging |
| `SSRApiService` | Backend HTTP requests |
| `SSRTenantCacheService` | Tenant configuration cache (multi-tenant mode) |
| `TenantResolver` | Domain-to-tenant resolution (multi-tenant mode) |
| `SSRRequestHandler` | Main request coordination and config resolution |

### Express Container Flow

1. Express server starts → Creates singleton container
2. HTTP request arrives → Middleware gets container  
3. SSRRequestHandler resolves app/tenant configuration
4. Configuration attached to `req.appConfig`
5. Services coordinate → Renders Quasar app with config context

## Quasar Container

**Location**: `frontend/src/modules/Core/services/`

Application services that run inside each Quasar SSR request. These are the same services that run on the client side, with SSR-aware `boot()` methods for server-side initialization.

- Standard application services (API, Config, I18n, etc.)
- SSR-aware services receive request context during `boot()`
- Same services used on client-side after hydration

## Express Container Service Types

### SSRSingletonService (Default)

Stateless services shared across all Express requests.

```ts
export class SSRApiService extends SSRService implements SSRSingletonService {
  register(container: SSRServiceContainer): void {
    this.logger = container.get(SSRLogService);
  }

  // Pass request context as parameters - never store in instance
  async get<T>(url: string, options?: { req?: Request }): Promise<T> {
    const baseURL = options?.req?.requestContext?.appConfig?.internalApiUrl;
    // Use baseURL for this request only
  }
}
```

### SSRScopedService

Request-specific services created via `container.scoped()` when you need to store request state.

```ts
export class RequestContextService extends SSRService implements SSRScopedService {
  private userAgent?: string;
  
  register(container: SSRServiceContainer): void {
    // Get dependencies
  }
  
  boot(options?: SSRServiceOptions): void {
    this.userAgent = options?.req?.headers['user-agent'];
  }
}
```

## Express Container Usage

### In Middleware (Framework Level)

```ts
// Express middleware gets container
export default defineSsrMiddleware(({ app, resolve, render }) => {
  app.get(resolve.urlPath('*'), async (req, res) => {
    const container = getSSRContainer();
    const handler = container.get(SSRRequestHandler);
    await handler.handleRequest(req, res, render);
  });
});
```

### Custom Express Services

```ts
// Most services should be singletons
const container = getSSRContainer();
const apiService = container.get(SSRApiService);
const data = await apiService.get('/endpoint', { req });

// Scoped services only when you need request state
const requestService = container.scoped(RequestContextService, { req, res });
const userAgent = requestService.getUserAgent();
```

## Configuration in SSR Services

The SSR system now uses a decoupled configuration approach that supports both single-tenant and multi-tenant deployments:

### Request Configuration

Configuration is attached to each request as `req.requestContext`:

```ts
// In SSR services, access configuration from request
export class MySSRService extends SSRService implements SSRSingletonService {
  async processRequest(req: Request): Promise<void> {
    // Access app configuration (works in both modes)
    const appConfig = req.requestContext?.appConfig;
    const apiUrl = appConfig?.apiUrl;
    const appName = appConfig?.appName;
    
    // Check if this is a tenant configuration
    if (appConfig && 'tenantId' in appConfig) {
      const tenantId = appConfig.tenantId;
      const tenantName = appConfig.tenantName;
      // Handle tenant-specific logic
    }
    
    // Use configuration for this request
    const response = await fetch(`${apiUrl}/endpoint`);
  }
}
```

### Configuration Types in SSR

The SSR system works with these configuration types:

- **`AppConfigProtected`**: Single-tenant mode without tenant fields
- **`TenantConfigProtected`**: Multi-tenant mode or single-tenant with tenant context
- Both include visibility controls (`__visibility`) for filtering public/protected fields

### Configuration Resolution

1. **Multi-tenant mode** (`SSR_MULTI_TENANT=true`): Resolves tenant from hostname via database
2. **Single-tenant mode** (`SSR_MULTI_TENANT=false`): Creates config from environment variables
3. **Hybrid mode**: Single-tenant with optional tenant context if `VITE_TENANT_ID`/`VITE_TENANT_NAME` are set

## Security Rules

- **Singletons**: Never store request data in instance variables
- **Scoped**: Only when you need request-specific state  
- **Request context**: Pass as method parameters
- **Configuration**: Always access via `req.requestContext.appConfig`, never cache in service instances

---

[← Back to Frontend Docs](./README.md)
