# Troubleshooting Guide

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

```bash
# Check if MySQL container is running
docker ps | grep mysql

# Connect to MySQL directly
docker exec -it quvel-mysql mysql -u root -p

# Check MySQL logs
docker logs quvel-mysql
```

### Migration Errors

```bash
# Run migrations with force flag
docker exec -it quvel-app php artisan migrate --force

# Reset database completely
docker exec -it quvel-app php artisan migrate:fresh --seed
```

## Frontend Issues

### SSR Rendering Problems

```bash
# Check frontend logs
docker logs -f quvel-frontend

# Restart frontend container
docker restart quvel-frontend

# Clear browser cache and cookies
```

### Vite/HMR Not Working

```bash
# Check if WebSockets are working through Traefik
docker logs quvel-traefik | grep websocket

# Restart frontend development server
cd frontend
yarn dev
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

```bash
# Stop all containers and remove volumes
./scripts/reset.sh

# View logs for debugging
./scripts/log.sh

# Reinstall from scratch
./scripts/setup.sh
```

---

[← Back to Docs](./README.md)
