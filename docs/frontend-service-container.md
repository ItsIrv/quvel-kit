# Frontend Services & Utilities

QuVel Kit provides a set of frontend **services and utilities** designed for **isomorphic (client & server-safe) development**. These services are injected via the **Service Container**, ensuring efficient management of common logic. The utils can be found in `frontend/src/utils`.

---

## **Accessing Services**

All services are accessible using the **Service Container**, which is available globally.

```ts
import { useContainer } from 'src/services/ContainerService';

const container = useContainer();
```

This allows access to utilities such as:

- **Task Management (`container.task`)**
- **Validation (`container.validation`)**
- **API Requests (`container.api`)**
- **Internationalization (`container.i18n`)**

---

## **Task Service (`container.task`)**

### **Managing Async Operations**

QuVel Kit introduces a powerful **Task Service** for **handling asynchronous operations**

## **Using Services in Vue Components**

### **Example: Login Form**

Here is an example task that:

- Only runs when the login form passes validation.
- Shows a loading layover.
- Shows a success or error notification.
- Returns the result of the task call in run(), success handlers, and in { result }.
- Keeps track of task status with { state: 'fresh' | 'active' | 'success' | 'error' }
- Parses the Laravel error response and stores them into an { errors } object.
- Stores full error from catch in { error } object.
- Can be localized.
- Can be reused.
- Typed payload and result for linting.

Note that this example explicitly uses all of the features with the "expanded" version to showcase
flexibility and ability. This can be compacted into a lot less lines and verbosity.

```vue
<script setup lang="ts">
import { useContainer } from 'src/services/ContainerService';
import { User } from 'src/types/auth.types';

const container = useContainer();

const loginTask = container.task.newTask<User, { email: string, password: string }>({
  shouldRun: async () => await loginForm.value?.validate(),
  showLoading: true,
  showNotification: {
    error: false,
    success: container.i18n.t('auth.success.loggedIn'),
  },
  taskPayload: () => ({ email: email.value, password: password.value }),
  task({ email, password }) {
    return sessionStore.login(email, password);
  },
  always() {
    // inside finally {}
  },
  errorHandlers: <ErrorHandler[]>[
    LaravelErrorHandler(),
    {
      key: 'status',
      matcher: (status: number): boolean => status === 400,
      callback(): void {
        console.log('Bad Request')
      }
    }
  ],
  successHandlers: <SuccessHandler[]>[
    {
      callback(): void {
        email.value = '';
        password.value = '';
      }
    },
    {
      callback(user: User): void {
        console.log(user)
      }
    }
  ],
});

// await task.run();

// console.log(task.errors.value)
</script>

<template>
  <q-form
    ref="loginForm"
    @submit.prevent="loginTask.run()"
  >
    <EmailField
      v-model="email"
      :error-message="(loginTask.errors.value.email as any)?.[0] ?? ''"
      :error="!!loginTask.errors.value.email"
    />

    <PasswordField
      v-model="password"
      :error-message="(loginTask.errors.value.password as any)?.[0] ?? ''"
      :error="!!loginTask.errors.value.password"
    />

    <q-btn
      color="primary"
      class="q-mt-md"
      type="submit"
    >
      {{ $t('auth.forms.login.button') }}
    </q-btn>
  </q-form>
</template>
```

---

## **Validation Service (`container.validation`)**

### **Schema-Based Form Validation**

QuVel Kit includes **Zod-based validation** with **i18n translations**.

### **Using the Validation Service**

```ts
import { emailSchema } from 'src/utils/validators/commonValidators';

container.validation.validate('test@example.com', emailSchema(), 'Email');
```

### **Example: Validating Fields**

```vue
<BaseField
  v-model="email"
  :label="$t('auth.forms.common.email')"
  name="email"
  type="email"
  :schema="emailSchema()"
  :error-message="errorMessage" 
  :error="error"
></BaseField>
```

---

## **Session Management**

QuVel Kit uses **Pinia for session management**, allowing **isomorphic** state access.

### **Example: Login Handling**

```ts
const sessionStore = useSessionStore();

await sessionStore.login('test@example.com', 'password123');

console.log(sessionStore.isAuthenticated); // true
```

### **Example: Logout Handling**

```ts
await sessionStore.logout();
console.log(sessionStore.isAuthenticated); // false
```

### **Pinia & Service Container Integration**

For developers who prefer **Pinia-based architecture**, the **session store has direct access to the Service Container**:

```ts
// Pinia store
actions: {
    async doSomething(): Promise<void> {
      // this.$container.validation.validate()
      // this.$container.i18n.t()
      // this.$container.task.newTask()
      const data = await this.$container.api.get('/quvel');
    }
  },
}
```

This means **API calls, validation, and task handling** can be accessed inside **Pinia stores**.

---

## **Laravel Response Error Handling (`LaravelErrorHandler`)**

The `LaravelErrorHandler` **automatically extracts errors from Laravel API responses**.

### **Example: Adding to Task**

```ts
const myTask = container.task.newTask({
  task: async () => await container.api.get('/endpoint'),
  errorHandlers: [LaravelErrorHandler()],
});
```

### **How It Works**

- **Extracts error messages from Laravel API responses**
- **Supports multiple error fields (`errors` object)**
- **Can be localized (`i18n`)**
