# Deployment Options

QuVel Kit offers flexible deployment configurations to match different development needs and resource constraints. Choose the option that best fits your environment.

## Overview

The framework supports four main deployment scenarios, each with different resource requirements and complexity:

| Scenario | Traefik | Backend | Frontend | Database | Complexity |
|----------|---------|---------|----------|----------|------------|
| **Traefik-Only** | Docker | Local | Local | Local | Lowest |
| **Minimal Resource** | Docker | Local | Local | Docker | Low |
| **Full Docker** | Docker | Docker | Docker | Docker | Medium |
| **Fully Local** | Local | Local | Local | Local | High |

## ⚡ Traefik-Only Setup (Most Minimal)

**Best for**: Developers with existing local development stack, maximum resource efficiency

### Architecture
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Browser       │    │   Traefik        │    │   Local Services│
│                 │───▶│   (Docker)       │───▶│                 │
│ HTTPS requests  │    │   SSL termination│    │ Backend: :8000  │
└─────────────────┘    │   Reverse proxy  │    │ Frontend: :3000 │
                       └──────────────────┘    │ MySQL: :3306    │
                                               │ Redis: :6379    │
                                               └─────────────────┘
```

### What runs where:
- **Traefik**: Docker container (reverse proxy, SSL only)
- **Laravel API**: Local PHP (`php artisan serve --host=0.0.0.0 --port=8000`)
- **Quasar Frontend**: Local Node.js (`quasar dev --port 3000`)
- **MySQL**: Local installation (Homebrew)
- **Redis**: Local installation (Homebrew)

### Advantages:
- ✅ Absolute minimum Docker footprint
- ✅ Works with existing local development stack
- ✅ Direct debugging access to all services
- ✅ Reuses existing MySQL/Redis installations

### Setup Process:
```bash
# Setup with traefik-only mode (new default)
./scripts/setup.sh

# Or explicitly specify traefik-only mode
./scripts/setup.sh --mode=traefik-only
```

Then follow the provided instructions to start local services.

---

## 🚀 Minimal Resource Setup

**Best for**: Daily development, older machines, battery life optimization

### Architecture
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Browser       │    │   Traefik        │    │   Local Services│
│                 │───▶│   (Docker)       │───▶│                 │
│ HTTPS requests  │    │   SSL termination│    │ Backend: :8000  │
└─────────────────┘    │   Reverse proxy  │    │ Frontend: :3000 │
                       └──────────────────┘    │ Database: :3306 │
                                               └─────────────────┘
```

### What runs where:
- **Traefik**: Docker container (reverse proxy, SSL)
- **Laravel API**: Local PHP (`php artisan serve --host=0.0.0.0 --port=8000`)
- **Quasar Frontend**: Local Node.js (`quasar dev --port 3000`)
- **MySQL**: Local installation or Docker
- **Redis**: Local installation or Docker

### Configuration:
In `docker/traefik/dynamic/backend.yml`:
```yaml
services:
  api-local:
    loadBalancer:
      servers:
        - url: 'http://host.docker.internal:8000' # ✅ Active
        # - url: 'http://quvel-app:8000'           # ❌ Commented (Docker)
```

In `docker/traefik/dynamic/frontend.yml`:
```yaml
services:
  web:
    loadBalancer:
      servers:
        - url: 'https://host.docker.internal:3000' # ✅ Active
        # - url: 'https://quvel-frontend:9000'      # ❌ Commented (Docker)
```

### Advantages:
- ✅ Lower resource usage than full Docker
- ✅ Direct debugging access
- ✅ Native tool access

### Setup Process:
```bash
# Quick setup (default minimal mode)
./scripts/setup.sh

# Or explicitly specify minimal mode
./scripts/setup.sh --mode=minimal
```

Then follow the provided instructions to start local services.

---

## 🐳 Full Docker Setup

**Best for**: Team consistency, CI/CD pipelines, production-like environments

### Architecture
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Browser       │    │   Traefik        │    │   Docker Network│
│                 │───▶│   (Docker)       │───▶│                 │
│ HTTPS requests  │    │   SSL termination│    │ Backend: :8000  │
└─────────────────┘    │   Reverse proxy  │    │ Frontend: :9000 │
                       └──────────────────┘    │ Database: :3306 │
                                               └─────────────────┘
