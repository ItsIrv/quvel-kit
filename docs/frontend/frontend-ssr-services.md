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
| `SSRTenantCacheService` | Tenant configuration cache |
| `TenantResolver` | Domain-to-tenant resolution |
| `SSRRequestHandler` | Main request coordination |

### Express Container Flow

1. Express server starts → Creates singleton container
2. HTTP request arrives → Middleware gets container  
3. Middleware uses services → Processes request
4. Services coordinate → Renders Quasar app

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
    const baseURL = options?.req?.tenantConfig?.internalApiUrl;
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

## Security Rules

- **Singletons**: Never store request data in instance variables
- **Scoped**: Only when you need request-specific state  
- **Request context**: Pass as method parameters

---

[← Back to Frontend Docs](./README.md)
