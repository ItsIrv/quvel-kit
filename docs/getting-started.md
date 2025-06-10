# Getting Started with QuVel Kit

## Prerequisites

### Required Software

QuVel Kit supports multiple deployment modes. Choose the requirements based on your preferred setup:

#### All Modes
| Software | Version | Purpose |
|----------|---------|--------|
| Node.js | 18.0+ | JavaScript runtime |
| Yarn | 1.22+ | Package management |
| mkcert | Latest | SSL certificate generation |

#### Traefik-Only Mode (Default)
| Software | Version | Purpose |
|----------|---------|--------|
| Docker | 20.10+ | For Traefik container only |
| PHP | 8.3+ | Backend development |
| Composer | 2.0+ | PHP dependency management |
| MySQL | 8.0+ | Database (via Homebrew) |
| Redis | 6.0+ | Cache/sessions (via Homebrew) |

#### Full Docker Mode
| Software | Version | Purpose |
|----------|---------|--------|
| Docker | 20.10+ | Container platform |
| Docker Compose | 2.0+ | Multi-container orchestration |

### Platform-Specific Requirements

#### Mobile Development

- **iOS**: Xcode 12.0+, iOS Simulator 12.0+
- **Android**: Android Studio with latest SDK tools

#### Desktop Development

- **Electron**: Node.js 18.0+

### Verification

Verify your environment based on your chosen deployment mode:

```bash
# All modes
node -v
yarn -v
mkcert -install

# Traefik-only mode (default)
docker -v           # For Traefik container
php -v              # For local backend
composer -V         # For PHP dependencies
mysql -V            # For local database
redis-server -v     # For local cache

# Full Docker mode
docker -v
docker-compose -v
```

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/ItsIrv/quvel-kit.git
cd quvel-kit
```

### 2. Choose Your Deployment Mode

QuVel Kit offers flexible deployment options. Choose the one that fits your needs:

```bash
# Traefik-only mode (default) - only Traefik in Docker, everything else local
./scripts/setup.sh

# Minimal mode - Traefik + databases in Docker, services local  
./scripts/setup.sh --mode=minimal

# Full Docker mode - all services in Docker
./scripts/setup.sh --mode=docker

# Local mode - everything local (requires local Traefik)
./scripts/setup.sh --mode=local
```

The setup script automates different tasks based on your chosen mode:

#### Traefik-Only Mode (Default)
- Generates SSL certificates
- Starts only Traefik container
- Provides instructions for local service setup

#### Full Docker Mode
- Generates SSL certificates
- Builds and starts all Docker containers
- Installs dependencies automatically
- Runs database migrations

#### Other Modes
See [Deployment Options](./deployment/deployment-options.md) for detailed setup instructions.

### 3. Start Services and Access Your Application

#### For Traefik-Only Mode (Default)

After setup, start your local services:

```bash
# Start local services
brew services start mysql
brew services start redis

# Setup backend
cd backend
composer install
php artisan key:generate
php artisan migrate:fresh --seed

# Start backend (in terminal 1)
php artisan serve --host=0.0.0.0 --port=8000

# Start frontend (in terminal 2)
cd frontend
quasar dev --port 3000
```

#### For Full Docker Mode

Services start automatically. No additional steps needed.

#### Access URLs

Once running, access the application at these URLs:

| Service | URL | Description |
|---------|-----|-------------|
| Frontend | [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io) | Quasar SSR application |
| API | [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io) | Laravel API |
| API Telescope | [https://api.quvel.127.0.0.1.nip.io/telescope](https://api.quvel.127.0.0.1.nip.io/telescope) | Laravel debugging |
| Coverage | [https://coverage-api.quvel.127.0.0.1.nip.io](https://coverage-api.quvel.127.0.0.1.nip.io) | Test coverage reports* |
| Traefik | [http://localhost:8080](http://localhost:8080) | Reverse proxy dashboard |

*Available in full Docker mode only

## Common Commands

| Action | Command | Description |
|--------|---------|-------------|
| Start | `./scripts/start.sh` | Start all services |
| Stop | `./scripts/stop.sh` | Stop all services |
| Restart | `./scripts/restart.sh` | Restart all services |
| Logs | `./scripts/log.sh` | View service logs |
| Reset | `./scripts/reset.sh` | Reset entire environment |

For detailed information about all available scripts, see the [Development Scripts](./deployment/scripts.md) documentation.

## Multi-Tenant Development

QuVel Kit supports multi-tenant applications with dynamic configuration. To test with a second tenant:

```bash
# Access second tenant
https://second-tenant.quvel.127.0.0.1.nip.io
```

### Dynamic Tenant Configuration

The new dynamic configuration system allows flexible tenant-specific settings:

```php
// Example: Configure a tenant with custom settings
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

$tenant = Tenant::find(1);
$config = new DynamicTenantConfig(
    [
        'app_name' => 'Custom App Name',
        'mail_from_address' => 'support@customapp.com',
        'socialite_providers' => ['google', 'microsoft'],
        'custom_feature_enabled' => true,
    ],
    [
        'app_name' => TenantConfigVisibility::PUBLIC,
        'socialite_providers' => TenantConfigVisibility::PUBLIC,
        'custom_feature_enabled' => TenantConfigVisibility::PUBLIC,
    ],
    'standard' // Tenant tier
);

$tenant->config = $config;
$tenant->save();
```

### Tenant Tiers

QuVel Kit supports different tenant isolation levels:

| Tier | Database | Cache | Redis | Use Case |
|------|----------|-------|-------|----------|
| **Basic** | Shared | Shared | Shared | Small tenants, cost-effective |
| **Standard** | Shared | Dedicated | Dedicated | Medium tenants, better performance |
| **Premium** | Dedicated | Dedicated | Dedicated | Large tenants, full isolation |
| **Enterprise** | Dedicated | Dedicated | Dedicated | Custom infrastructure needs |

### Module Configuration Integration

Modules can provide tenant-specific configuration:

```php
// In your module's service provider
public function boot(): void
{
    parent::boot();
    
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            // Register configuration pipe for runtime config
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                \Modules\YourModule\Pipes\YourModuleConfigPipe::class
            );
            
            // Register config provider for frontend exposure
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                \Modules\YourModule\Providers\YourModuleTenantConfigProvider::class
            );
        });
    }
}
```

## Next Steps

### Development
- [Frontend Documentation](./frontend/README.md) - Vue, Quasar, and TypeScript development
- [Backend Documentation](./backend/README.md) - Laravel API development and modules

### Deployment & Infrastructure
- [Deployment Options](./deployment/deployment-options.md) - Choose the right setup for your needs
- [Traefik Configuration](./deployment/traefik.md) - Reverse proxy and SSL setup
- [Development Scripts](./deployment/scripts.md) - Automation tools for common tasks

### Additional Resources
- [Troubleshooting](./troubleshooting.md) - Common issues and solutions
- [Folder Structure](./folder-structure.md) - Project organization overview

---

[← Back to Docs](./README.md)
