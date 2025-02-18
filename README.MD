# QuVel Kit

## Introduction

**QuVel Kit** is a **Quasar + Laravel Starter Kit** designed for building modern hybrid applications. It seamlessly integrates **SSR, SPA fallback, mobile, and desktop builds**, providing a robust full-stack solution for efficient development.

## Features

### **Core Features**

- **Quasar + Laravel full-stack integration**
- **Hybrid SSR + SPA setup** with seamless switching
- **Capacitor support** for `iOS` & `Android` builds
- **Electron** desktop build support
- **Traefik-based Docker environments** for `production` and `development`
- **Authentication system** with OAuth support (`Google`, `Apple`)
- **Comprehensive testing suite** with `Vitest` (frontend) & `PHPUnit` (backend)
- **Automated test coverage reports**
- **Detailed documentation** with `.md` files and **DocBlocks**

### **Authentication & User Management**

- **Login Methods**:
  - Email/Username + Password
  - OAuth Providers (`Google`, `Apple`)
- **OAuth Flow** with deep link support for **mobile compatibility**
- **Session-based authentication** for secure logins
- **Account Management**:
  - Sign-up & Login (Email/Username or OAuth)
  - Password reset & Email verification
  - Profile updates (name, password, avatar)

### **Developer Tooling & Testing**

- **Vitest UI** available at [`coverage.quvel.127.0.0.1.nip.io`](https://coverage.quvel.127.0.0.1.nip.io/__vitest__/)
- **PHPUnit Coverage Reports** at [`coverage-api.quvel.127.0.0.1.nip.io`](https://coverage-api.quvel.127.0.0.1.nip.io)
- **Live Docker logs & hot-reloading** for both frontend and backend

### **Planned Features**

- **RBAC (Roles & Permissions)**
- **Admin Dashboard**
- **Real-time WebSocket support**
- **In-app Notifications System**

## Documentation

For a full guide on **project setup, commands, and troubleshooting**, check the [📚 Docs](docs/README.md).

## Quick Setup

### **1️ Clone the repository**

```bash
git clone https://github.com/pdxapps/quvel-kit.git
cd quvel-kit
```

### **2️ Run setup script**

```bash
./scripts/setup.sh
```

This will:

- Set up .env files for Laravel & Quasar
- Start Docker containers
- Generate SSL certificates for local HTTPS support
- Generate app key
- Run database migrations & seeders
- Link storage
- Create an initial test coverage report

### **3️ Access your environment**

- Frontend: <https://quvel.127.0.0.1.nip.io>
- API: <https://api.quvel.127.0.0.1.nip.io>
- Vitest UI: <https://coverage.quvel.127.0.0.1.nip.io/__vitest__/>
- PHPUnit Coverage Reports: <https://coverage-api.quvel.127.0.0.1.nip.io>
- Traefik Dashboard: <http://localhost:8080>

## Available Documentation

### **Core Guides**

- **[README](docs/README.md)** – Project overview
- **[Getting Started](docs/getting-started.md)** – Step-by-step installation and setup
- **[Folder Structure](docs/folder-structure.md)** – Overview of the project organization
- **[Backend Usage](docs/backend-usage.md)** – Laravel API, authentication, migrations, and database setup
- **[Frontend Usage](docs/frontend-usage.md)** – Quasar SSR, SPA, mobile builds, and UI development
- **[Testing Guide](docs/testing.md)** – Running tests, coverage reports, and CI setup
- **[Troubleshooting](docs/troubleshooting.md)** – Common issues and solutions
