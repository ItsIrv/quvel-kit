# QuVel Kit

## Overview

**QuVel Kit** is a production-ready **Laravel 12 + Vue 3 + Quasar 2** boilerplate designed for building enterprise applications. It features SSR with SPA fallback, mobile/desktop builds, and a modular architecture with TypeScript throughout.

## Technology Stack

### Backend

- **Laravel 12** - Latest version with modular architecture
- **PHP 8.3+** - Modern PHP features
- **Laravel Modules** - Modular backend architecture
- **Laravel Sanctum** - Authentication system
- **Laravel Socialite** - OAuth integration
- **Multi-tenancy** - Built-in tenant isolation

### Frontend

- **Vue 3** - Component-based UI framework
- **TypeScript** - Type-safe development
- **Quasar 2** - UI framework with SSR capabilities
- **Pinia** - State management
- **Tailwind CSS** - Utility-first styling
- **Zod** - Schema validation
- **Laravel Echo & Pusher** - Real-time communication

### DevOps

- **Docker** - Containerized development and deployment
- **Traefik** - Request routing and SSL termination
- **GitHub Actions** - CI/CD integration
- **Vitest & PHPUnit** - Comprehensive testing

## Key Features

### Architecture

- **Service Container** - Dependency injection for frontend services
- **SSR with SPA Fallback** - Optimal performance and SEO
- **Multi-platform** - Web, mobile (iOS/Android), and desktop (Electron)
- **RBAC** - Role-based access control
- **Configuration Visibility System** - Tiered config security (PRIVATE, PROTECTED, PUBLIC)

### Developer Experience

- **Hot Module Replacement** - Fast development workflow
- **TypeScript Throughout** - Full type safety
- **Comprehensive Documentation** - Detailed guides and API references
- **Pre-built Components** - Form fields, validation, authentication flows

## Documentation

For detailed documentation, see the following resources:

- [Main Documentation](docs/README.md) - Setup and architecture
- [Frontend Documentation](docs/frontend/README.md) - Vue, Quasar, and TypeScript
- [Backend Documentation](docs/backend/README.md) - Laravel and API

## Quick Start

```bash
# Clone the repository
git clone https://github.com/your-username/quvel-kit.git
cd quvel-kit

# Get your local IP address (needed for multi-tenant setup)
ifconfig | grep "inet " | grep -v 127.0.0.1

# Run the setup script (will prompt for your local IP)
./scripts/setup.sh
```

Once setup completes, access the application at these URLs:

| Service | URL | Description |
|---------|-----|-------------|
| Frontend | [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io) | Quasar SSR application |
| API | [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io) | Laravel API |
| API Telescope | [https://api.quvel.127.0.0.1.nip.io/telescope](https://api.quvel.127.0.0.1.nip.io/telescope) | Laravel debugging |
| Second Tenant | [https://second-tenant.quvel.127.0.0.1.nip.io](https://second-tenant.quvel.127.0.0.1.nip.io) | Multi-tenant testing |

## License

QuVel Kit is open-source software licensed under the MIT license.

---

Built by [Irving Gomez](https://irv.codes).
