# Service Container

## Overview

The Service Container in QuVel Kit implements a dependency injection pattern that orchestrates core and dynamic services throughout the application lifecycle. This architecture provides a centralized access point for essential services like API communication, validation, internationalization, task management, and real-time WebSocket communication. All core services work in both server and client environments with no additional configuration.

## Key Features

- **Strongly Typed Services** – TypeScript interfaces ensure type safety
- **Dynamic Registration** – Runtime service registration with dependency resolution
- **Lifecycle Management** – Services implement registration and boot phases
- **SSR Compatibility** – Works in both server and client environments
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
| `config`    | Environment variables and settings | - |

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

## Creating Custom Services

Custom services should implement the `BootableService` interface:

```ts
import { BootableService } from 'src/modules/Core/types/service.types';
import { ServiceContainer } from 'src/modules/Core/services/ServiceContainer';
import { Service } from 'src/modules/Core/services/Service';

export class NotificationService extends Service implements BootableService {
  private apiService!: ApiService;
  
  // Register dependencies from the container
  register({ api }: ServiceContainer): void {
    this.apiService = api;
  }
  
  // Optional boot method for initialization
  boot(): void {
    // Initialization logic
  }
  
  // Service methods
  send(message: string): void {
    // Implementation
  }
}
```

## Dynamic Service Registration

You can register your own services to the container:

```ts
import { NotificationService } from './NotificationService';

// Get the container
const container = useContainer();

// Add a service
container.addService("notification", new NotificationService());

// Retrieve a service with proper typing
const notification = container.getService<NotificationService>("notification");
notification?.send("Operation completed");
```

## Rules and Gotchas

- Services are initialized in a specific order; core services are available first
- Circular dependencies between services should be avoided
- The container is recreated for each SSR request to maintain isolation
- Dynamic services are not available during SSR unless explicitly registered
- Always destructure only the services you need from the container
- Services are singleton instances - they maintain state between uses

## Source Files

- [ServiceContainer.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/services/ServiceContainer.ts) - Main container implementation
- [container.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/boot/container.ts) - Quasar boot file for container initialization
- [serviceContainer.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/stores/plugins/serviceContainer.ts) - Pinia plugin for store integration

---

[← Back to Frontend Docs](./README.MD)
