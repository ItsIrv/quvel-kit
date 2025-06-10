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
- **Deployment**: Template-based configuration system with automatic mode switching

## Configuration System

QuVel Kit features an intelligent configuration system that automatically generates the correct settings for your deployment mode:

- **Template-Based**: Dynamic generation from templates ensures correct paths and URLs
- **Auto IP Detection**: Automatically includes both localhost and LAN IP domains
- **Mode Switching**: Switch between deployment modes instantly with `./scripts/deploy-mode.sh`
- **Path Intelligence**: Automatically uses Docker vs local paths based on where services run
