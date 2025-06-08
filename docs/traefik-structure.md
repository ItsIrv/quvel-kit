# Traefik Configuration

## Overview

QuVel Kit uses Traefik as a reverse proxy to handle SSL termination and subdomain routing. This configuration provides:

- HTTPS for all services
- WebSockets support
- Production-like local development environment

## Architecture

Traefik operates as a Docker service that routes requests between the host and application containers based on labels and rules.

---

## Configuration Components

### Main Configuration

- Entry points for HTTP and HTTPS
- Dashboard configuration
- Certificate resolver settings

### Dynamic Configuration

- Service-specific routing rules
- Certificate settings
- Frontend and backend routing

### Docker Labels

- Container-specific routing instructions
- Service discovery mechanism

---

## Setup Process

### SSL Certificate Setup

1. Install and configure mkcert:

```bash
mkcert -install
```

2. Verify SSL certificates in `docker/certs/`:
   - `selfsigned.crt`
   - `selfsigned.key`
   - `certificates.yaml`

If certificates are missing, run:

```bash
./scripts/setup.sh
```

### Routing System

QuVel Kit uses `.nip.io` subdomains for local development:

| Service | URL |
|---------|-----|
| Frontend | `https://quvel.127.0.0.1.nip.io` |
| API | `https://api.quvel.127.0.0.1.nip.io` |
| Traefik Dashboard | `http://localhost:8080` |

---

### 4. Running the Project with Traefik

Traefik is included in `docker-compose.yml`. To start the entire stack:

```bash
./scripts/start.sh
```

To restart Traefik only:

```bash
cd docker
docker-compose restart traefik
```

---

### 5. Debugging & Logs

#### Check Traefik Logs

```bash
docker logs -f quvel-traefik
```

#### View Running Routes

```bash
docker exec -it quvel-traefik traefik routes
```

#### Check the Dashboard

If enabled, the Traefik Dashboard is accessible at:

```bash
http://localhost:8080
```

---

## Why HTTPS Internally?

QuVel Kit uses HTTPS for all services, even in development, for several key reasons:

### WebSockets and SSR

- Quasar's dev server (Vite) requires HTTPS for WebSockets in proxy setups
- Hot Module Replacement (HMR) works reliably over secure connections

### Mobile and Desktop Compatibility

- Capacitor (mobile) and Electron (desktop) builds require HTTPS connections
- Prevents cross-origin issues when building for multiple platforms

### Development Benefits

- Secure connections mirror production environments
- Consistent behavior across all deployment targets

## Deployment Configuration Options

Traefik's dynamic configuration supports multiple deployment scenarios through the server URL configuration:

```yaml
services:
  web:
    loadBalancer:
      servers:
        # - url: 'https://127.0.0.1.nip.io:3000'        # Option 1: Docker network routing
        - url: 'https://host.docker.internal:3000'    # Option 2: Local machine hosting
        # - url: 'https://quvel-frontend:9000'         # Option 3: Docker container routing
      serversTransport: 'insecureTransport'           # Supports self-signed certificates
```

### Configuration Options

1. **Docker Network Routing**: All services run in Docker containers
   - Highest isolation and consistency
   - Higher resource usage

2. **Local Machine Hosting**: Traefik in Docker, services on host
   - Reduced resource usage
   - Faster development cycles
   - Ideal for daily development

3. **Docker Container Routing**: Only Traefik uses Docker networking
   - Host backend and frontend on your local machine
   - Minimal Docker footprint
   - Useful for specific services

### Lightweight Development Setup

For minimal resource usage, you can:

- Install Traefik directly on your host machine (via Homebrew) and not use Docker at all
- Configure paths to match your local environment instead of Docker mounts
