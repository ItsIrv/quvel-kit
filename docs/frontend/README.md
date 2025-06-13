# Frontend Documentation

Vue 3 + Quasar 2 with SSR, TypeScript, and multi-platform deployment. Features a service container pattern with modular architecture and support for web, mobile (Capacitor), and desktop (Electron) builds.

## Architecture

### Service Container Pattern
The frontend uses dependency injection with a three-phase lifecycle:
- **Construct** - Service initialization
- **Boot** - Environment-specific setup (receives req/res in SSR)
- **Register** - Service registration and composable setup

### Module Structure
```text
src/modules/{ModuleName}/
├── components/     # Vue components
├── services/       # Module services  
├── stores/         # Pinia stores
├── models/         # TypeScript models
├── composables/    # Vue composables
└── validators/     # Zod validators
```

### Multi-Platform Support
- **Web**: SSR with SPA fallback
- **Mobile**: Capacitor for iOS/Android
- **Desktop**: Electron builds

## Core Concepts

### Services & Architecture
- **[Service Container](./frontend-service-container.md)** - Quasar application dependency injection
- **[Express Container](./frontend-ssr-services.md)** - Express server service architecture
- **[Configuration Service](./frontend-config-service.md)** - Tiered configuration system
- **[Environment Configs](./frontend-env-configs.md)** - Build-time configuration

### State & Data Management  
- **[State Management](./frontend-state-management.md)** - Pinia patterns and hydration
- **[Task Management](./frontend-task-management.md)** - Async operation handling
- **[Pagination](./frontend-pagination.md)** - Data pagination strategies

### UI Development
- **[Composables](./frontend-composables.md)** - Reusable Vue composition functions
- **[Validation](./frontend-validation.md)** - Form validation with Zod
- **[Translations](./frontend-translations.md)** - Multi-language support

### Real-Time Features
- **[WebSockets](./frontend-websockets.md)** - Real-time communication
- **[Notifications](./frontend-notifications.md)** - User notification system

### Development & Deployment
- **[Usage Guide](./frontend-usage.md)** - Development workflow and setup
- **[Logging](./frontend-logging.md)** - Debug and application logging
- **[PWA Limitations](./frontend-pwa-limitations.md)** - Multi-tenancy constraints

[← Back to Main Documentation](../README.md)
