# Troubleshooting Guide

This guide covers common issues across different deployment modes. Issues are organized by category with mode-specific solutions where applicable.

## Deployment Mode Issues

### Wrong Deployment Mode

If services aren't working as expected, check your current deployment mode:

```bash
# Check current deployment mode
./scripts/deploy-mode.sh current

# Switch to traefik-only mode (most minimal)
./scripts/deploy-mode.sh traefik-only

# Switch to full Docker mode  
./scripts/deploy-mode.sh docker
```

### Traefik-Only Mode Issues

**Services not accessible:**
```bash
# Ensure local services are running
brew services list | grep -E "(mysql|redis)"
brew services start mysql
brew services start redis

# Check if backend is running
curl http://127.0.0.1:8000/api/health

# Check if frontend is running  
curl http://127.0.0.1:3000
```

**Database connection issues:**
```bash
# Test local MySQL connection
mysql -u root -p -h 127.0.0.1

# Update backend .env for local database
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=quvel
```

### Full Docker Mode Issues

**Resource constraints:**
```bash
# Check Docker resource usage
docker stats

# Consider switching to traefik-only mode
./scripts/deploy-mode.sh traefik-only
```

## SSL Certificate Issues

### Browser Security Warnings

If you see certificate warnings in your browser:

```bash
# 1. Reinstall mkcert certificates in your system trust store
mkcert -install

# 2. Regenerate project certificates with all domains
mkcert -cert-file docker/certs/selfsigned.crt -key-file docker/certs/selfsigned.key \
  quvel.127.0.0.1.nip.io \
  api.quvel.127.0.0.1.nip.io \
  coverage-api.quvel.127.0.0.1.nip.io \
  coverage.quvel.127.0.0.1.nip.io \
  second-tenant.quvel.127.0.0.1.nip.io

# 3. Verify certificates exist
ls docker/certs/

# 4. Restart services to apply changes
./scripts/restart.sh
```

### Certificate Not Recognized by Mobile/Desktop Apps

Capacitor and Electron require proper SSL certificates:

```bash
# Export mkcert root CA to a location accessible by your apps
mkcert -CAROOT

# Copy the rootCA.pem file to your project
cp "$(mkcert -CAROOT)/rootCA.pem" ./frontend/src-capacitor/
```

## Docker Environment Issues

### Container Startup Failures

```bash
# Check container status
docker ps -a

# View detailed logs for specific containers
docker logs quvel-app
docker logs quvel-frontend
docker logs quvel-mysql
docker logs quvel-traefik

# Restart all services
./scripts/restart.sh
```

### Port Conflicts

If you see errors about ports being in use:

```bash
# Find processes using specific ports
sudo lsof -i :80
sudo lsof -i :443
sudo lsof -i :3306

# Edit port mappings in docker-compose.yml if needed
```

### Performance Issues

```bash
# Remove unused Docker resources
docker system prune -a

# Remove all volumes (will delete database data)
docker system prune -a --volumes

# Check Docker Desktop resource allocation (macOS/Windows)
```

## Database Issues

### Connection Failures

#### Traefik-Only Mode
```bash
# Check if local MySQL is running
brew services list | grep mysql
brew services start mysql

# Test connection
mysql -u root -p -h 127.0.0.1

# Check backend .env configuration
grep DB_ backend/.env
```

#### Full Docker Mode
```bash
# Check if MySQL container is running
docker ps | grep mysql

# Connect to MySQL directly
docker exec -it quvel-mysql mysql -u root -p

# Check MySQL logs
docker logs quvel-mysql
```

### Migration Errors

#### Traefik-Only Mode
```bash
# Run migrations locally
cd backend
php artisan migrate --force

# Reset database completely
php artisan migrate:fresh --seed
```

#### Full Docker Mode
```bash
# Run migrations in container
docker exec -it quvel-app php artisan migrate --force

# Reset database completely
docker exec -it quvel-app php artisan migrate:fresh --seed
```

## Frontend Issues

### SSR Rendering Problems

#### Traefik-Only Mode
```bash
# Check frontend process
ps aux | grep "quasar dev"

# Restart frontend development server
cd frontend
quasar dev --port 3000

# Clear browser cache and cookies
```

#### Full Docker Mode
```bash
# Check frontend logs
docker logs -f quvel-frontend

# Restart frontend container
docker restart quvel-frontend

# Clear browser cache and cookies
```

### Vite/HMR Not Working

#### All Modes
```bash
# Check if WebSockets are working through Traefik
docker logs quvel-traefik | grep websocket

# Test WebSocket connection
curl -i -N -H "Connection: Upgrade" -H "Upgrade: websocket" \
  https://quvel.127.0.0.1.nip.io/hmr
```

#### Traefik-Only Mode
```bash
# Restart frontend locally
cd frontend
yarn dev --port 3000
```

#### Full Docker Mode
```bash
# Restart frontend container
docker restart quvel-frontend
```

## Multi-Tenant Issues

### Tenant Not Found

```bash
# List all tenants
docker exec -it quvel-app php artisan tenant:list

# Check tenant cache
docker exec -it quvel-app php artisan tenant:cache-clear
```

## Network Issues

### Domain Resolution Problems

```bash
# Test domain resolution
ping quvel.127.0.0.1.nip.io

# Check your hosts file if using custom domains
cat /etc/hosts
```

### Traefik Routing Issues

```bash
# Check Traefik logs
docker logs quvel-traefik

# Verify Traefik configuration
cat docker/traefik/dynamic/frontend.yml
```

## Complete Environment Reset

When all troubleshooting fails, perform a complete reset:

### Traefik-Only Mode
```bash
# Stop Docker services
docker-compose -f docker/docker-compose.yml down

# Stop local services
brew services stop mysql
brew services stop redis

# Clear local backend cache
cd backend
php artisan cache:clear
php artisan config:clear
rm -rf vendor

# Reinstall from scratch
./scripts/setup.sh --mode=traefik-only
```

### Full Docker Mode
```bash
# Stop all containers and remove volumes
./scripts/reset.sh

# View logs for debugging
./scripts/log.sh

# Reinstall from scratch
./scripts/setup.sh --mode=docker
```

## Mode-Specific Quick Fixes

### Switching Modes for Troubleshooting

```bash
# Try traefik-only mode (most minimal)
./scripts/deploy-mode.sh traefik-only

# Try full Docker mode (more isolated)
./scripts/deploy-mode.sh docker

# Check what mode you're currently using
./scripts/deploy-mode.sh current
```

---

[← Back to Docs](./README.md)
