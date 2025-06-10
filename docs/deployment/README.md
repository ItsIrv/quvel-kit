# Deployment & Infrastructure

This section covers deployment options, infrastructure setup, and development environment configuration for QuVel Kit.

## Documentation Overview

### Setup & Configuration
- **[Getting Started](./getting-started.md)** – Quick setup and environment configuration
- **[Deployment Options](./deployment-options.md)** – Choose the right setup for your needs

### Infrastructure Components
- **[Traefik Configuration](./traefik.md)** – Reverse proxy and SSL setup
- **[Docker Setup](./docker.md)** – Container configuration and orchestration
- **[Development Scripts](./scripts.md)** – Automation tools for common tasks

### Platform Deployment
- **[Local Development](./local-development.md)** – Development environment setup
- **[Production Deployment](./production.md)** – Production hosting guide
- **[Mobile Development](./mobile.md)** – iOS and Android development setup

---

## Quick Setup

```bash
# Traefik-only (default - most minimal)
./scripts/setup.sh

# Full Docker (everything containerized)  
./scripts/setup.sh --mode=docker

# Switch between modes anytime
./scripts/deploy-mode.sh traefik-only
./scripts/deploy-mode.sh docker
```

### Deployment Modes

| Mode | Traefik | Services | Database | Best For |
|------|---------|----------|----------|----------|
| **Traefik-Only** | Docker | Local | Local | Existing local stacks |
| **Minimal** | Docker | Local | Docker | Mixed preferences |
| **Docker** | Docker | Docker | Docker | Consistency, CI/CD |
| **Local** | Local | Local | Local | Maximum performance |

---

[← Back to Main Documentation](../README.md)