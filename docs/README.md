# QuVel Kit Documentation

Complete documentation for QuVel Kit - a full-stack SaaS framework with multi-tenancy, multi-platform deployment, and modern development tools.

## Quick Navigation

### 🚀 Getting Started
- **[Setup & Installation](./getting-started.md)** - Quick start with deployment modes
- **[Project Structure](./folder-structure.md)** - Architecture and organization

### 💻 Development
- **[Backend Guide](./backend/README.md)** - Laravel modules, API, and architecture
- **[Frontend Guide](./frontend/README.md)** - Vue, Quasar, TypeScript, and SSR

### 🏗️ Deployment & Infrastructure
- **[Deployment Options](./deployment/README.md)** - Docker, local, and hybrid setups
- **[Troubleshooting](./troubleshooting.md)** - Common issues and solutions

## Architecture Overview

QuVel Kit uses a modular architecture with clear separation between frontend and backend concerns:

- **Backend**: Laravel 12 with modular structure (Auth, Tenant, Core, Notifications)
- **Frontend**: Vue 3 + Quasar 2 with SSR, service container pattern, and TypeScript
- **Multi-Tenancy**: Domain-based isolation with dynamic configuration
- **Deployment**: Flexible modes from minimal (traefik-only) to full Docker
