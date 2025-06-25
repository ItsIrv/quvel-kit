# Module Registry System

The QuVel Kit frontend uses a module registry system that lets you organize your app into self-contained modules. Each module can provide routes, translations, services, boot files, and CSS.

## How It Works

The system has four main parts:

1. **Module Registry** (`src/modules/moduleRegistry.ts`) - Functions that collect resources from all modules
2. **Module Interface** (`src/modules/Core/types/module.types.ts`) - TypeScript contract defining what modules can provide
3. **Module Config** (`src/config/modules.ts`) - Where you register new modules
4. **Individual Modules** - Your actual feature modules

## Creating a Module

### 1. Create the module structure

```
src/modules/MyModule/
├── index.ts              # Module loader (required)
├── boot/                 # Boot files (optional)
├── css/                  # Stylesheets (optional)
├── router/               # Routes (optional)
├── i18n/                 # Translations (optional)
└── services/             # Service classes (optional)
```

### 2. Create the module loader

```typescript
// src/modules/MyModule/index.ts
import type { ModuleLoader } from 'src/modules/Core/types/module.types';
import { moduleResource } from 'src/modules/moduleUtils';

export const MyModule: ModuleLoader = {
  routes: () => [
    {
      path: '/my-feature',
      component: () => import('./pages/MyPage.vue')
    }
  ],

  i18n: () => ({
    'en-US': { myModule: { title: 'My Module' } }
  }),

  build: (ctx) => ({
    boot: [moduleResource('MyModule', 'boot/setup')],
    css: [moduleResource('MyModule', 'css/styles.scss')],
    plugins: ['Dialog'],
    animations: ['fadeIn']
  })
};
```

### 3. Register the module

```typescript
// src/config/modules.ts
import { MyModule } from 'src/modules/MyModule';

export function getAllModules() {
  return {
    MyModule: () => MyModule,  // Add here
  };
}
```

## Path Helper Functions

Quasar requires specific relative paths for boot files and CSS. Use the helper functions:

```typescript
import { moduleResource, moduleResources } from 'src/modules/moduleUtils';

// Single resource
moduleResource('MyModule', 'boot/setup')
// Returns: '../modules/MyModule/boot/setup'

// Multiple resources
moduleResources('MyModule', ['boot/setup', 'boot/config'])
// Returns: ['../modules/MyModule/boot/setup', '../modules/MyModule/boot/config']
```

## Registry Functions

### Routes

```typescript
import { getModuleRoutes } from 'src/modules/moduleRegistry';
const allRoutes = getModuleRoutes(); // Gets routes from all modules
```

### Translations

```typescript
import { getModuleI18n } from 'src/modules/moduleRegistry';
const translations = getModuleI18n('en-US'); // Gets English translations from all modules
```

### Services

```typescript
import { getModuleServices } from 'src/modules/moduleRegistry';
const services = getModuleServices(); // Gets services from all modules for DI
```

### Build Config

```typescript
import { getBuildConfig } from 'src/modules/moduleRegistry';
const config = getBuildConfig(ctx); // Merges build configs from all modules
```

## Context-Aware Configuration

Use the Quasar context to conditionally load resources:

```typescript
build: (ctx) => {
  const isSSR = ctx?.modeName === 'ssr';
  const isDev = ctx?.dev;
  
  return {
    boot: [
      moduleResource('MyModule', 'boot/base'),
      ...(isSSR ? [moduleResource('MyModule', 'boot/ssr-only')] : []),
      ...(isDev ? [moduleResource('MyModule', 'boot/dev-tools')] : [])
    ],
    css: [moduleResource('MyModule', 'css/main.scss')]
  };
}
```

## Boot Files

Boot files run during app startup. Create them in your module's `boot/` folder:

```typescript
// src/modules/MyModule/boot/setup.ts
import { boot } from 'quasar/wrappers';

export default boot(({ app }) => {
  // Initialize your module
  console.log('MyModule initialized');
});
```

### Boot file options

```typescript
// Client-only
{ path: moduleResource('MyModule', 'boot/client-only'), server: false }

// Server-only  
{ path: moduleResource('MyModule', 'boot/server-only'), client: false }
```

## Common Patterns

### Service Registration

```typescript
// In your module
services: () => ({
  MyService: MyServiceClass
})

// Use anywhere in app
const myService = container.get('MyService');
```

### Conditional Loading

```typescript
build: (ctx) => ({
  boot: [
    ...(process.env.FEATURE_FLAG === 'true' ? [moduleResource('MyModule', 'boot/feature')] : [])
  ]
})
```

### CSS Organization

```typescript
build: () => ({
  css: [
    moduleResource('MyModule', 'css/base.scss'),
    moduleResource('MyModule', 'css/components.scss')
  ]
})
```

## Troubleshooting

**Module not loading**: Check it's registered in `src/config/modules.ts`

**Boot file errors**: Verify the path using `moduleResource()` helper

**CSS not applying**: Ensure SCSS files exist and paths are correct

**Type errors**: Make sure your module implements the `ModuleLoader` interface

## Key Points

1. Use `moduleResource()` for all boot and CSS paths
2. Order matters for boot files - critical ones first
3. Modules can be conditional based on build context
4. Failed modules don't break the app
5. Check console for module loading errors
