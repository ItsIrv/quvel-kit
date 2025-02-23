# Task Management

## Overview

QuVel Kit provides a **Task Service** that simplifies asynchronous operations. It handles **loading states, error handling, success notifications, and lifecycle hooks**.

## Features

- **Encapsulated Async Logic** – Handles API calls, form submissions, and background operations.
- **Automatic Loading State** – Shows loading indicators when tasks run.
- **Error & Success Handling** – Displays notifications and error messages.
- **Reactivity & State Tracking** – Provides real-time updates on task execution.
- **Laravel Error Handling** – Extracts validation and API errors automatically.
- **Reusable Frozen Tasks** – Define reusable async logic in services.

---

## Using the Task Service

The `container.task` service allows developers to create tasks with structured workflows.

### **Creating a Task**

A basic example of using the task service:

```ts
const getItemAndShowLoading = container.task.newTask<string, { id: number }>({
  showLoading: true,
  task: async ({ id } /* from task payload */) 
    => await container.api.get(`/items/${id}`),
  successHandlers: [
    (result) => console.log('Fetched item:', result), // result from task
  ],
  errorHandlers: [(error /* error from catch */) => console.error('Failed to fetch item:', error)],
});

await getItemAndShowLoading.run({
  taskPayload: { id: 42 },
});
```

---

## **Task Lifecycle & State**

Tasks provide built-in state tracking:

| State       | Description |
|------------|------------|
| `fresh`    | Task has not been run yet. |
| `active`   | Task is currently running. |
| `success`  | Task completed successfully. |
| `error`    | Task encountered an error. |

You can track task state in Vue components:

```vue
<template>
  <q-btn :loading="task.state.value === 'active'" @click="task.run()">
    Fetch Data
  </q-btn>
</template>
```

---

## **Error Handling & Laravel Validation**

QuVel Kit automatically extracts errors from Laravel API responses.

### **Handling Laravel Errors**

```ts
import { LaravelErrorHandler } from 'src/utils/taskUtil';

const fetchUserTask = container.task.newTask<User, { id: number }>({
  task: async ({ id }) => await container.api.get(`/users/${id}`),
  errorHandlers: [LaravelErrorHandler()],
});
```

### **Example: Handling Form Errors**

```vue
<template>
  <q-form @submit.prevent="loginTask.run()">
    <EmailField v-model="email" :error-message="loginTask.errors.value.email?.[0]" />
    <PasswordField v-model="password" :error-message="loginTask.errors.value.password?.[0]" />
    <q-btn type="submit">Login</q-btn>
  </q-form>
</template>
```

---

## **Reusable Frozen Tasks**

Frozen tasks allow developers to define **reusable async logic** inside services, ensuring consistent behavior.

### **Defining a Frozen Task in ExampleService**

```ts
import { LaravelErrorHandler } from 'src/utils/taskUtil';x
import type { BootableService } from 'src/types/service.types';
import type { ServiceContainer } from './ServiceContainer';
import { Service } from './Service';

export class ExampleService extends Service implements BootableService {
  private fetchExampleTask;

  // Register is called after all core services have booted.
  register(container: ServiceContainer): void {
    this.fetchExampleTask = container.task.newFrozenTask<string, { id: number }>({
      showLoading: true,
      showNotification: {
        success: container.i18n.t('example.success.fetched'),
      },
      task: async ({ id }) => await container.api.get(`/example/${id}`),
      errorHandlers: [LaravelErrorHandler()],
    });
  }

  /** Public method to trigger the frozen task */
  fetchExample(id: number) {
    return this.fetchExampleTask.run({ taskPayload: { id } });
  }
}
```

### **Using the Frozen Task in a Vue Component**

```vue
<script setup lang="ts">
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();
const exampleService = container.getService<ExampleService>('exampleService');

async function fetchData() {
  const result = await exampleService?.fetchExample(42);
  console.log('Example Data:', result);
}
</script>

<template>
  <q-btn @click="fetchData">Fetch Example Data</q-btn>
</template>
```

### **Why Use a Frozen Task?**

✅ **Reusable** – The logic is defined **once** inside `ExampleService`, ensuring consistency.  
✅ **Immutable** – Developers **cannot override** critical behaviors (loading state, error handling).  
✅ **Encapsulated API Calls** – All components use the same **structured API request**.  

---
