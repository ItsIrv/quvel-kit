# Component Usage in QuVel Kit

## Overview

This guide explains how to use **QuVel Kit's Service Container** inside Vue components. By leveraging the container, you can **seamlessly integrate API calls, validation, tasks, and translations** in your application.

## Accessing the Service Container

In any Vue component, you can access the **Service Container** using the `useContainer` composable:

```ts
import { useContainer } from 'src/composables/useContainer';
const container = useContainer();
```

Once initialized, you can use its built-in services, such as:

- **API Requests (`container.api`)**
- **Task Management (`container.task`)**
- **Validation (`container.validation`)**
- **Internationalization (`container.i18n`)**

---

## Using Services in Vue Components

### **Example: Login Form with API, Validation, and Tasks**

This example demonstrates how to:

- **Perform API requests** to log in a user.
- **Validate form fields** using `container.validation`.
- **Manage async tasks** with loading indicators.
- **Handle errors gracefully**.
- **Use i18n translations** for success messages.
- **Error handling** using LaravelErrorHandler.
- **Toast Notifications** for success and failure cases.
- **Form resetting** on success.

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import { useSessionStore } from 'src/stores/sessionStore';
import EmailField from 'src/components/Form/EmailField.vue';
import PasswordField from 'src/components/Form/PasswordField.vue';
import { LaravelErrorHandler } from 'src/utils/taskUtil';
import type { ErrorHandler } from 'src/types/task.types';
import type { User } from 'src/models/User';

/** Initialize Services */
const container = useContainer();
const sessionStore = useSessionStore();

/** Form Refs */
const email = ref('');
const password = ref('');
const loginForm = ref<HTMLFormElement>();

/** Define Login Task */
const loginTask = container.task.newTask<User, { email: string, password: string }>({
  shouldRun: async () => await loginForm.value?.validate(),
  showLoading: true,
  showNotification: {
    success: container.i18n.t('auth.status.success.loggedIn'),
  },
  task: async () => await sessionStore.login(email.value, password.value),
  errorHandlers: <ErrorHandler[]>[
    LaravelErrorHandler(),
  ],
  successHandlers: () => {
    email.value = '';
    password.value = '';
  },
});
</script>

<template>
  <q-form ref="loginForm" @submit.prevent="loginTask.run()">
    <EmailField v-model="email"
                :error-message="(loginTask.errors.value.email as any)?.[0] ?? ''"
                :error="!!loginTask.errors.value.email"
    />
    <PasswordField v-model="password"
                   :error-message="(loginTask.errors.value.password as any)?.[0] ?? ''"
                   :error="!!loginTask.errors.value.password"
    />
    <q-btn color="primary" type="submit">{{ $t('auth.forms.login.button') }}</q-btn>
  </q-form>
</template>
```

---

## Working with API Requests in Components

Instead of calling Axios directly, use `container.api` for API requests.

```ts
const fetchUserData = async () => {
  try {
    const user = await container.api.get<User>('/user');
    console.log('User data:', user);
  } catch (error) {
    console.error('Failed to fetch user:', error);
  }
};
```

### **Example: Fetching Data on Mount**

```vue
<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import type { User } from 'src/models/User';

const container = useContainer();
const user = ref<User | null>(null);

onMounted(async () => {
  user.value = await container.api.get<User>('/user');
});
</script>

<template>
  <div v-if="user">
    <p>Welcome, {{ user.name }}</p>
  </div>
</template>
```

---

## Using Translations (i18n) in Components

Access **localized translations** inside components using `container.i18n`.

```vue
<template>
  <p>{{ $t('auth.welcomeMessage', { name: user?.name }) }}</p>
</template>
```

Using translations inside scripts:

```ts
const translatedMessage = container.i18n.t('auth.status.success.loggedIn');
console.log(translatedMessage);
```

---

## Best Practices for Using Services in Vue

- **Use the Service Container (`useContainer`) instead of direct imports** for maintainability.
- **Keep API calls inside `container.api` to maintain global handling (cookies, errors, etc.).**
- **Use `container.task` to handle async logic** and improve error resilience.
- **Leverage `container.i18n` for easy internationalization** across components.
- **Ensure validation logic is reusable using `container.validation` and schema definitions.**

---

## Summary

- **Service Container (`useContainer`)** provides access to API, validation, tasks, and translations.
- **Async API tasks are handled efficiently using `container.task`.**
- **Validation and i18n are seamlessly integrated into Vue components.**
- **Encapsulated logic inside the container makes components modular and testable.**

By following these principles, **QuVel Kit components remain clean, scalable, and maintainable.**
