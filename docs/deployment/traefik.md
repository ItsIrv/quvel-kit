# Traefik Configuration

Traefik serves as QuVel Kit's reverse proxy, handling SSL termination, domain routing, and WebSocket support. This guide explains the configuration structure and deployment flexibility.

## Why Traefik?

### Core Requirements
- **HTTPS Everywhere**: iOS devices and Capacitor require secure connections
- **WebSocket Support**: Hot Module Replacement (HMR) needs WebSocket proxying
- **Multi-domain Routing**: Tenant-based subdomain routing
- **SSL Termination**: Single point for certificate management

### Development Benefits
- **Production Parity**: Development mirrors production routing
- **Mobile Compatibility**: Works with iOS/Android development
- **Certificate Management**: Automatic HTTPS for all services

---

## Architecture Overview

```
Browser (HTTPS) → Traefik (SSL/Routing) → Backend Services
                     ↓
              [Certificate Management]
              [Domain Routing Rules]
              [WebSocket Proxying]
```

### Key Components:

| Component | Purpose | Location |
|-----------|---------|----------|
| **Main Config** | Entry points, SSL settings | `docker/traefik/traefik.yml` |
| **Dynamic Routes** | Service routing rules | `docker/traefik/dynamic/` |
| **Certificates** | SSL certificates | `docker/certs/` |

---

## Configuration Structure

### Main Configuration (`traefik.yml`)

```yaml
# Entry points for HTTP/HTTPS traffic
entryPoints:
  web:
    address: ':80'      # HTTP entry point
  websecure:
    address: ':443'     # HTTPS entry point
    http:
      tls: {}          # Enable TLS

# Default SSL certificate
tls:
  stores:
    default:
      defaultCertificate:
        certFile: '/certs/selfsigned.crt'
        keyFile: '/certs/selfsigned.key'

# Dynamic configuration directory
providers:
  file:
    directory: /traefik    # Watches dynamic config files
    watch: true           # Auto-reload on changes
```

### Dynamic Configuration Files

#### Backend Routing (`backend.yml`)

Handles API requests to Laravel backend:

```yaml
http:
  routers:
    api-local:
      rule: 'Host(`api.quvel.127.0.0.1.nip.io`)'
      entryPoints: websecure
      service: api-local
      tls: {}

  services:
    api-local:
      loadBalancer:
        servers:
          # Multiple options - choose based on deployment:
          - url: 'http://host.docker.internal:8000'  # ✅ Local PHP
          # - url: 'http://quvel-app:8000'           # ❌ Docker container
          # - url: 'http://127.0.0.1:8000'           # ❌ Direct local
```

#### Frontend Routing (`frontend.yml`)

Handles web app and WebSocket requests:

```yaml
http:
  routers:
    web:
      rule: 'Host(`quvel.127.0.0.1.nip.io`)'
      service: web
      
    web-ws:
      rule: 'Host(`quvel.127.0.0.1.nip.io`) && PathPrefix(`/hmr`)'
      service: web-ws     # Dedicated WebSocket routing

  services:
    web:
      loadBalancer:
        servers:
          # Multiple options - choose based on deployment:
          - url: 'https://host.docker.internal:3000'  # ✅ Local Quasar
          # - url: 'https://quvel-frontend:9000'       # ❌ Docker container
        serversTransport: 'insecureTransport'         # Accept self-signed certs
```

---

## Deployment Scenarios Explained

The configuration files contain multiple commented server URLs. Each represents a different deployment scenario:

### 1. Minimal Resource (Current Active Configuration)

**What's running:**
- Traefik: Docker container
- Backend: Local PHP (`php artisan serve`)
- Frontend: Local Quasar (`quasar dev`)

**Configuration:**
```yaml
# backend.yml
- url: 'http://host.docker.internal:8000'  # ✅ Points to local PHP

# frontend.yml  
- url: 'https://host.docker.internal:3000' # ✅ Points to local Quasar
```

**Why `host.docker.internal`?**
- Traefik runs in Docker and needs to reach the host machine
- `host.docker.internal` is Docker's way to access host services
- Alternative to exposing host IP addresses

### 2. Full Docker (Commented Out)

**What would run:**
- All services: Docker containers
- Communication: Internal Docker network

**Configuration:**
```yaml
# backend.yml
- url: 'http://quvel-app:8000'           # ❌ Docker service name

# frontend.yml
- url: 'https://quvel-frontend:9000'     # ❌ Docker service name
```

**Why service names?**
- Docker containers communicate using service names
- No need for `host.docker.internal` within Docker network
- Internal networking is faster and more secure

### 3. Fully Local (Also Commented)

**What would run:**
- Traefik: Local installation (Homebrew)
- All services: Local processes

