# QuVel Kit Frontend

## Overview

The QuVel Kit frontend is built with Vue 3, TypeScript, and Quasar 2, featuring server-side rendering (SSR) with SPA fallback and Capacitor support for native mobile/desktop builds. The architecture follows a modular, service-oriented approach with strict TypeScript typing throughout.

## Technology Stack

| Component | Technology | Version | Purpose |
|-----------|------------|---------|--------|
| Framework | Vue 3 | 3.x | Composition API-based UI framework |
| UI Library | Quasar 2 | 2.x | Component library with SSR support |
| Language | TypeScript | 5.x | Type-safe development |
| Styling | Tailwind CSS | 3.x | Utility-first CSS framework |
| State | Pinia | 2.x | State management |
| Validation | Zod | 3.x | Runtime schema validation |
| HTTP | Axios | 1.x | API communication |
| WebSockets | Laravel Echo | 1.x | Real-time updates |
| Logging | Winston | 3.x | Structured logging |

## Getting Started

```bash
# Install dependencies
cd frontend
yarn install

# Start development server with SSR
yarn dev:ssr

# Start development server with SPA mode
yarn dev:spa

# Build for production
yarn build
```

## Directory Structure

```
frontend/
├─ src/
│   ├─ modules/         # Feature modules with components, stores, services
│   │   ├─ Core/        # Core functionality and services
│   │   ├─ Auth/        # Authentication and authorization
│   │   ├─ Tenant/      # Multi-tenancy support
│   │   └─ Quvel/       # QuVel-specific components
│   ├─ boot/            # Quasar initialization files
│   ├─ composables/     # Shared Vue composition functions
│   ├─ css/             # Global styles and Tailwind configuration
│   └─ services/        # Core application services
├─ src-ssr/            # Server-side rendering implementation
├─ src-capacitor/      # Native platform integration
└─ test/               # Unit and component tests
```

## Documentation

Comprehensive documentation is available in the `/docs/frontend` directory:

### Core Features

- [Composables](../docs/frontend/frontend-composables.md) - Vue composition functions
- [Service Container](../docs/frontend/frontend-service-container.md) - Dependency injection
- [Configuration](../docs/frontend/frontend-config-service.md) - Application settings
- [Logging](../docs/frontend/frontend-logging.md) - Structured logging system
- [Task Management](../docs/frontend/frontend-task-management.md) - Async operations

### State & Data

- [State Management](../docs/frontend/frontend-state-management.md) - Pinia stores
- [Pagination](../docs/frontend/frontend-pagination.md) - Data pagination
- [Validation](../docs/frontend/frontend-validation.md) - Form validation with Zod

### Communication

- [WebSockets](../docs/frontend/frontend-websockets.md) - Real-time communication
- [Notifications](../docs/frontend/frontend-notifications.md) - User notifications
- [Translations](../docs/frontend/frontend-translations.md) - i18n support

### Development

- [Environment Setup](../docs/frontend/frontend-usage.md) - Development workflow
- [Environment Configuration](../docs/frontend/frontend-env-configs.md) - Environment variables

## Key Architecture Features

### Universal/Isomorphic Design

The codebase is designed to run in multiple environments:

- Browser (client-side)
- Node.js (server-side rendering)
- Native platforms (via Capacitor)

Code that needs to behave differently based on the runtime environment should use the appropriate guards:

```typescript
// In composables or services
if (process.env.SERVER) {
  // Server-only code
} else if (process.env.CLIENT) {
  // Browser-only code
}
```

### Service Container

The application uses a dependency injection container to manage services:

```typescript
// Accessing services
import { useContainer } from 'src/modules/Core/composables/useContainer';

const { api, config, logger } = useContainer();
```

### Strict Typing

All code must use strict TypeScript typing:

- No use of `any` type
- Proper interface and type definitions
- Runtime validation with Zod for external data

### Module-Based Organization

Code is organized by feature modules rather than by type:

```typescript
// Good: Feature-based organization
src/modules/Auth/services/AuthService.ts
src/modules/Auth/stores/sessionStore.ts
src/modules/Auth/components/LoginForm.vue

// Avoid: Type-based organization
src/services/AuthService.ts
src/stores/sessionStore.ts
src/components/LoginForm.vue
```

## SSR Considerations

- Lifecycle management for subscriptions and timers
- Hydration-safe component design
- Platform-specific code isolation
- State serialization for SSR transfer

## Capacitor Integration

The project supports building native mobile and desktop applications via Capacitor:

```bash
# Build for Capacitor
yarn build:capacitor

# Add platforms
npx cap add android
npx cap add ios
npx cap add electron

# Sync changes to native projects
npx cap sync
```
