# Frontend Service Container

## Overview

The **Service Container** in QuVel Kit is a **dependency injection system** that manages core and dynamic services efficiently. It provides a structured way to access essential utilities like API requests, validation, translations, and task management across the frontend.

## Features

- **Strongly Typed Core Services** – Ensures safety and prevents accidental modifications.
- **Dynamic Service Registration** – Developers can register and retrieve custom services on demand.
- **Lazy Loading** – Services are only initialized when needed.
- **Prevention of Circular Booting** – Services are tracked to prevent recursive initialization.
- **Bootstrap & Register Lifecycle** – Services can define initialization logic before booting.
- **SSR Compatibility** – Ensures proper state management between server and client environments.

---

## Core Services

The service container provides the following built-in services:

| Service      | Description |
|-------------|------------|
| `api`       | API service using Axios for HTTP requests. Uses Laravel Session Cookie in SSR for authenticated requests. |
| `i18n`      | Internationalization service for translations. Uses Language cookie in both SSR and client for locale. |
| `validation` | Validation service using Zod with helpers for component validation rules and translation. |
| `task`      | Task service for managing async operations and common actions. |

### **Initializing the Container**

The container is initialized per request (SSR) or once in the client:

```ts
import { ServiceContainer } from 'src/services/ServiceContainer';
import { createApiService } from 'src/utils/axiosUtil';
import { createI18nService } from 'src/utils/i18nUtil';
import { createValidationService } from 'src/utils/validationUtil';
import { TaskService } from 'src/services/TaskService';
import { ExampleService } from 'src/services/ExampleService';

export function createContainer(ssrContext?: QSsrContext | null): ServiceContainer {
  return new ServiceContainer(
    createApiService(ssrContext),
    createI18nService(ssrContext),
    createValidationService(),
    new TaskService(),
    new Map([["example", new ExampleService()]]),
  );
}
```

---

## Using the Service Container

### **Retrieving the Container**

You can access the container globally in your components:

```ts
import { useContainer } from 'src/composables/useContainer';
const container = useContainer();
```

### **Accessing Core Services**

```ts
const api = container.api;
const i18n = container.i18n;
const validation = container.validation;
const task = container.task;
```

---

## **Creating Custom Services**

### **Understanding `BootableService` Contract**

All services that need to be registered dynamically must implement `BootableService`, which contains two lifecycle methods:

```ts
export interface BootableService {
  register(container: ServiceContainer): void;
  boot?(): void;
}
```

### **Adding a Custom Service**

A custom service must implement `BootableService`, allowing it to interact with the container and dependencies.

#### **Example: Creating a Notification Service**

```ts
import type { BootableService } from 'src/types/service.types';
import type { ServiceContainer } from 'src/services/ServiceContainer';

export class NotificationService implements BootableService {
  private container!: ServiceContainer;

  register(container: ServiceContainer): void {
    this.container = container;
  }

  boot(): void {
    console.log("NotificationService booted.");
  }

  send(message: string): void {
    console.log(`[Notification]: ${message}`);
  }
}
```

### **Registering a Custom Service**

```ts
import { NotificationService } from 'src/services/NotificationService';

container.addService("notification", new NotificationService());
```

### **Using the Custom Service**

```ts
const notification = container.getService<NotificationService>("notification");
notification?.send("Task completed successfully!");
```

---

## **Example: Creating a Reusable Task in a Service**

Services can include **predefined tasks** that encapsulate common workflows.

### **Example: Notification Service with a Task**

```ts
import type { BootableService } from 'src/types/service.types';
import type { ServiceContainer } from 'src/services/ServiceContainer';
import type { TaskInstance } from 'src/services/TaskService';

export class NotificationService implements BootableService {
  private container!: ServiceContainer;
  private notifyTask!: TaskInstance<void, string>;

  register(container: ServiceContainer): void {
    this.container = container;

    this.notifyTask = container.task.newFrozenTask<void, string>({
      showLoading: true,
      showNotification: { success: "Notification sent!" },
      task: async (message) => this.send(message),
    });
  }

  send(message: string): void {
    console.log(`[Notification]: ${message}`);
  }

  async notify(message: string): Promise<void> {
    await this.notifyTask.run({ taskPayload: message });
  }
}
```

### **Using the Notification Task**

```ts
const notificationService = container.getService<NotificationService>("notification");
await notificationService?.notify("User logged in.");
```

This ensures a **reusable, standardized** way to manage notifications throughout the app.

---

## **Best Practices for Custom Services**

- **Use `register()` for dependency injection** – Ensures services are available when needed.
- **Keep services stateless if possible** – Reduces complexity and makes them more reusable.
- **Use `boot()` for initialization logic** – Avoid modifying the container at this stage.
- **Ensure SSR compatibility** – Be mindful of code that should only run on the client.
- **Prevent recursive dependencies** – Do not add services inside `boot()` to avoid infinite loops.

---

## Conclusion

The **Service Container** provides a **scalable**, **modular**, and **SSR-compatible** approach to dependency management in QuVel Kit. With its built-in services and dynamic registration system, developers can extend functionality while maintaining **clear structure and maintainability**.

For more in-depth **usage examples**, refer to:

- **[Frontend Component Usage](./frontend-component-usage.md)**
- **[Frontend Tasks](./frontend-tasks.md)**
- **[Frontend API & Requests](./frontend-api.md)**
