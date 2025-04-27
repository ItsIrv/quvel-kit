# QuVel Kit

## Introduction

**QuVel Kit** is a **Quasar + Laravel Starter Kit** designed for building modern hybrid applications with **SSR, SPA fallback, mobile, and desktop builds**. It provides a modular, scalable foundation for full-stack development, offering robust authentication, state management, and dependency injection via a **Service Container**.

## Features

### **Core Features**

- **Quasar + Laravel full-stack integration**
- **Hybrid SSR + SPA setup** with seamless switching
- **Capacitor support** for `iOS` & `Android` builds
- **Electron** desktop build support
- **Traefik-based Docker environments** for `production` and `development`
- **Fully modular Laravel backend using `nWidart/laravel-modules`**
- **Service Container for managing frontend dependency injection**
- **Authentication system** with OAuth (`Google`, `Apple`)
- **RBAC (Role-Based Access Control) with multi-tenancy support**
- **Comprehensive testing suite** with `Vitest` (frontend) & `PHPUnit` (backend)
- **Automated test coverage reports**
- **Detailed documentation** with `.md` files and **DocBlocks**

### **Authentication & User Management**

- **Login Methods**:
  - Email/Username + Password
  - OAuth Providers (`Google`, `Apple`)
- **OAuth Flow** with deep link support for **mobile compatibility**
- **Session-based authentication** (tokens coming soon)
- **Multi-tenancy support using a `tenant_id` system**
- **Role-Based Access Control (RBAC) from the start**
- **Account Management**:
  - Sign-up & Login (Email/Username or OAuth)
  - Password reset & Email verification
  - Profile updates (name, password, avatar)

### **Frontend Service Container**

- **API Service** (`container.api`) – Manages Axios HTTP requests with session authentication
- **Task Service** (`container.task`) – Encapsulates async operations with built-in loading, error handling, and success notifications
- **Validation Service** (`container.validation`) – Zod-based validation with support for Vue components and translations
- **i18n Service** (`container.i18n`) – Fully localized interface with SSR-safe translations
- **Dynamic Service Loading** – Register and retrieve custom services at runtime

### **Backend Architecture**

- **Multi-tenancy with a single database**
- **Modular architecture using `nWidart/laravel-modules`**
- **RBAC built-in with roles and permissions**
- **Session-based authentication with Sanctum**
- **Socialite-based OAuth login system with secure nonce validation**
- **Middleware for enforcing tenant-based access**

## Documentation

For a full guide on **project setup, commands, and troubleshooting**, check the [Documentation](docs/README.md).

## Setup & Installation

Refer to the [Getting Started Guide](docs/getting-started.md) for detailed instructions on installation and setup.

## Development & Debugging

Refer to the [Frontend Usage Guide](docs/frontend/frontend-usage.md) and [Backend Usage Guide](docs/backend-usage.md) for development workflows.

## Roadmap

### **Short-Term Goals**

- Finish Auth Flows. Password Reset remaining.
- Complete Socialite (DONE) & RBAC integration
- Expand testing coverage (Backend coverage ~85%, missing coverage = modules in development. FE coverage ~1% from base examples, not a priority)
- Improve module-based backend and frontend architecture (DONE)

### **Long-Term Goals**

- Add token-based authentication
- Extend Laravel Echo for real-time features (DONE)
- Push & background Notifications.
- Offline mode.

---

QuVel Kit provides a **fully modular** foundation for Laravel & Quasar applications, ensuring best practices, multi-tenancy support, and an **enterprise-ready stack** from day one.
