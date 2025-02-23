# Frontend Validation

## Overview

The **Validation Service** in QuVel Kit provides **schema-based validation** using **Zod**, ensuring form validation is both **strongly typed** and **internationalized (i18n-supported)**.

## Features

- **Zod Schema Validation**: Leverages Zod for defining validation rules.
- **Error Handling**: Automatically extracts and formats error messages.
- **Component Integration**: Easily integrates with Vue forms.
- **i18n Support**: Enables multi-language validation messages.
- **Reusable Validation Rules**: Create once, use across multiple components.

---

## Using the Validation Service

To access the **Validation Service**, retrieve the service container:

```ts
import { useContainer } from 'src/composables/useContainer';
const container = useContainer();
```

### **Validating a Single Value**

```ts
import { emailSchema } from 'src/utils/validators/commonValidators';

const result = container.validation.validate('test@example.com', emailSchema(), 'Email');
console.log(result); // "true" if valid, otherwise an error message
```

---

## Creating Validation Rules

QuVel Kit enables defining **reusable validation rules** using Zod.

### **Example: Email Validation Schema**

```ts
import { z } from 'zod';

export const emailSchema = () => z.string().email();
```

### **Example: Password Validation Schema**

```ts
export const passwordSchema = () =>
  z.string()
    .min(8,)
    .max(32);
```

---

## Form Validation in Vue Components

### **Example: Validating Input Fields**

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import { emailSchema } from 'src/utils/validators/commonValidators';

const container = useContainer();
const email = ref('');
const errorMessage = ref('');

const validateEmail = () => {
  const result = container.validation.validate(email.value, emailSchema(), 'Email');
  errorMessage.value = result === true ? '' : result;
};
</script>

<template>
  <q-input v-model="email" label="Email" @blur="validateEmail" :error-message="errorMessage" />
</template>
```

---
