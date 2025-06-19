# Service Container

## Overview

The Service Container in QuVel Kit implements a dependency injection pattern that orchestrates core and dynamic services throughout the application lifecycle. This architecture provides a centralized access point for essential services like API communication, validation, internationalization, task management, and real-time WebSocket communication. All core services work in both server and client environments with SSR support built-in.

> **Warning**: This documentation covers the **Quasar application container** (`src/modules/Core/`). There is a separate **Express container** (`src-ssr/`) for framework-level services. See [Express and Quasar Service Containers](./frontend-ssr-services.md) for details on the Express container architecture.

## Key Features

- **Strongly Typed Services** – TypeScript interfaces ensure type safety
- **Uniform DI Strategy** – All services follow the same lifecycle pattern
- **SSR-Aware Services** – Services can access request/response objects in SSR context
- **Lifecycle Management** – Three-phase initialization: construct → boot → register
- **Error Handling** – Comprehensive error handling during service initialization
- **Lazy Loading** – Services can be instantiated on-demand
- **Store Integration** – Available in Pinia stores via plugin

## Core Services

The service container provides these core services:

| Service      | Description | Documentation |
|-------------|------------|---------------|
| `api`       | API communication using Axios with interceptors | [API Service](./frontend-api-service.md) |
| `i18n`      | Internationalization and translation | [Translations](./frontend-translations.md) |
| `validation` | Schema-based validation using Zod | [Validation](./frontend-validation.md) |
| `task`      | Async operation orchestration | [Task Management](./frontend-task-management.md) |
| `ws`        | Real-time communication with Laravel Echo | [WebSockets](./frontend-websockets.md) |
| `config`    | Configuration with tiered visibility system | [Configuration Service](./frontend-config-service.md) |
| `log`       | Logging service for client and server | [Logging Service](./frontend-logging.md) |

## Using the Service Container

### In Vue Components

Access the container through the `useContainer` composable:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

// Destructure only what you need
const { api, i18n } = useContainer();

// Use services directly
api.get('/users');
i18n.t('auth.welcomeMessage');
```

### In Pinia Stores

The container is automatically available in all Pinia stores via the `$container` property:

```ts
import { defineStore } from 'pinia';

export const useUserStore = defineStore('user', {
  actions: {
    async fetchUser(id: number) {
      return await this.$container.api.get(`/users/${id}`);
    }
  }
});
```

## Service Lifecycle

All services follow a uniform three-phase lifecycle:

1. **Constructor** - Parameterless constructor for basic setup
2. **Boot** (optional) - Receives SSR context for initialization
3. **Register** (optional) - Receives container for dependency injection

## Creating Custom Services

### Basic Service (Non-SSR)

For services that don't need SSR context:

```ts
import { Service } from 'src/modules/Core/services/Service';
import { RegisterService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

export class NotificationService extends Service implements RegisterService {
  private api!: ApiService;
  
  constructor() {
    super();
    // No parameters allowed
  }
  
  // Called after all services are instantiated
  register(container: ServiceContainer): void {
    this.api = container.api;
  }
  
  // Service methods
  send(message: string): void {
    // Implementation using this.api
  }
}
```

### SSR-Aware Service

For services that need access to request/response objects:

```ts
import { Service } from 'src/modules/Core/services/Service';
import { RegisterService, SsrAwareService, SsrServiceOptions } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';

export class ServerDataService extends Service implements SsrAwareService, RegisterService {
  private data?: any;
  private api!: ApiService;
  
  // Called with SSR context (req/res) if available
  boot(ssrServiceOptions?: SsrServiceOptions): void {
    if (ssrServiceOptions?.req) {
      // Access request headers, cookies, etc.
      this.data = ssrServiceOptions.req.headers['x-custom-header'];
    }
  }
  
  // Called after all services are instantiated
  register(container: ServiceContainer): void {
    this.api = container.api;
  }
  
  getData(): any {
    return this.data;
  }
}
```

## Dynamic Service Registration

You can register your own services to the container in multiple ways:

### Lazy Loading with Class Constructor

Services are automatically instantiated, booted (if SSR-aware), and registered when first accessed:

```ts
import { NotificationService } from './NotificationService';

// Get the container
const container = useContainer();

// Get or lazily create a service by class constructor
const notification = container.get(NotificationService);
notification.send("Operation completed");
```

### Pre-registering Services

You can add services to the container explicitly:

```ts
import { NotificationService } from './NotificationService';

// Get the container
const container = useContainer();

// Add a service class (will be instantiated with proper lifecycle)
const added = container.addService(NotificationService);
console.log(added); // true if added, false if already exists

// Later, retrieve the service
const notification = container.get(NotificationService);
notification.send("Operation completed");
```

### Checking Service Existence

```ts
import { NotificationService } from './NotificationService';

const container = useContainer();

// Check if a service is registered
if (container.hasService(NotificationService)) {
  const notification = container.get(NotificationService);
  notification.send("Service exists!");
}
```

## Rules and Best Practices

### Lifecycle Rules

- **Constructor** must be parameterless - no arguments allowed
- **Boot** method should NOT access the container or other services
- **Register** method is where you get dependencies from the container
- Services are instantiated in order, then all are booted, then all are registered

### Architecture Guidelines

- Circular dependencies between services should be avoided
- The container is recreated for each SSR request to maintain isolation
- Services are singleton instances within a container - they maintain state
- Always destructure only the services you need from the container
- Use utility functions (createApi, createLogger, etc.) for complex initialization

### SSR Considerations

- This Quasar container runs inside SSR requests (not the Express server level)
- SSR-aware services receive request/response objects in boot() during SSR
- Non-SSR services work the same in both server and client
- The container handles SSR context automatically - no manual checks needed
- For Express server-level services, see [Express Container documentation](./frontend-ssr-services.md)

### Error Handling

- Service initialization errors are caught and logged with descriptive messages
- Failed boot or register phases will throw with the service name and error
- Check console for detailed error information during development

## Type Definitions

```ts
// Base service type
export type Service = object;

// SSR context passed to boot method
export interface SsrServiceOptions {
  req?: Request;
  res?: Response;
}

// Interface for SSR-aware services
export interface SsrAwareService extends Service {
  boot(ssrServiceOptions?: SsrServiceOptions): void;
}

// Interface for services that need the container
export interface RegisterService {
  register(container: ServiceContainer): void;
}

// Service class type for container
export type ServiceClass<T extends Service = Service> = new () => T;
```

[← Back to Frontend Docs](./README.md)
