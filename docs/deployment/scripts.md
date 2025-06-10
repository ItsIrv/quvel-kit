# QuVel Kit Scripts

This document provides a comprehensive guide to the utility scripts available in QuVel Kit. These scripts automate common development tasks and help manage the application environment.

## Setup Scripts

### `setup.sh`

The main setup script that initializes the QuVel Kit environment with support for different deployment modes.

```bash
# Default minimal mode (Traefik in Docker, services local)
./scripts/setup.sh

# Full Docker mode (all services in Docker)
./scripts/setup.sh --mode=docker

# Local mode (all services local)
./scripts/setup.sh --mode=local

# Show help
./scripts/setup.sh --help
```

**What it does:**

- Creates `.env` files for both frontend and backend if they don't exist
- Generates SSL certificates via `ssl.sh`
- Configures Traefik routing based on selected deployment mode
- Starts appropriate Docker containers based on mode
- Sets up database and runs migrations (Docker mode only)
- Provides next-step instructions for each mode

**Deployment modes:**

- **minimal**: Traefik, MySQL, Redis in Docker; backend/frontend local
- **docker**: All services in Docker containers
- **local**: All services local (requires local Traefik installation)

**When to use:**

- When setting up QuVel Kit for the first time
- After running `reset.sh` to recreate the environment
- When switching between deployment modes

### `ssl.sh`

Generates SSL certificates for local development using mkcert.

```bash
./scripts/ssl.sh
```

**What it does:**

- Installs mkcert if not already installed
- Sets up a local Certificate Authority if needed
- Copies the CA certificate to the `docker/certs` directory
- Prompts for your LAN IP to create additional certificates for LAN access
- Generates SSL certificates for all required domains

**When to use:**

- When setting up SSL for the first time
- When adding new domains that need SSL certificates
- When certificates expire or need to be regenerated

### `.env.sh`

Ensures environment files exist for both frontend and backend.

```bash
./scripts/env.sh
```

**What it does:**

- Creates `.env` files by copying from `.env.example` if they don't exist

**When to use:**

- Typically called by other scripts
- When you need to ensure environment files exist without running the full setup

## Service Management Scripts

### `start.sh`

Starts all Docker containers for the QuVel Kit environment.

```bash
./scripts/start.sh
```

**What it does:**

- Starts all Docker containers defined in `docker/docker-compose.yml`
- Shows a list of running containers

**When to use:**

- To start the environment after it has been stopped
- After system restart

### `stop.sh`

Stops all Docker containers for the QuVel Kit environment.

```bash
./scripts/stop.sh
```

**What it does:**

- Stops all Docker containers defined in `docker/docker-compose.yml`

**When to use:**

- When you want to stop the environment
- Before system shutdown
- When you need to free up system resources

### `restart.sh`

Restarts all Docker containers for the QuVel Kit environment.

```bash
./scripts/restart.sh
```

**What it does:**

- Stops all containers using `stop.sh`
- Starts all containers using `start.sh`

**When to use:**

- After making configuration changes
- When services are not responding correctly
- After updating SSL certificates

### `reset.sh`

Completely resets the QuVel Kit environment, removing all containers, volumes, and SSL certificates.

```bash
./scripts/reset.sh
```

**What it does:**

- Stops all Docker containers
- Removes all Docker volumes (including databases)
- Removes all SSL certificates
- Prepares the environment for a fresh setup

**When to use:**

- When you want to start with a clean environment
- When troubleshooting persistent issues
- When major configuration changes require a fresh start

### `deploy-mode.sh`

Switches between different deployment configurations without running full setup.

```bash
# Switch to minimal resource mode
./scripts/deploy-mode.sh minimal

# Switch to full Docker mode
./scripts/deploy-mode.sh docker

# Switch to fully local mode
./scripts/deploy-mode.sh local

# Show current deployment mode
./scripts/deploy-mode.sh current
```

**What it does:**

- Updates Traefik configuration files to match the selected deployment mode
- Comments/uncomments appropriate server URLs in backend.yml and frontend.yml
- Provides instructions for manual service management after switching
- Shows current deployment mode when called without arguments

**When to use:**

- When you want to switch deployment modes without full reset
- To check which deployment mode is currently active
- After changing development requirements or resource constraints

## Utility Scripts

### `log.sh`

Shows logs from all Docker containers.

```bash
./scripts/log.sh
```

**What it does:**

- Displays the last 100 log lines from all containers
- Continues to show new log entries as they occur (follows logs)

**When to use:**

- When debugging issues
- When monitoring application behavior
- When checking for errors across all services

### `capacitor.sh`

Updates Capacitor configuration for iOS development.

**THIS SCRIPT NEEDS TO BE RAN AFTER `yarn dev:ios` AND BEFORE pressing BUILD in Xcode.**

```bash
./scripts/capacitor.sh
```

**What it does:**

- Prompts for your LAN IP address
- Updates the Capacitor configuration to use your LAN IP
- Configures the app to use HTTPS

**Why LAN IP is required:**

Capacitor has a specific requirement for mobile development that differs from regular web development:

1. Capacitor strictly uses the URL specified in `capacitor.config.json` and cannot be overridden at runtime
2. This URL must be accessible from your physical iOS device on the same network
3. Using `localhost` or `127.0.0.1` won't work because those addresses refer to the device itself, not your development machine
4. The LAN IP (e.g., `192.168.1.X`) allows your iOS device to connect to your development machine

The script creates a special tenant URL using your LAN IP (e.g., `https://cap-tenant.quvel.192.168.1.X.nip.io`) that:

- Works with the SSL certificates generated during setup
- Is accessible from any device on your local network
- Maintains secure HTTPS connections required by Capacitor

**When to use:**

- After running `yarn dev:ios` to create the initial Capacitor project
- Before building the app in Xcode
- When your LAN IP changes
- When setting up mobile development for the first time

## Best Practices

### Script Usage Patterns

1. **Initial Setup**

   ```bash
   ./scripts/setup.sh
   ```

2. **Daily Development Workflow**

   ```bash
   # Start the environment
   ./scripts/start.sh
   
   # View logs when needed
   ./scripts/log.sh
   
   # Stop when done
   ./scripts/stop.sh
   ```

3. **Troubleshooting**

   ```bash
   # Restart services
   ./scripts/restart.sh
   
   # If issues persist, reset and setup again
   ./scripts/reset.sh
   ./scripts/setup.sh
   ```

4. **Mobile Development**

   ```bash
   # Update Capacitor config
   ./scripts/capacitor.sh
   ```

### Environment Variables

The scripts use environment variables from the following files:

- `backend/.env` - Laravel environment variables
- `frontend/.env` - Quasar environment variables

If you need to customize the environment, edit these files after they're created.

---

[← Back to Docs](./README.md)
