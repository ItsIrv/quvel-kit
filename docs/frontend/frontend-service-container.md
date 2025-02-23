# Frontend Service Container

## Overview

The **Service Container** in QuVel Kit is a **dependency injection system** that manages core and dynamic services efficiently. It provides a structured way to access essential utilities like API requests, validation, translations, and task management across the frontend.

## Features

- **Strongly Typed Core Services** – Ensures safety and prevents accidental modifications.
- **Dynamic Service Registration** – Developers can register and retrieve custom services on demand.
- **Lazy Loading** – Services are only initialized when needed.
- **Prevention of Circular Booting** – Services are tracked to prevent recursive initialization.
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
    new Map([['example', new ExampleService()]]),
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

## Dynamic Services

Dynamic services allow developers to register custom services at runtime.

### **Adding a Dynamic Service**

```ts
import { ExampleService } from 'src/services/ExampleService';
container.addService('exampleService', new ExampleService());
```

### **Retrieving a Dynamic Service**

```ts
const exampleService = container.getService<ExampleService>('exampleService');
exampleService?.test(42);
```

### **Checking if a Service Exists**

```ts
if (container.hasService('exampleService')) {
  console.log('ExampleService is registered');
}
```

### **Preventing Duplicate Service Registration**

To avoid accidental overwrites, use the `overwrite` flag:

```ts
container.addService('exampleService', new ExampleService(), true); // Forces overwrite
```

---

## Preventing Circular Booting

If a service adds another service in its `register` method, ensure it does not cause a recursive loop. While internal checks exist, it is best to avoid it in practice.

### **Example of Correct Usage**

```ts
export class ExampleService extends Service implements BootableService {
  register(container: ServiceContainer): void {
    if (!container.hasService('exampleBoot')) {
      container.addService('exampleBoot', new ExampleService());
    }
  }
}

---

## Best Practices

- **Use the core services when possible** – The container provides optimized, built-in services for API, validation, translations, and tasks.
- **Avoid overloading the container** – Only add necessary dynamic services.
- **Lazy-load services where applicable** – Only initialize services when they are actually needed.
- **Prevent recursive dependencies** – Ensure services do not depend on each other in a circular way.
- **Use SSR-compatible logic** – Ensure services handle both server and client-side execution properly.
