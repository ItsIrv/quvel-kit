# Frontend Session Management

## Overview

QuVel Kit uses **Pinia** for session management, enabling **state persistence** across SSR and client-side navigation. The session store provides authentication tracking, user details, and integration with the **Service Container**.

## Features

- **Centralized Session State** – Manages user authentication and session details.
- **SSR Support** – Session state is properly hydrated between server and client.
- **Integrated with Service Container** – Access API, validation, and tasks inside stores.
- **Persistent State** – Session data remains available across page reloads.

---

## Using the Session Store

The session store is accessed using `useSessionStore()`:

```ts
import { useSessionStore } from 'src/stores/sessionStore';

const sessionStore = useSessionStore();
```

### **Checking Authentication State**

```ts
if (sessionStore.isAuthenticated) {
  console.log('User is logged in:', sessionStore.user);
}
```

---

## **Logging In & Out**

### **Login Example**

```ts
await sessionStore.login('test@example.com', 'password123');
console.log(sessionStore.isAuthenticated); // true
```

### **Logout Example**

```ts
await sessionStore.logout();
console.log(sessionStore.isAuthenticated); // false
```

---

## **Using the Service Container Inside Pinia Stores**

Pinia stores in QuVel Kit have **direct access** to the **Service Container**, allowing API calls, validation, and task management.

### **Example: Calling an API Inside the Store**

```ts
import { defineStore } from 'pinia';

export const useExampleStore = defineStore('example', {
  actions: {
    async fetchData() {
      const data = await this.$container.api.get('/example-endpoint');
      console.log('Fetched data:', data);
    },
  },
});
```

### **Example: Using Validation & Tasks Inside the Store**

```ts
export const useExampleStore = defineStore('example', {
  actions: {
    async submitForm(formData) {
      const isValid = this.$container.validation.validate(formData.email, emailSchema(), 'Email');
      if (!isValid) return;
      
      await this.$container.task.newTask({
        task: async () => await this.$container.api.post('/submit', formData),
      }).run();
    },
  },
});
```

---

## **Session State in Vue Components**

### **Using `sessionStore` in a Component**

```vue
<script setup lang="ts">
import { useSessionStore } from 'src/stores/sessionStore';

const sessionStore = useSessionStore();
</script>

<template>
  <div>
    <p v-if="sessionStore.isAuthenticated">
      Logged in as {{ sessionStore.user?.name }}
    </p>
    <q-btn v-if="sessionStore.isAuthenticated" @click="sessionStore.logout()">
      Logout
    </q-btn>
  </div>
</template>
```

---
