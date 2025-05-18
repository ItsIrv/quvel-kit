# Backend Documentation

## Overview

QuVel Kit's backend is built with Laravel 12 and follows a modular architecture pattern. The backend provides a robust API for the frontend and supports multi-tenancy out of the box.

## Technology Stack

| Component | Technology | Version |
|-----------|------------|--------|
| Framework | Laravel | 12.0 |
| PHP | PHP | 8.3+ |
| Database | MySQL | 8.0+ |
| Cache | Redis | 6.0+ |
| Queue | Redis | 6.0+ |
| Authentication | Laravel Sanctum | Latest |
| API Documentation | OpenAPI/Swagger | Latest |

## Documentation Sections

### Getting Started

- [Getting Started](./getting-started.md) - Setup and basic usage

### Modules

- [Module Development](./module-development.md) - Working with Laravel Modules
- [Tenant Module](./tenant-module.md) - Multi-tenancy support
- [Auth Module](./auth-module.md) - Authentication and authorization
- [Core Module](./core-module.md) - Core functionality and utilities

### Development Tools

- [Code Quality](./code-quality.md) - Code quality standards and best practices
- [Testing](./testing.md) - Testing and debugging

## Key Features

- **Modular Architecture** - Organized into reusable modules
- **API-First Design** - Built for frontend consumption
- **Multi-Tenancy** - Support for multiple tenants with domain isolation
- **Comprehensive Testing** - Unit, feature, and module tests
- **Authentication** - Secure authentication using Laravel Sanctum

---

[← Back to Main Documentation](../README.md)
