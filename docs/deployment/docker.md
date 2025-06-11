# Docker Configuration

QuVel Kit includes comprehensive Docker support for both development and production environments. This guide covers the Docker setup, service configuration, and deployment options.

## Overview

The Docker configuration supports flexible deployment scenarios:

- **Full containerization**: All services in Docker
- **Hybrid setup**: Traefik in Docker, services local
- **Development tools**: Coverage reports, testing, and monitoring

---

## Docker Compose Structure

### Service Architecture

```yaml
# docker/docker-compose.yml
services:
  traefik:      # Reverse proxy & SSL termination
  app:          # Laravel API backend
  frontend:     # Quasar SSR frontend
  mysql:        # Database
  redis:        # Cache & session store
  coverage:     # PHPUnit coverage reports
  vitest-ui:    # Frontend test interface
```

### Service Dependencies

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Browser   │───▶│   Traefik   │───▶│   Services  │
└─────────────┘    └─────────────┘    └─────────────┘
                          │                   │
                          ▼                   ▼
                   ┌─────────────┐    ┌─────────────┐
                   │ SSL Certs   │    │   Network   │
                   └─────────────┘    └─────────────┘
```

---

## Core Services

### Traefik (Reverse Proxy)

```yaml
traefik:
  image: traefik:v2.10
  container_name: quvel-traefik
  ports:
    - '80:80'       # HTTP
    - '443:443'     # HTTPS
    - '8080:8080'   # Dashboard
  volumes:
    - '/var/run/docker.sock:/var/run/docker.sock:ro'
    - '${PWD}/docker/certs:/certs:ro'
    - '${PWD}/docker/traefik/traefik.yml:/etc/traefik/traefik.yml:ro'
    - '${PWD}/docker/traefik/dynamic:/traefik:ro'
```

**Purpose:**

- SSL termination for all services
- Domain-based routing
- WebSocket proxying
- Development dashboard

### Laravel Backend

```yaml
app:
  build:
    context: ../backend
    dockerfile: ../docker/backend.Dockerfile
  container_name: quvel-app
  volumes:
    - ../backend:/var/www
    - /var/www/vendor    # Performance: Keep dependencies in container
  command: ['composer install --dev && php artisan serve --host=0.0.0.0 --port=8000']
```

**Features:**

- Live code reloading
- Composer dependency management
- Artisan command access
- Volume mounting for development

### Quasar Frontend

```yaml
frontend:
  build:
    context: ../frontend
    dockerfile: ../docker/frontend.Dockerfile
  container_name: quvel-frontend
  volumes:
    - ../frontend:/frontend
    - /frontend/node_modules  # Performance: Keep dependencies in container
  command: ['yarn install && yarn dev:ssr']
```

**Features:**

- Hot Module Replacement (HMR)
- SSR development server
- Yarn dependency management
- SSL certificate access

### Database Services

```yaml
mysql:
  image: mysql:8
  environment:
    MYSQL_ROOT_PASSWORD: root
    MYSQL_DATABASE: quvel
    MYSQL_USER: quvel_user
    MYSQL_PASSWORD: quvel_password
  volumes:
    - mysql_data:/var/lib/mysql  # Persistent storage

redis:
  image: redis:latest
  ports:
    - '6379:6379'
```

---

## Development Tools

### Coverage Reports

```yaml
coverage:
  image: nginx:latest
  volumes:
    - ../backend/storage/coverage:/usr/share/nginx/html:ro
  labels:
    - 'traefik.http.routers.coverage.rule=Host(`coverage-api.quvel.127.0.0.1.nip.io`)'
```

**Access:** `https://coverage-api.quvel.127.0.0.1.nip.io`

### Vitest UI

```yaml
vitest-ui:
  volumes:
    - ../frontend:/frontend
  command: ['yarn test:unit:ui']
  labels:
    - 'traefik.http.routers.vitest-ui.rule=Host(`coverage.quvel.127.0.0.1.nip.io`)'
```

**Access:** `https://coverage.quvel.127.0.0.1.nip.io`

---

## Dockerfile Configuration

### Backend Dockerfile

```dockerfile
# docker/backend.Dockerfile
FROM php:8.3-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
```

### Frontend Dockerfile

```dockerfile
# docker/frontend.Dockerfile
FROM node:22-alpine

# Install system dependencies
RUN apk add --no-cache git python3 make g++

WORKDIR /frontend

# Copy package files
COPY package*.json yarn.lock ./

# Install dependencies
RUN yarn install --frozen-lockfile

# Copy source code
COPY . .
```

---

## Development Workflows

### Standard Development (Hybrid Setup)

**Services in Docker:**

- Traefik (reverse proxy)
- MySQL (database)
- Redis (cache)

**Services Local:**

- Laravel (`php artisan serve`)
- Quasar (`quasar dev`)

**Commands:**

```bash
# Start infrastructure
./scripts/start.sh

# Start backend locally
cd backend && php artisan serve --host=0.0.0.0 --port=8000

# Start frontend locally  
cd frontend && quasar dev --port 3000
```

### Full Docker Development

**All services in Docker containers**

**Commands:**

```bash
# Update traefik configuration (uncomment Docker URLs)
# Edit: docker/traefik/dynamic/backend.yml
# Edit: docker/traefik/dynamic/frontend.yml

# Start all services
./scripts/start.sh
```

### Docker-only Infrastructure

**Minimal Docker footprint - only essential services**

