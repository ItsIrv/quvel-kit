# Validation

## Overview

QuVel Kit provides a comprehensive **schema-based validation** system using **Zod** that integrates with **i18n** for localized error messages. The validation system is designed to work seamlessly with Quasar form components through a set of reusable field components and validation schemas.

## Features

- **Zod Schema Validation** – Type-safe validation rules with strong TypeScript integration
- **Reusable Form Components** – Pre-built field components with built-in validation
- **Internationalized Error Messages** – Automatic translation of validation errors
- **Form-Level Validation** – Support for cross-field validation rules
- **API Response Validation** – Validate data from API responses

---

## Validation Architecture

QuVel Kit's validation system consists of several key components:

1. **ValidationService** – Core service for validating data against Zod schemas
2. **Reusable Form Components** – Pre-built field components with built-in validation
3. **Validation Schemas** – Reusable Zod schemas for common validation patterns
4. **Form-Level Validators** – Schemas for validating entire forms with cross-field validation

### Validation Service API

The validation service is accessible through the service container:

```ts
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();
```

#### Validating Single Fields

```ts
import { emailSchema } from 'src/modules/Core/utils/validators/commonValidators';

// Returns true if valid, or the first error message if invalid
const result = container.validation.validateFirstError('user@example.com', emailSchema(), 'Email');

// Returns an array of error messages, or empty array if valid
const errors = container.validation.validateAllErrors('invalid', emailSchema(), 'Email');
```

#### Creating Quasar Form Rules

```ts
// The validation service provides a helper to create Quasar-compatible form rules
const emailRule = container.validation.createInputRule(emailSchema(), 'Email');

// Use in a Quasar form
<q-input v-model="email" :rules="[emailRule]" />
```

---

## Reusable Form Components

QuVel Kit includes pre-built form components with built-in validation:

### BaseField Component

The `BaseField` component is the foundation for all form inputs:

```vue
<BaseField
  v-model="fieldValue"
  label="Field Label"
  :schema="zodSchema()"
  :error-message="errorMessage"
  :error="hasError"
/>
```

The component automatically creates validation rules from the provided Zod schema and integrates with Quasar's form validation system.

### Specialized Field Components

QuVel Kit provides specialized field components for common input types:

```vue
<!-- Email field with built-in validation -->
<EmailField
  v-model="email"
  :error-message="errorMessage"
  :error="hasError"
/>

<!-- Password field with built-in validation -->
<PasswordField
  v-model="password"
  :error-message="errorMessage"
  :error="hasError"
/>

<!-- Password confirmation field with match validation -->
<PasswordConfirmField
  v-model="passwordConfirm"
  :password-value="password"
/>
```

---

## Validation Schemas

QuVel Kit includes reusable validation schemas for common data types:

### Common Validators

```ts
import { 
  emailSchema, 
  passwordSchema, 
  nameSchema 
} from 'src/modules/Core/utils/validators/commonValidators';

// Email validation (valid email format)
const email = emailSchema();

// Password validation (min 8, max 100 characters)
const password = passwordSchema();

// Name validation (min 2, max 30 characters)
const name = nameSchema();
```

### Form-Level Validators

For validating entire forms with cross-field validation:

```ts
import { registerSchema } from 'src/modules/Core/utils/validators/authValidators';

// Validate registration form with password confirmation
const formData = {
  email: 'user@example.com',
  password: 'password123',
  confirmPassword: 'password123'
};

const result = registerSchema().safeParse(formData);

if (!result.success) {
  console.log(result.error.errors);
  // Might show: [{ path: ['confirmPassword'], message: 'Passwords do not match' }]
}
```

---

## Practical Examples

### Basic Form Validation

```vue
<script setup lang="ts">
import { ref } from 'vue';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';
import PasswordConfirmField from 'src/modules/Auth/components/Form/PasswordConfirmField.vue';

const email = ref('');
const password = ref('');
const passwordConfirm = ref('');

function onSubmit() {
  // Form validation is handled by the field components
  console.log('Form submitted');
}
</script>

<template>
  <q-form @submit.prevent="onSubmit">
    <EmailField v-model="email" />
    <PasswordField v-model="password" />
    <PasswordConfirmField 
      v-model="passwordConfirm" 
      :password-value="password" 
    />
    
    <q-btn type="submit" label="Submit" />
  </q-form>
</template>
```

