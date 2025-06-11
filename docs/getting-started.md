# Getting Started with QuVel Kit

## Prerequisites

### Base Requirements (All Modes)
- Node.js 18.0+
- Yarn 1.22+  
- mkcert (for SSL certificates)
- Docker 20.10+ (unless using fully local mode)

### Mode-Specific Requirements

- **Traefik-Only Mode**: PHP 8.3+, Composer 2.0+, MySQL 8.0+, Redis 6.0+
- **Full Docker Mode**: Docker Compose 2.0+
- **Local Mode**: All of the above + Traefik installed locally

For detailed requirements, see [Deployment Options](./deployment/deployment-options.md).

## Quick Start

```bash
# Clone and enter the repository
git clone https://github.com/ItsIrv/quvel-kit.git
cd quvel-kit

# Run setup (auto-detects IP, generates configs, starts services)
./scripts/setup.sh

# Or choose a specific mode
./scripts/setup.sh --mode=docker   # Everything in Docker
./scripts/setup.sh --mode=minimal  # Hybrid approach
./scripts/setup.sh --mode=local    # Everything local
```

## What Happens During Setup

1. **Environment Detection**: Automatically detects your local IP for multi-device access
2. **SSL Generation**: Creates self-signed certificates via mkcert
3. **Configuration Generation**: Creates all Traefik configs from templates
4. **Service Startup**: Starts appropriate services based on deployment mode
5. **Instructions**: Provides mode-specific next steps

## Accessing Your Application

Once setup is complete:

- **Frontend**: https://quvel.127.0.0.1.nip.io
- **API**: https://api.quvel.127.0.0.1.nip.io  
- **Traefik Dashboard**: http://localhost:8080

Your LAN IP is automatically included, so you can also access via `https://quvel.{your-ip}.nip.io` from other devices.

## Switching Deployment Modes

The configuration system allows instant switching between modes:

```bash
# Check current mode
./scripts/deploy-mode.sh current

# Switch to a different mode
./scripts/deploy-mode.sh docker
./scripts/deploy-mode.sh traefik-only
```

## Common Operations

- **Start Services**: `./scripts/start.sh`
- **Stop Services**: `./scripts/stop.sh`
- **View Logs**: `./scripts/log.sh`
- **Reset Everything**: `./scripts/reset.sh`

## Next Steps

- **Development**: See [Backend](./backend/README.md) or [Frontend](./frontend/README.md) guides
- **Multi-Tenancy**: Learn about the [Tenant System](./backend/tenant-module.md)
- **Deployment**: Explore [Deployment Options](./deployment/deployment-options.md)
- **Troubleshooting**: Check [Common Issues](./troubleshooting.md)

---

[← Back to Docs](./README.md)