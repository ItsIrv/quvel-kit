# Task Management

## Overview

The Task Service simplifies asynchronous operations by handling loading states, error handling, success notifications, and lifecycle hooks. It provides a consistent approach to managing API calls and other async processes throughout your application.

## Core Features

- **Managed Async Operations** – Handles loading states, errors, and success cases
- **Automatic Loading Indicators** – Shows and hides loading overlays
- **Error Handling** – Processes errors with customizable handlers, including Laravel validation errors
- **Success Handling** – Processes successful responses with customizable handlers
- **Notifications** – Automatic success/error notifications with customizable messages
- **Conditional Execution** – Tasks can be conditionally executed based on validation or other conditions
- **Mutable Options** – Specific task options can be updated at runtime

## Task Options

The `newTask` method accepts these options:

| Option | Type | Description |
|--------|------|-------------|
| `task` | `Function` | The main async function to execute |
| `taskPayload` | `any` | Data to pass to the task function |
| `showLoading` | `boolean` | Whether to show loading indicator |
| `showNotification` | `Object` | Configure success/error notifications |
| `shouldRun` | `boolean` | Condition that must be true to run |
| `successHandlers` | `Function[]` | Functions to run on success |
| `errorHandlers` | `Function[]` | Functions to run on error |
| `always` | `Function` | Function that runs regardless of outcome |

### Task Return Value

```ts
interface TaskResult<T> {
  // Is the task currently running?
  isActive: ComputedRef<boolean>;
  
  // Run the task with optional custom options
  run: (customOptions?: Partial<TaskOptions<T>>) => Promise<T | false>;
  
  // Reset the task state
  reset: () => void;
  
  // Current task state
  state: Ref<TaskState>; // 'fresh' | 'active' | 'success' | 'error'
  
  // Error if the task failed
  error: Ref<unknown>;
  
  // Laravel validation errors
  errors: Ref<ErrorBag>; // Map<string, string>
  
  // Result of the task
  result: Ref<T | undefined>;
}
```

## Creating Tasks

Tasks are created using the `task` service from the container:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { task } = useContainer();

// Create a task
const fetchDataTask = task.newTask({
  // The main async operation to perform
  task: async () => {
    return await fetch('/api/data').then(res => res.json());
  },
  // Show loading overlay during execution
  showLoading: true,
  // Automatic notifications
  showNotification: {
    success: true, // Use default success message
    error: 'Failed to fetch data' // Custom error message
  },
  // Custom error handlers
  errorHandlers: [task.errorHandlers.Laravel()],
  // Condition to check before running
  shouldRun: async () => true,
  // Always execute this callback
  always: () => console.log('Task completed')
});

// Run the task
const result = await fetchDataTask.run();

// Access task state
console.log(fetchDataTask.isActive.value); // Is the task currently running?
console.log(fetchDataTask.state.value); // 'fresh', 'active', 'success', or 'error'
console.log(fetchDataTask.result.value); // The result of the task
console.log(fetchDataTask.error.value); // The error if the task failed
console.log(fetchDataTask.errors.value); // Error bag for Laravel validation errors
```

### Built-in Error Handlers

The task service includes built-in error handlers for common scenarios:

```ts
// Laravel error handler (processes validation errors)
const laravelErrorHandler = task.errorHandlers.Laravel();

// Create a task with the Laravel error handler
const submitFormTask = task.newTask({
  task: async () => {
    return await api.post('/api/form', formData);
  },
  errorHandlers: [laravelErrorHandler]
});

// Access validation errors from the errors map
const emailError = submitFormTask.errors.value.get('email');