**Configuration:**
```yaml
# backend.yml
- url: 'http://127.0.0.1:8000'          # ❌ Direct local access

# frontend.yml
- url: 'https://127.0.0.1:3000'         # ❌ Direct local access
```

**Why direct IPs?**
- No Docker involved, direct process communication
- Fastest possible routing
- Requires Traefik installed locally

---

## Special Routing Features

### WebSocket Support

Frontend configuration includes dedicated WebSocket routing:

```yaml
web-ws:
  rule: 'Host(`quvel.127.0.0.1.nip.io`) && PathPrefix(`/hmr`)'
  service: web-ws
  middlewares:
    - websocket-headers

middlewares:
  websocket-headers:
    headers:
      customRequestHeaders:
        Connection: 'Upgrade'
        Upgrade: 'websocket'
```

**Purpose:**
- Enables Vite Hot Module Replacement
- Ensures WebSocket connections are properly proxied
- Required for frontend live reload

### Mobile Development (Capacitor)

Special routing for iOS/Android development:

```yaml
capacitor:
  rule: 'Host(`cap-tenant.quvel.127.0.0.1.nip.io`)'
  service: capacitor
```

**Purpose:**
- Provides dedicated tenant for mobile testing
- Uses LAN IP for device access
- Maintains HTTPS requirement for Capacitor

### LAN IP Support

Configuration includes LAN IP routing:

```yaml
rule: 'Host(`quvel.192.168.86.21.nip.io`)' # REPLACE_WITH_LOCAL_IP
```

**Purpose:**
- Enables access from other devices on network
- Required for mobile device testing
- Automatically updated by setup scripts

---

## Certificate Management

### SSL Certificate Structure

```
docker/certs/
├── ca.pem              # Certificate Authority
├── selfsigned.crt      # Main certificate
├── selfsigned.key      # Private key
└── certificates.yaml   # Traefik certificate config
```

### Certificate Generation

Certificates are generated by `./scripts/ssl.sh`:

1. **Install mkcert**: Local Certificate Authority
2. **Generate CA**: Trusted by system/browsers
3. **Create certificates**: Cover all development domains
4. **LAN support**: Include LAN IP addresses

### Supported Domains

Generated certificates cover:
- `*.127.0.0.1.nip.io` (local development)
- `*.192.168.x.x.nip.io` (LAN access)
- `localhost` (direct access)

---

## Switching Deployment Modes

### To Switch from Minimal to Full Docker:

1. **Update backend.yml:**
   ```yaml
   # Comment out:
   # - url: 'http://host.docker.internal:8000'
   
   # Uncomment:
   - url: 'http://quvel-app:8000'
   ```

2. **Update frontend.yml:**
   ```yaml
   # Comment out:  
   # - url: 'https://host.docker.internal:3000'
   
   # Uncomment:
   - url: 'https://quvel-frontend:9000'
   ```

3. **Restart Traefik:**
   ```bash
   ./scripts/restart.sh
   ```

### To Switch to Fully Local:

1. **Install Traefik locally:**
   ```bash
   brew install traefik
   ```

2. **Update configuration paths:**
   ```yaml
   # Use direct local addresses
   - url: 'http://127.0.0.1:8000'
   - url: 'https://127.0.0.1:3000'
   ```

3. **Run Traefik locally:**
   ```bash
   traefik --configfile=docker/traefik/traefik.yml
   ```

---

## Troubleshooting

### Common Issues

#### 1. Services Not Accessible
```bash
# Check Traefik logs
docker logs -f quvel-traefik

# Verify routes
docker exec -it quvel-traefik traefik routes
```

#### 2. SSL Certificate Errors
```bash
# Regenerate certificates
./scripts/ssl.sh

# Check certificate validity
openssl x509 -in docker/certs/selfsigned.crt -text -noout
```

#### 3. WebSocket Connection Issues
- Verify WebSocket middleware is configured
- Check that HMR paths are correctly routed
- Ensure HTTPS is used end-to-end

#### 4. Wrong Service URLs
- Check which deployment mode you're using
- Verify correct URLs are uncommented
- Ensure services are running on expected ports

### Debug Commands

```bash
# View Traefik dashboard
open http://localhost:8080

# Check running containers
docker ps | grep quvel

# Test connectivity
curl -k https://api.quvel.127.0.0.1.nip.io/api/health

# View Traefik config
docker exec quvel-traefik cat /etc/traefik/traefik.yml
```

---

## Production Considerations

### Security
- Disable dashboard in production
- Use proper SSL certificates (Let's Encrypt)
- Implement rate limiting
- Configure proper CORS headers

### Performance
- Enable compression
- Configure caching headers
- Use connection pooling
- Monitor resource usage

### Monitoring
- Enable access logs
- Configure metrics collection
- Set up health checks
- Monitor certificate expiration

---

[← Back to Deployment](./README.md)