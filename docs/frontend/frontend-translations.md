# Translations & Internationalization

## Overview

QuVel Kit provides internationalization (i18n) support using **Vue I18n**. The i18n service is available through the service container and integrates with validation, notifications, and UI components to provide a consistent multilingual experience.

## Features

- **Vue I18n Integration** – Full access to Vue I18n capabilities
- **Service Container Access** – Available via `container.i18n`
- **Locale Persistence** – User language preference stored in cookies
- **Validation Integration** – Automatic translation of validation messages
- **SSR Support** – Works in both server and client environments

---

## Using the I18n Service

The I18n service is accessible through the service container and provides methods for translating messages and managing locales.

### In Components

```ts
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();

// Translate a message
const welcomeMessage = container.i18n.t('common.welcome');

// Translate with parameters
const greeting = container.i18n.t('common.greeting', { name: 'John' });

// Check if a translation exists
const hasTranslation = container.i18n.te('common.welcome');
```

### In Templates

In Vue templates, you can use the global `$t` function:

```vue
<template>
  <h1>{{ $t('common.welcome') }}</h1>
  <p>{{ $t('common.greeting', { name: userName }) }}</p>
  
  <!-- Conditional rendering based on translation existence -->
  <div v-if="$te('features.new')" class="new-badge">
    {{ $t('common.new') }}
  </div>
</template>
```

---

## Changing Locales

The I18n service provides methods for changing and persisting the user's locale preference.

```ts
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();

// Change the locale
function switchLanguage(locale: string) {
  container.i18n.changeLocale(locale);
  // The locale is automatically stored in cookies
}
```

### Language Switcher Component

```vue
<script setup lang="ts">
import { ref } from 'vue';
import { useContainer } from 'src/composables/useContainer';

const container = useContainer();
const currentLocale = ref(container.i18n.instance.global.locale.value);

function changeLocale(locale: string) {
  container.i18n.changeLocale(locale);
  currentLocale.value = locale;
}
</script>

<template>
  <div class="language-switcher">
    <q-btn-dropdown :label="$t(`locales.${currentLocale}`)">
      <q-list>
        <q-item clickable v-close-popup @click="changeLocale('en-US')">
          <q-item-section>{{ $t('locales.en-US') }}</q-item-section>
        </q-item>
        <q-item clickable v-close-popup @click="changeLocale('es-MX')">
          <q-item-section>{{ $t('locales.es-MX') }}</q-item-section>
        </q-item>
      </q-list>
    </q-btn-dropdown>
  </div>
</template>
```

---

## Translation Organization

QuVel Kit organizes translations in a modular structure that makes it easy to extend and maintain.

### Translation Files Structure

Translations are organized in a hierarchical structure:

```text
src/
├── i18n/                          # Main i18n directory
│   ├── index.ts                   # Exports all translations
│   ├── en-US.ts                   # English translations (imports core + adds app-specific)
│   └── es-MX.ts                   # Spanish translations (imports core + adds app-specific)
│
└── modules/                       # Module-specific translations
    ├── Core/i18n/                 # Core module translations
    │   ├── en-US/                 # English translations for core
    │   │   ├── index.ts           # Combines all core English translations
    │   │   ├── common.ts          # Common UI elements
    │   │   ├── validation.ts      # Validation messages
    │   │   └── ...
    │   └── es-MX/                 # Spanish translations for core
    │
    └── Auth/i18n/                 # Auth module translations
        └── en-US/
            └── auth.ts            # Auth-specific translations
```

### Extending Translations

To add your own translations, modify the language files in the main `i18n` directory:

```ts
// src/i18n/en-US.ts
import coreTranslations from 'src/modules/Core/i18n/en-US';

// Add your custom translations here
const appTranslations = {
  app: {
    name: 'My App',
    features: {
      dashboard: 'Dashboard',
      reports: 'Reports'
    }
  }
};

export default {
  ...coreTranslations,  // Include all core translations
  ...appTranslations     // Add your custom translations
} as const;
```

## Adding New Languages

To add a new language to your application:

1. Create a new language file in the `src/i18n` directory:

```ts
// src/i18n/fr-FR.ts
import coreTranslations from 'src/modules/Core/i18n/fr-FR'; // You'll need to create this

const appTranslations = {
  app: {
    name: 'QuVel Kit',
    version: 'v1.0.0',
  },
};

export default {
  ...coreTranslations,
  ...appTranslations,
} as const;
```

2. Update the supported locales in `i18nUtil.ts`:

```ts
const SUPPORTED_LOCALES: MessageLanguages[] = ['en-US', 'es-MX', 'fr-FR'];
```

3. Import and add the new language in `src/i18n/index.ts`:

```ts
import enUS from './en-US';
import esMX from './es-MX';
import frFR from './fr-FR';

export default {
  'en-US': enUS,
  'es-MX': esMX,
  'fr-FR': frFR,
} as const;
```

## Integration with Validation

The validation service automatically uses the i18n service to translate validation error messages:

```ts
import { useContainer } from 'src/composables/useContainer';
import { emailSchema } from 'src/modules/Core/utils/validators/commonValidators';

const container = useContainer();

// Validation errors will be translated using the current locale
const result = container.validation.validateFirstError('invalid', emailSchema(), 'Email');
// Result might be: "Email must be a valid email address"
```

### Validation Error Messages

Validation error messages are defined in the core translations under the `validation` key (in `modules/Core/i18n/en-US/validation.ts`):

```ts
export default {
  invalid_type: '{attribute} must be a {expectedType}',
  minLength: '{attribute} must be at least {min} characters',
  maxLength: '{attribute} must not exceed {max} characters',
  email: '{attribute} must be a valid email address',
  // ... other validation messages
};
```

---

## Rules and Gotchas

### Best Practices

- **Use Hierarchical Keys** – Organize translations by feature and purpose
- **Provide Default Translations** – Always include fallback translations for all keys
- **Use Parameters** – Use placeholders like `{name}` for dynamic content
- **Consistent Naming** – Follow a consistent naming convention for translation keys
- **Modular Organization** – Keep module-specific translations within their modules

### Common Pitfalls

- **Missing Translations** – Ensure all keys exist in all language files
- **Hardcoded Strings** – Avoid hardcoding text that should be translated
- **Forgetting Context** – Some languages may need context for proper translation
- **Not Handling Plurals** – Use Vue I18n's plural features for count-dependent text
- **Tasks Notifications** - Ensure you pass i18n as a resolvable callback, to lazy load the translation. Otherwise many translations will be loaded at startup.

## Source Files

- **[I18nService.ts](../frontend/src/modules/Core/services/I18nService.ts)** – Core i18n service
- **[i18nUtil.ts](../frontend/src/modules/Core/utils/i18nUtil.ts)** – Utility functions for i18n
- **[i18n/index.ts](../frontend/src/i18n/index.ts)** – Main translations index

---

[← Back to Frontend Docs](./README.MD)
