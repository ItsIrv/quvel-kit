# QuVel Kit

![Backend CI](https://github.com/ItsIrv/quvel-kit/actions/workflows/backend-ci.yml/badge.svg)
[![codecov](https://codecov.io/gh/ItsIrv/quvel-kit/branch/main/graph/badge.svg)](https://codecov.io/gh/ItsIrv/quvel-kit)
![License](https://img.shields.io/github/license/ItsIrv/quvel-kit)

## Overview

**QuVel Kit** is a full-stack SaaS framework combining Laravel + Vue + Quasar that can target multiple platforms and serve multiple tenants from a single codebase. Build web (SSR/SPA), mobile (iOS/Android), and desktop (Electron) applications with built-in multi-tenancy, OAuth authentication, and real-time features.

## Live Demos

- [First Tenant](https://quvel.pdxapps.com)
- [Second Tenant](https://quvel-two.pdxapps.com)

## Key Features

- **Multi-Platform Deployment** - Web, mobile, and desktop from one codebase
- **Multi-Tenancy** - Domain-based tenant isolation with dynamic configuration
- **Modern Stack** - Laravel 12, Vue 3, Quasar 2, TypeScript
- **Service Architecture** - Modular backend and frontend with dependency injection
- **Real-Time Features** - WebSocket support with Laravel Echo
- **Flexible Deployment** - Automatic configuration generation for all deployment modes
- **Developer Experience** - Hot reloading, TypeScript, comprehensive testing

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

# Setup with automatic configuration generation (default: traefik-only)
./scripts/setup.sh

# Or choose a specific deployment mode
./scripts/setup.sh --mode=docker   # Full Docker
./scripts/setup.sh --mode=minimal  # Traefik + databases in Docker
./scripts/setup.sh --mode=local    # Everything local

# Switch deployment modes anytime
./scripts/deploy-mode.sh docker
./scripts/deploy-mode.sh traefik-only
```

After setup, access your application (supports both localhost and LAN IP automatically):

- **Frontend**: [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io)
- **API**: [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io)
- **Multi-Tenant Demo**: [https://second-tenant.quvel.127.0.0.1.nip.io](https://second-tenant.quvel.127.0.0.1.nip.io)

## License

QuVel Kit is open-source software licensed under the MIT license.

---

Built by [Irving Gomez](https://irv.codes).
