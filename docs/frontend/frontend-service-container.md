# Frontend Service Container

## Overview

The **Service Container** in QuVel Kit implements an **dependency injection pattern** that orchestrates core and dynamic services throughout the application lifecycle. This architecture provides a centralized access point for essential services like API communication, validation, internationalization, task management, and real-time WebSocket communication. All core services were built with full isomorphic compatibility in mind,
and are ready to be used in both server and client environments with no additional configuration.

## Architectural Features

- **Strongly Typed Core Services** – TypeScript interfaces ensure type safety and prevent accidental modifications
- **Dynamic Service Registration** – Runtime service registration with dependency resolution
- **Lazy Loading & Initialization** – Services are instantiated only when required
- **Circular Dependency Prevention** – Service initialization tracking prevents recursive boot cycles
- **Lifecycle Hooks** – Services implement registration and boot phases for proper initialization
- **SSR Compatibility** – Seamless operation in both server and client environments
- **Modular Design** – Services are organized in the Core module for maintainability

---

## Core Services Architecture

The service container provides the following enterprise services:

| Service      | Description | Module Location |
|-------------|------------|----------------|
| `api`       | API communication service using Axios with interceptors for authentication, error handling, and request/response transformation | `Core/services/ApiService.ts` |
| `i18n`      | Internationalization service for dynamic locale management and translation | `Core/services/I18nService.ts` |
| `validation` | Schema-based validation service using Zod with form integration | `Core/services/ValidationService.ts` |
| `task`      | Task orchestration service for managing asynchronous operations | `Core/services/TaskService.ts` |
| `ws`        | WebSocket service for real-time communication using Laravel Echo and Pusher | `Core/services/WebSocketService.ts` |
| `config`    | Configuration service for accessing environment variables and settings | `Core/services/ConfigService.ts` |

### **Container Initialization Architecture**

The container is initialized per request in SSR mode or once in the client:

```ts
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { createApiService } from 'src/modules/Core/utils/axiosUtil';
import { createI18nService } from 'src/modules/Core/utils/i18nUtil';
import { createValidationService } from 'src/modules/Core/utils/validationUtil';
import { TaskService } from 'src/modules/Core/services/TaskService';
import { WebSocketService } from 'src/modules/Core/services/WebSocketService';
import { ConfigService } from 'src/modules/Core/services/ConfigService';

export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  const configService = new ConfigService();
  
  return new ServiceContainer(
    createApiService(ssrContext),
    createI18nService(ssrContext),
    createValidationService(),
    new TaskService(),
    new WebSocketService({
      apiKey: configService.get('PUSHER_APP_KEY'),
      cluster: configService.get('PUSHER_APP_CLUSTER'),
      apiUrl: configService.get('API_URL')
    }),
    configService,
    new Map() // Dynamic services map
  );
}
```

---

## Using the Service Container

### **Container Access Pattern**

Access the container through the `useContainer` composable:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';
const container = useContainer();
```

### **Core Service Access**

```ts
// API service for HTTP requests
const api = container.api;

// Internationalization service
const i18n = container.i18n;

// Validation service
const validation = container.validation;

// Task management service
const task = container.task;

// WebSocket service
const ws = container.ws;

// Configuration service
const config = container.config;
```

---

## **Enterprise Service Architecture**

### **The `BootableService` Interface**

All services implement the `BootableService` interface, which defines the service lifecycle:

```ts
export interface BootableService {
  /**
   * Registration phase - services receive container reference and register dependencies
   * @param container The service container instance
   */
  register(container: ServiceContainer): void;
  
  /**
   * Boot phase - services initialize after all services are registered
   * This method is optional and only implemented when needed
   */
  boot?(): void;
}
```

### **Service Implementation Pattern**

A properly implemented service follows this pattern:

```ts
import { BootableService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { Service } from 'src/modules/Core/services/Service';

export class NotificationService extends Service implements BootableService {
  private apiService!: ApiService;
  private i18nService!: I18nService;

  /**
   * Register dependencies from the container
   * @param container The service container instance
   */
  register(container: ServiceContainer): void {
    this.apiService = container.api;
    this.i18nService = container.i18n;
  }

  /**
   * Initialize the service after registration
   */
  boot(): void {
    // Initialization logic
  }

  /**
   * Send a notification to the user
   * @param message The notification message
   */
  send(message: string): void {
    // Implementation
  }
}
```

### **Dynamic Service Registration**

```ts
import { NotificationService } from 'src/modules/Notifications/services/NotificationService';

// Add a service to the container
container.addService("notification", new NotificationService());

// Retrieve a service with proper typing
const notification = container.getService<NotificationService>("notification");
notification?.send("Operation completed successfully");
```

---

## **Task Pattern in Services**

Services can encapsulate business logic in reusable tasks:

```ts
import { BootableService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { Service } from 'src/modules/Core/services/Service';
import { TaskInstance } from 'src/modules/Core/services/TaskService';
import { LaravelErrorHandler } from 'src/modules/Core/utils/taskUtil';

export class UserService extends Service implements BootableService {
  private container!: ServiceContainer;
  private fetchUserTask!: TaskInstance<User, { id: number }>;

  register(container: ServiceContainer): void {
    this.container = container;

    // Define a reusable task for fetching users
    this.fetchUserTask = container.task.newFrozenTask<User, { id: number }>({
      showLoading: true,
      showNotification: { 
        success: container.i18n.t('user.success.fetched')
      },
      task: async ({ id }) => await container.api.get(`/api/users/${id}`),
      errorHandlers: [LaravelErrorHandler()],
    });
  }

  /**
   * Fetch a user by ID
   * @param id The user ID
   * @returns The user data
   */
  async fetchUser(id: number): Promise<User> {
    return await this.fetchUserTask.run({ taskPayload: { id } });
  }
}
```

### **Using the Service Task**

```ts
const userService = container.getService<UserService>("user");
const user = await userService?.fetchUser(42);
```

---

## **Best Practices**

- **Single Responsibility Principle** – Each service should have a single, well-defined responsibility
- **Dependency Injection** – Use `register()` for proper dependency resolution
- **Immutable Services** – Treat core services as immutable to prevent side effects
- **Stateless Design** – Minimize state in services for better testability
- **Proper Error Handling** – Implement comprehensive error handling in service methods
- **SSR Compatibility** – Check for SSR environment before using browser-only APIs
- **TypeScript Interfaces** – Define clear interfaces for service contracts
- **Documentation** – Use JSDoc comments for all public methods

---

## Related Documentation

The **Service Container** provides the foundation for QuVel Kit's modular architecture. For detailed documentation on specific services:

- **[Task Management](./frontend-task-management.md)** – Task orchestration patterns
- **[Validation](./frontend-validation.md)** – Schema-based validation
- **[WebSockets](./frontend-websockets.md)** – Real-time communication
- **[Component Usage](./frontend-component-usage.md)** – Using services in components
