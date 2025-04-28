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
├── scripts/          # Utility scripts
├── .github/          # CI/CD workflows
```

## Key Directories

### Backend (`backend/`)

- Laravel 12 application with API endpoints
- Models, controllers, middleware, and migrations
- Authentication and business logic
- Modular architecture with Laravel Modules

### Frontend (`frontend/`)

- Quasar 2 SSR application with Vue 3
- TypeScript components and pages
- Pinia state management
- Service-based architecture

### Docker (`docker/`)

- Container configurations for all services
- Traefik reverse proxy for routing and SSL
- Environment-specific configurations
- Development and production setups

### Docs (`docs/`)

- Setup and architecture documentation
- Frontend and backend development guides
- API references and usage examples

### Scripts (`scripts/`)

- Automation scripts for common tasks
- Setup, start, stop, and maintenance commands
- Development workflow helpers

## Development Environment

- **Environment Variables**: `.env` files in both frontend and backend
- **Hot Reloading**: Real-time updates during development
- **Code Quality**: Configured linters and formatters
- **Testing**: Vitest for frontend, PHPUnit for backend

---

[← Back to Docs](./README.md)