```

### What runs where:
- **All services**: Docker containers
- **Communication**: Internal Docker network
- **Isolation**: Complete containerization

### Configuration:
In `docker/traefik/dynamic/backend.yml`:
```yaml
services:
  api-local:
    loadBalancer:
      servers:
        # - url: 'http://host.docker.internal:8000' # ❌ Commented (Local)
        - url: 'http://quvel-app:8000'              # ✅ Active
```

In `docker/traefik/dynamic/frontend.yml`:
```yaml
services:
  web:
    loadBalancer:
      servers:
        # - url: 'https://host.docker.internal:3000' # ❌ Commented (Local)
        - url: 'https://quvel-frontend:9000'         # ✅ Active
```

### Advantages:
- ✅ Consistent environment across team
- ✅ Isolated dependencies
- ✅ Production parity

### Setup Process:
```bash
# Setup with full Docker mode
./scripts/setup.sh --mode=docker
```

All services start automatically and the application is immediately accessible.

---

## 💻 Fully Local Setup

**Best for**: Maximum performance, experienced developers, custom tooling

### Architecture
```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Browser       │    │   Traefik        │    │   Local Services│
│                 │───▶│   (Homebrew)     │───▶│                 │
│ HTTPS requests  │    │   SSL termination│    │ Backend: :8000  │
└─────────────────┘    │   Reverse proxy  │    │ Frontend: :3000 │
                       └──────────────────┘    │ Database: :3306 │
                                               └─────────────────┘
```

### What runs where:
- **Everything**: Local installation
- **No Docker**: Zero container overhead
- **Direct access**: All services native

### Setup Process:
```bash
# Install dependencies via Homebrew (example)
brew install php@8.3 mysql redis node traefik
brew install --cask composer

# Setup with local mode
./scripts/setup.sh --mode=local
```

Follow the provided instructions to start all services locally.

### Advantages:
- ✅ No Docker overhead
- ✅ Native debugging
- ✅ Custom tool integration

### Considerations:
- More complex initial setup
- Version management across team members
- Platform-specific dependencies

---

## Mobile Development Considerations

### iOS Development (Capacitor)
All deployment options support iOS development with slight configuration differences:

#### Network Access Requirements:
- **LAN IP needed**: iOS devices must access your development machine
- **HTTPS required**: Capacitor requires secure connections
- **Certificate trust**: Devices must trust your development certificates

#### Configuration:
Run `./scripts/capacitor.sh` to configure:
- Updates Capacitor config with your LAN IP
- Ensures SSL certificates cover LAN domains
- Sets up tenant routing for mobile access

### Example URLs:
- **Local**: `https://quvel.127.0.0.1.nip.io`
- **LAN**: `https://quvel.192.168.1.100.nip.io`
- **Capacitor**: `https://cap-tenant.quvel.192.168.1.100.nip.io`

---

## Switching Between Setups

Use the provided scripts to switch between deployment modes:

```bash
# Switch to traefik-only mode (only Traefik in Docker)
./scripts/setup.sh --mode=traefik-only

# Switch to minimal resource mode (Traefik + DB in Docker, services local)
./scripts/setup.sh --mode=minimal

# Switch to full Docker mode (all services in Docker)
./scripts/setup.sh --mode=docker

# Switch to fully local mode (all services local including Traefik)
./scripts/setup.sh --mode=local

# Quick mode switching without full setup
./scripts/deploy-mode.sh traefik-only
./scripts/deploy-mode.sh minimal
./scripts/deploy-mode.sh docker
./scripts/deploy-mode.sh local
```

**Manual switching:**
1. Update traefik configuration files
2. Stop/start appropriate services
3. Update environment variables if needed

---

## Troubleshooting

### Common Issues:

1. **Services not accessible**
   - Check traefik configuration matches your setup
   - Verify service URLs are correct
   - Ensure ports are not in use

2. **SSL Certificate errors**
   - Run `./scripts/ssl.sh` to regenerate certificates
   - Trust certificates in browser/system

3. **Performance issues**
   - Consider switching to minimal resource setup
   - Check Docker resource limits
   - Monitor system resource usage

---

[← Back to Deployment](./README.md)