# Backend Documentation

Laravel 12 API with modular architecture, multi-tenancy, and comprehensive testing. The backend provides a robust foundation for multi-platform applications with domain-based tenant isolation.

## Architecture

The backend follows a modular pattern with dedicated modules for different concerns:

- **Core Module** - Base functionality, service container, middleware
- **Tenant Module** - Multi-tenancy with dynamic configuration
- **Auth Module** - Authentication with OAuth and Sanctum
- **Notifications Module** - Real-time notifications and WebSockets
- **Catalog Module** - Example business module demonstrating patterns

## Module Development

### Creating Modules

- **[Module Development Guide](./module-development.md)** - Working with Laravel Modules
- **[Code Quality Standards](./code-quality.md)** - Testing, linting, and best practices

### Core Modules

- **[Multi-Tenancy System](./tenant-module.md)** - Architecture and dynamic configuration
- **[Authentication](./auth-module.md)** - OAuth, Sanctum, and security
- **[Core Module](./core-module.md)** - Base functionality and utilities

### Advanced Features

- **[Dynamic Assets System](./dynamic-assets.md)** - Multi-tenant CSS/JS injection and loading strategies

### Development Tools

- **[Common Commands](./common-commands.md)** - Quick reference for Laravel commands
- **[Testing Guide](./testing.md)** - Unit, feature, and integration testing

---

[← Back to Main Documentation](../README.md)
