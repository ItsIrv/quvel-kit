# QuVel Kit

## Overview

**QuVel Kit** is a production-ready **Laravel 12 + Vue 3 + Quasar 3** boilerplate designed for building enterprise applications. It features SSR with SPA fallback, mobile/desktop builds, and a modular architecture with TypeScript throughout.

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
- **Quasar 3** - UI framework with SSR capabilities
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

- [Getting Started](docs/getting-started.md) - Installation and setup
- [Frontend Documentation](docs/frontend/README.md) - Vue, Quasar, and TypeScript
- [Backend Documentation](docs/backend-usage.md) - Laravel and API

## Quick Start

```bash
# Clone the repository
git clone https://github.com/your-username/quvel-kit.git
cd quvel-kit

# Start the development environment
docker-compose up -d

# Install dependencies
docker-compose exec app composer install
docker-compose exec frontend npm install

# Run migrations and seed the database
docker-compose exec app php artisan migrate --seed

# Start the frontend development server
docker-compose exec frontend npm run dev
```

Visit <http://localhost:8080> to see your application running.

## License

QuVel Kit is open-source software licensed under the MIT license.

---

Built by [Irving Gomez](https://irv.codes).