```yaml
# Minimal docker-compose.yml
services:
  traefik:
    # ... traefik config
  mysql:
    # ... mysql config  
  redis:
    # ... redis config
```

---

## Service Management

### Starting Services

```bash
# Start all services
./scripts/start.sh

# Start specific service
docker-compose -f docker/docker-compose.yml up traefik

# Start with logs
docker-compose -f docker/docker-compose.yml up -d && ./scripts/log.sh
```

### Stopping Services

```bash
# Stop all services
./scripts/stop.sh

# Stop specific service
docker-compose -f docker/docker-compose.yml stop app
```

### Restarting Services

```bash
# Restart all services
./scripts/restart.sh

# Restart specific service
docker-compose -f docker/docker-compose.yml restart traefik
```

### Rebuilding Services

```bash
# Rebuild all services
docker-compose -f docker/docker-compose.yml build

# Rebuild specific service
docker-compose -f docker/docker-compose.yml build app

# Rebuild without cache
docker-compose -f docker/docker-compose.yml build --no-cache
```

---

## Volume Management

### Development Volumes

| Volume | Purpose | Performance Impact |
|--------|---------|-------------------|
| `../backend:/var/www` | Live code editing | Medium |
| `../frontend:/frontend` | Live code editing | Medium |
| `/var/www/vendor` | PHP dependencies | High (in container) |
| `/frontend/node_modules` | Node dependencies | High (in container) |

### Production Volumes

```yaml
volumes:
  mysql_data:           # Database persistence
  redis_data:           # Cache persistence (optional)
  app_storage:          # Laravel storage
  ssl_certificates:     # SSL certificates
```

---

## Networking

### Docker Network

```yaml
networks:
  quvel-network:
    driver: bridge
```

**Service Communication:**

- Internal: Service names (`quvel-app`, `quvel-mysql`)
- External: Host ports (3306, 6379, 8080)
- Web: Traefik routing (*.nip.io domains)

### Port Mapping

| Service | Internal Port | External Port | Purpose |
|---------|---------------|---------------|---------|
| Traefik | 80/443 | 80/443 | Web traffic |
| Traefik Dashboard | 8080 | 8080 | Monitoring |
| MySQL | 3306 | 3306 | Database access |
| Redis | 6379 | 6379 | Cache access |

---

## Environment Variables

### Backend Environment

```bash
# backend/.env
DB_HOST=127.0.0.1          # Local MySQL
# DB_HOST=quvel-mysql      # Docker MySQL

REDIS_HOST=127.0.0.1       # Local Redis  
# REDIS_HOST=quvel-redis   # Docker Redis

APP_URL=https://api.quvel.127.0.0.1.nip.io
```

### Frontend Environment

```bash
# frontend/.env
VITE_API_URL=https://api.quvel.127.0.0.1.nip.io
VITE_APP_URL=https://quvel.127.0.0.1.nip.io
```

---

## Performance Optimization

### Development Performance

1. **Keep dependencies in containers:**

   ```yaml
   volumes:
     - /var/www/vendor      # Don't sync to host
     - /frontend/node_modules
   ```

2. **Use bind mounts for source code:**

   ```yaml
   volumes:
     - ../backend:/var/www   # Live editing
     - ../frontend:/frontend
   ```

3. **Exclude heavy directories:**

   ```dockerfile
   # .dockerignore
   node_modules
   vendor
   .git
   storage/logs
   ```

### Resource Limits

```yaml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
```

---

## Troubleshooting

### Common Issues

#### 1. Permission Problems

```bash
# Fix Laravel permissions
docker exec quvel-app chown -R www-data:www-data /var/www/storage
docker exec quvel-app chmod -R 775 /var/www/storage
```

#### 2. Port Conflicts

```bash
# Check port usage
lsof -i :3306
lsof -i :6379

# Stop conflicting services
brew services stop mysql
brew services stop redis
```

#### 3. Volume Mount Issues

```bash
# Check volume mounts
docker inspect quvel-app | grep Mounts -A 10

# Recreate volumes
docker-compose down -v
docker-compose up -d
```

#### 4. Network Connectivity

```bash
# Test internal connectivity
docker exec quvel-app ping quvel-mysql
docker exec quvel-app ping quvel-redis

# Check network
docker network ls
docker network inspect docker_quvel-network
```

### Debug Commands

```bash
# Container logs
docker logs -f quvel-app
docker logs -f quvel-frontend

# Container shell access
docker exec -it quvel-app bash
docker exec -it quvel-frontend sh

# Resource usage
docker stats

# Service health
docker-compose ps
```

---

## Production Deployment

### Security Considerations

1. **Remove development tools:**

   ```yaml
   # Remove from production:
   # - vitest-ui
   # - coverage
   # - traefik dashboard
   ```

2. **Environment security:**

   ```bash
   # Use Docker secrets
   echo "password123" | docker secret create mysql_password -
   ```

3. **Network isolation:**

   ```yaml
   networks:
     frontend:
       external: false
     backend:
       external: false
   ```

### Production docker-compose.yml

```yaml
version: '3.8'
services:
  traefik:
    # Remove dashboard
    # Add Let's Encrypt
    # Add monitoring
    
  app:
    # Remove dev dependencies
    # Add health checks
    # Configure logging
    
  frontend:
    # Build production assets
    # Configure caching
    # Add CDN integration
```

---

[← Back to Deployment](./README.md)