// Check if a field has an error
const hasEmailError = submitFormTask.errors.value.has('email');
```

### Using with Form Components

```vue
<template>
  <q-form @submit.prevent="submitFormTask.run()">
    <EmailField 
      v-model="email"
      :error-message="submitFormTask.errors.value.get('email')"
      :error="submitFormTask.errors.value.has('email')"
    />
    
    <PasswordField 
      v-model="password"
      :error-message="submitFormTask.errors.value.get('password')"
      :error="submitFormTask.errors.value.has('password')"
    />
    
    <q-btn 
      type="submit" 
      label="Submit"
      :loading="submitFormTask.isActive.value"
    />
  </q-form>
</template>
```

## Using Tasks in Components and Stores

Tasks can be used in both components and stores with a similar pattern. The main difference is how you access the container:

- In components: Use the `useContainer()` composable
- In stores: Access via the injected `this.$container` property

### Component Example

```ts
// In components, get the container via the composable
const { task, i18n, api } = useContainer();

// Form data
const email = ref('');
const password = ref('');

// Create login task
const loginTask = task.newTask({
  showNotification: {
    success: () => i18n.t('auth.status.success.loggedIn'),
  },
  task: async () => await api.post('/auth/login', { email, password }),
  errorHandlers: [task.errorHandlers.Laravel()],
  successHandlers: () => {
    $emit('update:modelValue', false);
    resetForms();
  },
});
```

In the template, use the `errors` Map to display validation errors:

```vue
<template>
  <!-- A form that runs the login task when submitted -->
  <q-form @submit.prevent="loginTask.run()">
    <!-- Email field with validation errors -->
    <EmailField 
      v-model="email" 
      :error-message="loginTask.errors.value.get('email')"
      :error="loginTask.errors.value.has('email')"
    />

    <PasswordField 
      v-model="password" 
      :error-message="loginTask.errors.value.get('password')"
      :error="loginTask.errors.value.has('password')"
    />

    <!-- A button that runs the task, shows a loading spinner while it's running, and is disabled while it's running -->
    <q-btn 
      type="submit" 
      :loading="loginTask.isActive.value"
      :disabled="loginTask.isActive.value"
    >
      {{ $t('auth.forms.login.button') }}
    </q-btn>
  </q-form>
</template>
```

### Store Example

In stores, the container is automatically injected as `this.$container`:

```ts
export const someStore = defineStore('someStore', {
  actions: {
    // Store method that runs a task
    async fetchUser(): Promise<void> {
      // In stores, access the container via this.$container
      void this.$container.task.newTask<{ user: IUser }>({
        showLoading: true,
        showNotification: {
          success: () => this.$container.i18n.t('auth.status.success.loggedIn'),
          error: () => this.$container.i18n.t('auth.status.errors.login'),
        },
        task: async (): Promise<{ user: IUser }> =>
          await this.$container.api.get<{ user: IUser }>(
            `/someApi/getUser`,
          ),
        successHandlers: ({ user }): void => {
          // Do something with the user
        },
        errorHandlers: [this.$container.task.errorHandlers.Laravel()],
      })
      .run();
    },
  }
});
```

When designing your application, it's generally better to encapsulate tasks within store methods rather than exposing them directly. This keeps your API clean and implementation details hidden.

## Rules and Gotchas

- Always reset tasks when forms are cleared (`taskInstance.reset()`)
- Use `task.errorHandlers.Laravel()` for Laravel validation errors
- Tasks are stateful - create a new task for each component instance
- Access errors with `errors.value.get('fieldName')` and check existence with `errors.value.has('fieldName')`
- The `run()` method returns the result or `false` if it fails
- In stores, encapsulate API calls in methods rather than exposing tasks directly
- Delegate complex task management to dedicated services

## Source Files

- [TaskService.ts](../../frontend/src/modules/Core/services/TaskService.ts) - Task service implementation
- [task.types.ts](../../frontend/src/modules/Core/types/task.types.ts) - Type definitions
- [errorUtil.ts](../../frontend/src/modules/Core/utils/errorUtil.ts) - Error handling utilities
- [laravel.types.ts](../../frontend/src/modules/Core/types/laravel.types.ts) - Laravel response types

---

[← Back to Frontend Docs](./README.md)
