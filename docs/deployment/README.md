# Deployment & Infrastructure

This section covers deployment options, infrastructure setup, and development environment configuration for QuVel Kit.

## Documentation Overview

### Setup & Configuration
- **[Getting Started](./getting-started.md)** – Quick setup and environment configuration
- **[Deployment Options](./deployment-options.md)** – Choose the right setup for your needs

### Infrastructure Components
- **[Traefik Configuration](./traefik.md)** – Reverse proxy and SSL setup
- **[Docker Setup](./docker.md)** – Container configuration and orchestration
- **[Development Scripts](./scripts.md)** – Automation tools and configuration generation

### Platform Deployment
- **[Local Development](./local-development.md)** – Development environment setup
- **[Production Deployment](./production.md)** – Production hosting guide
- **[Mobile Development](./mobile.md)** – iOS and Android development setup

---

## Quick Setup

```bash
# Automatic setup with intelligent configuration generation
./scripts/setup.sh                    # Default: traefik-only mode
./scripts/setup.sh --mode=docker      # Full Docker mode
./scripts/setup.sh --mode=minimal     # Hybrid mode
./scripts/setup.sh --mode=local       # Everything local

# Switch between modes anytime (generates new configs automatically)
./scripts/deploy-mode.sh traefik-only
./scripts/deploy-mode.sh docker
./scripts/deploy-mode.sh minimal
./scripts/deploy-mode.sh local

# Check current deployment mode
./scripts/deploy-mode.sh current
```

### Deployment Modes

| Mode | Traefik | Services | Database | Configuration | Best For |
|------|---------|----------|----------|---------------|----------|
| **Traefik-Only** | Docker | Local | Local | Auto-generated | Existing local stacks |
| **Minimal** | Docker | Local | Docker | Auto-generated | Mixed preferences |
| **Docker** | Docker | Docker | Docker | Auto-generated | Consistency, CI/CD |
| **Local** | Local | Local | Local | Auto-generated | Maximum performance |

### Intelligent Configuration System

QuVel Kit uses a template-based configuration system that automatically generates the correct settings for your deployment mode:

#### What Gets Generated
- **`traefik.yml`**: Main Traefik configuration with correct paths (Docker vs local)
- **`frontend.yml`**: Frontend service routing with proper targets
- **`backend.yml`**: Backend API routing with correct upstream services
- **`certificates.yaml`**: SSL certificate configuration with proper paths

#### Smart Path Detection
- **Docker Modes**: Uses container paths (`/certs/`, `/traefik`, `quvel-frontend:9000`)
- **Local Mode**: Uses absolute local paths (`/full/path/to/docker/certs/`)
- **Hybrid Modes**: Uses `host.docker.internal` for Docker-to-local communication

#### Network Intelligence
- **Auto IP Detection**: Automatically detects and includes your LAN IP
- **Multi-Device Access**: Generates domains for both `127.0.0.1.nip.io` and `{your-ip}.nip.io`
- **WebSocket Support**: Proper WebSocket routing for hot module replacement

#### Template Locations
```
docker/traefik/templates/
├── traefik.yml.template      # Main Traefik config
├── frontend.yml.template     # Frontend routing
├── backend.yml.template      # Backend routing
└── certificates.yml.template # SSL configuration
```

---

[← Back to Main Documentation](../README.md)