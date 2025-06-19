# Folder Structure

## Overview

QuVel Kit uses a structured layout for clarity and maintainability. This document outlines the key directories and their purposes.

## Directory Layout

```bash
├── backend/          # Laravel API (PHP)
├── frontend/         # Quasar SSR (Vue.js)
├── docker/           # Docker configurations
│   ├── certs/        # SSL certificates
│   ├── traefik/      # Reverse proxy configuration
│   │   ├── dynamic/  # Dynamic routing configs
├── docs/             # Documentation
│   ├── backend/      # Backend development guides
│   ├── frontend/     # Frontend development guides
│   ├── deployment/   # Infrastructure and deployment guides
├── scripts/          # Utility scripts
├── .github/          # CI/CD workflows
```

## Key Directories

### Backend (`backend/`)

- Laravel 12 application with API endpoints
- Models, controllers, middleware, and migrations
- Authentication and business logic
- Modular architecture with Laravel Modules

#### Module Structure
```
backend/Modules/{ModuleName}/
├── app/                  # Module application code
│   ├── Actions/          # Business logic actions
│   ├── Contracts/        # Interfaces
│   ├── Http/             # Controllers, middleware, requests
│   ├── Models/           # Eloquent models
│   ├── Pipes/            # Configuration pipes (for tenant config)
│   ├── Providers/        # Service providers & config providers
│   └── Services/         # Service classes
├── config/               # Module configuration
├── database/             # Migrations, factories, seeders
├── docs/                 # Module-specific documentation
├── lang/                 # Translations
├── routes/               # Route definitions
└── tests/                # Module tests
```

### Frontend (`frontend/`)

- Quasar 2 SSR application with Vue 3
- TypeScript components and pages
- Pinia state management
- Service-based architecture

#### Module Structure
```
frontend/src/modules/{ModuleName}/
├── components/          # Vue components
├── composables/         # Vue composables
├── models/              # TypeScript models/interfaces
├── pages/               # Page components
├── services/            # Module services
├── stores/              # Pinia stores
├── types/               # TypeScript type definitions
└── validators/          # Zod validators
```

### Docker (`docker/`)

- Container configurations for all services
- Traefik reverse proxy for routing and SSL
- Environment-specific configurations
- Development and production setups

### Docs (`docs/`)

Documentation is organized into focused sections:

#### Main Documentation
- **Getting Started**: Installation and setup guides
- **Folder Structure**: Project organization overview (this document)
- **Troubleshooting**: Common issues and solutions

#### Development Guides (`docs/backend/`, `docs/frontend/`)
- **Backend**: Laravel modules, authentication, testing, architecture
- **Frontend**: Vue/Quasar development, services, state management

#### Infrastructure (`docs/deployment/`)
- **Deployment Options**: Different setup modes (traefik-only, minimal, docker, local)
- **Traefik Configuration**: Reverse proxy and SSL setup
- **Docker Setup**: Container configuration and orchestration
- **Scripts**: Automation tools and development workflows

### Scripts (`scripts/`)

Development automation tools:

- **setup.sh**: Environment initialization with deployment mode support
- **deploy-mode.sh**: Switch between deployment configurations  
- **start.sh/stop.sh/restart.sh**: Service management
- **ssl.sh**: SSL certificate generation
- **log.sh**: View service logs
- **reset.sh**: Complete environment reset
- **capacitor.sh**: Mobile development configuration

## Development Environment

### Deployment Flexibility

QuVel Kit supports multiple deployment modes to match different development needs:

- **Traefik-Only** (Default): Only Traefik in Docker, everything else local
- **Minimal**: Traefik + databases in Docker, services local
- **Full Docker**: All services in Docker containers
- **Fully Local**: Everything local (including Traefik via Homebrew)

### Development Tools

- **Environment Variables**: `.env` files in both frontend and backend
- **Hot Reloading**: Real-time updates during development (all modes)
- **Code Quality**: Configured linters and formatters
- **Testing**: Vitest for frontend, PHPUnit for backend
- **SSL**: Automatic HTTPS with self-signed certificates for development
- **Multi-tenancy**: Built-in tenant isolation and configuration

---

[← Back to Docs](./README.md)
