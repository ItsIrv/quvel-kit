# Task Management

## Overview

The Task Service simplifies asynchronous operations by handling loading states, error handling, success notifications, and lifecycle hooks. It provides a consistent approach to managing API calls and other async processes throughout your application.

## Key Features

- **State Management** - Tracks loading, success, and error states
- **Automatic Loading Indicators** - Shows and hides loading spinners
- **Error Handling** - Built-in Laravel validation error extraction
- **Success/Error Notifications** - Configurable toast messages
- **Reactive Properties** - Vue-compatible reactive state

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

## Task States

Each task has reactive state properties you can use in your components:

| Property | Type | Description |
|----------|------|-------------|
| `isActive` | `ComputedRef<boolean>` | Whether the task is currently running |
| `state` | `Ref<'fresh' \| 'active' \| 'success' \| 'error'>` | Current state of the task |
| `error` | `Ref<unknown>` | The error if one occurred |
| `errors` | `Ref<ErrorBag>` | Validation errors (Map of field to error message) |
| `result` | `Ref<Result \| undefined>` | The result of the task |
| `reset` | `Function` | Method to reset the task state |
| `run` | `Function` | Method to execute the task |

## Creating Tasks

Tasks are created using the `task` service from the container:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { task } = useContainer();

// Create a basic task
const loginTask = task.newTask({
  // The main function to execute
  task: async () => {
    return await api.post('/auth/login', { email, password });
  },
  
  // Show loading indicator while running
  showLoading: true,
  
  // Show success notification when complete
  showNotification: {
    success: 'Login successful',
  },
  
  // Handle errors (Laravel validation errors)
  errorHandlers: [task.errorHandlers.Laravel()],
  
  // Execute after success
  successHandlers: (result) => {
    // Do something with the result
  },
});

// Run the task
await loginTask.run();
```

## Error Handling

The task service includes built-in error handling for Laravel validation errors:

```ts
// Create a task with Laravel error handling
const submitTask = task.newTask({
  task: async () => { /* API call */ },
  errorHandlers: [task.errorHandlers.Laravel()],
});

// After an error occurs, validation errors are available in a Map
if (submitTask.errors.value.has('email')) {
  console.log(submitTask.errors.value.get('email')); // "The email field is required"
}
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

- [TaskService.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/services/TaskService.ts) - Task service implementation
- [task.types.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/types/task.types.ts) - Type definitions
- [errorUtil.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/utils/errorUtil.ts) - Error handling utilities
- [laravel.types.ts](/Users/irv/Workspace/pdxapps.com/repos/quvel-kit/frontend/src/modules/Core/types/laravel.types.ts) - Laravel response types

---

[← Back to Frontend Docs](./README.MD)
