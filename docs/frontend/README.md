# Frontend Documentation

## Overview

QuVel Kit's frontend is built with Vue 3 and Quasar 2, featuring server-side rendering (SSR) with SPA fallback and Capacitor support for native mobile/desktop builds. The architecture follows a modular, service-oriented approach with strict TypeScript typing throughout.

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
│   │   ├── Notifications/ # Notification system
│   │   ├── Catalog/     # Catalog management
│   │   └── Quvel/       # Quvel-specific components
│   ├── boot/            # Quasar boot files
│   ├── i18n/            # Translations
│   └── composables/     # Shared Vue composables
└── src-ssr/            # Server-side rendering code
```

## Documentation

### Core Services

- **[Service Container](./frontend-service-container.md)** - Dependency injection system
- **[Configuration Service](./frontend-config-service.md)** - Application settings with tiered visibility
- **[Environment Configs](./frontend-env-configs.md)** - Environment-specific configuration
- **[Task Management](./frontend-task-management.md)** - Async operation orchestration
- **[Logging](./frontend-logging.md)** - Application logging system

### State Management

- **[State Management](./frontend-state-management.md)** - Pinia store patterns
- **[Pagination](./frontend-pagination.md)** - Data pagination strategies
- **[Notifications](./frontend-notifications.md)** - Notification system

### UI & Interaction

- **[Composables](./frontend-composables.md)** - Reusable Vue composition functions
- **[Validation](./frontend-validation.md)** - Form and data validation with Zod
- **[Translations](./frontend-translations.md)** - Internationalization with Vue I18n
- **[WebSockets](./frontend-websockets.md)** - Real-time communication

### Development

- **[Environment Setup & Usage](./frontend-usage.md)** - Development workflow

## Need Help?

For troubleshooting, check the [Troubleshooting Guide](../troubleshooting.md) or open an issue in the project repository.

[← Back to Main Documentation](../README.md)
