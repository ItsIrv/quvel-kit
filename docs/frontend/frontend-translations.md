# Translations & Internationalization

## Overview

QuVel Kit provides **built-in internationalization (i18n)** support using **Vue I18n**. This allows seamless localization of UI components, validation messages, and notifications.

## Features

- **Vue I18n Integration** – QuVel Kit uses Vue I18n for translations.
- **Service-Based Access** – Use `container.i18n` for translations in services and components.
- **Validation & Form Errors** – Automatically translates validation errors.
- **Language Switching & Persistence** – Users can switch languages, and preferences are stored.

---

## Setting Up Vue I18n

QuVel Kit initializes Vue I18n as part of the **Service Container**. The service is automatically available inside Vue components and services.

```ts
import { createI18n } from 'vue-i18n';

const messages = {
  en: { welcome: 'Welcome to QuVel Kit', auth: { login: 'Login' } },
  fr: { welcome: 'Bienvenue sur QuVel Kit', auth: { login: 'Connexion' } },
};

export const i18n = createI18n({
  locale: 'en', // Default language
  messages,
});
```

### **Accessing Translations**

Translations can be accessed using the service container.

```ts
const container = useContainer();
console.log(container.i18n.t('welcome')); // Output: "Welcome to QuVel Kit"
```

You can also use `$t` inside Vue templates:

```vue
<p>{{ $t('welcome') }}</p>
```

---

## Using Translations in Services & Components

The `i18n` service is available in the **Service Container**, allowing translations in any service.

```ts
export class ExampleService {
  constructor(private container: ServiceContainer) {}

  sayHello() {
    return this.container.i18n.t('welcome');
  }
}
```

---

## Translating Form Errors & Validation Messages

Validation messages are translated automatically using Vue I18n.

### **Example: Translating Form Errors**

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

## Language Switching & Persistence

QuVel Kit allows users to switch languages dynamically, and it persists preferences using cookies.

### **Saving Language Preference**

I recommend using the `LanguageSwitcher.vue` component for this purpose, but feel free to check out the code
and call the functions manually. Check `i18nUtil` for more details.

---
