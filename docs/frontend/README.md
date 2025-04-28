# QuVel Kit Frontend Documentation

## Overview

The QuVel Kit frontend is built with Vue 3, TypeScript, and Quasar 2 in SSR mode, providing an enterprise-ready foundation for web applications. This documentation covers the architecture, services, and development patterns used throughout the frontend codebase.

## Technology Stack

| Technology | Purpose | Version |
|------------|---------|--------|
| Vue 3 | UI Framework | 3.x |
| TypeScript | Type Safety | 5.x |
| Quasar 2 | UI Components & SSR | 2.x |
| Pinia | State Management | 2.x |
| Tailwind CSS | Utility Styling | 3.x |
| Zod | Schema Validation | 3.x |
| Axios | HTTP Client | 1.x |
| Laravel Echo | WebSockets | 1.x |

## Architecture

The frontend follows a modular architecture with a service-oriented approach:

```text
frontend/
├── src/
│   ├── modules/         # Feature modules
│   │   ├── Core/        # Core functionality
│   │   ├── Auth/        # Authentication
│   │   ├── User/        # User management
│   │   └── ...          # Other feature modules
│   ├── boot/            # Quasar boot files
│   ├── i18n/            # Translations
│   └── composables/     # Shared Vue composables
└── src-ssr/            # Server-side rendering code
```

## Documentation

### Core Services

- **[Service Container](./frontend-service-container.md)** - Dependency injection system
- **[Configuration Service](./frontend-config-service.md)** - Application settings with tiered visibility
- **[Task Management](./frontend-task-management.md)** - Async operation orchestration

### State Management

- **[State Management](./frontend-state-management.md)** - Pinia store patterns
- **[Pagination](./frontend-pagination.md)** - Data pagination strategies

### UI & Interaction

- **[Validation](./frontend-validation.md)** - Form and data validation with Zod
- **[Translations](./frontend-translations.md)** - Internationalization with Vue I18n
- **[WebSockets](./frontend-websockets.md)** - Real-time communication

### Development

- **[Environment Setup & Usage](./frontend-usage.md)** - Development workflow

## Key Concepts

### Service Container

The service container is the central piece of the frontend architecture. It provides access to core services:

```ts
import { useContainer } from 'src/modules/Core/composables/useContainer';

// In Vue components
const { api, task, validation, i18n, ws, config } = useContainer();

// In Pinia stores (automatically injected)
actions: {
  async fetchData() {
    return await this.$container.api.get('/endpoint');
  }
}
```

### Task Service

The task service handles async operations with built-in loading states and error handling:

```ts
const loginTask = container.task.newTask({
  task: async () => await container.api.post('/auth/login', credentials),
  showNotification: {
    success: () => 'Login successful',
    error: () => 'Login failed'
  }
});

// Run the task
await loginTask.run();
```

## Need Help?

For troubleshooting, check the [Troubleshooting Guide](../troubleshooting.md) or open an issue in the project repository.

[← Back to Main Documentation](../README.md)