### Using with Task Service

Integrating validation with the task service for API requests:

```ts
import { ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import { loginSchema } from 'src/modules/Core/utils/validators/authValidators';

const container = useContainer();
const email = ref('');
const password = ref('');

// Create a login task with validation
const loginTask = container.task.newTask({
  shouldRun: async () => {
    // Validate the entire form
    const result = loginSchema().safeParse({
      email: email.value,
      password: password.value
    });
    
    return result.success;
  },
  task: async () => {
    return await container.api.post('/auth/login', {
      email: email.value,
      password: password.value
    });
  },
  errorHandlers: [container.task.errorHandlers.Laravel()]
});

// Run the task
function login() {
  loginTask.run();
}
```

### Handling API Validation Errors

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';
import EmailField from 'src/modules/Auth/components/Form/EmailField.vue';
import PasswordField from 'src/modules/Auth/components/Form/PasswordField.vue';

const container = useContainer();
const email = ref('');
const password = ref('');

// Create login task with Laravel error handling
const loginTask = container.task.newTask({
  task: async () => {
    return await container.api.post('/auth/login', {
      email: email.value,
      password: password.value
    });
  },
  errorHandlers: [container.task.errorHandlers.Laravel()]
});
</script>

<template>
  <q-form @submit.prevent="loginTask.run()">
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
    
    <q-btn 
      type="submit" 
      label="Login"
      :loading="loginTask.isActive.value"
    />
  </q-form>
</template>
```

---

## Creating Custom Validators

You can create custom validators using Zod schemas:

```ts
import { z } from 'zod';

// Phone number validation
export const phoneSchema = () => 
  z.string()
    .regex(/^\+?[1-9]\d{1,14}$/, 'Invalid phone number format');

// Date validation
export const dateSchema = () => 
  z.string()
    .refine((value) => !isNaN(Date.parse(value)), {
      message: 'Invalid date format'
    });

// Custom field component using the schema
export const PhoneField = defineComponent({
  // Component implementation
});
```

### Creating Form-Level Validators

```ts
import { z } from 'zod';
import { emailSchema, passwordSchema } from './commonValidators';

// Form with cross-field validation
export const changePasswordSchema = () =>
  z.object({
    currentPassword: passwordSchema(),
    newPassword: passwordSchema(),
    confirmPassword: z.string().min(8).max(100),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'],
  })
  .refine((data) => data.currentPassword !== data.newPassword, {
    message: 'New password must be different from current password',
    path: ['newPassword'],
  });
```

---

## Rules and Gotchas

### Best Practices

- **Use Field Components** – Leverage the pre-built field components for consistent validation
- **Form-Level Validation** – Use form-level schemas for cross-field validation
- **Lazy Translation Loading** – Pass i18n as a callback to task notifications for lazy loading
- **Error Handling** – Use the `errorHandlers.Laravel` for API validation errors

### Common Pitfalls

- **Missing Schema Parameters** – Remember to call schema functions with parentheses: `emailSchema()`
- **Forgetting Error Props** – Always pass both `:error` and `:error-message` to field components
- **Translation Keys** – Ensure validation message keys exist in your i18n files
- **Task Error Mapping** – Use `errors.value.get('field')` to access Laravel validation errors

---

## Source Files

- **[ValidationService.ts](../frontend/src/modules/Core/services/ValidationService.ts)** – Core validation service
- **[BaseField.vue](../frontend/src/modules/Core/components/Form/BaseField.vue)** – Base field component
- **[EmailField.vue](../frontend/src/modules/Auth/components/Form/EmailField.vue)** – Email field component
- **[commonValidators.ts](../frontend/src/modules/Core/utils/validators/commonValidators.ts)** – Common validation schemas
- **[authValidators.ts](../frontend/src/modules/Core/utils/validators/authValidators.ts)** – Auth form validators

---

[← Back to Frontend Docs](./README.MD)
