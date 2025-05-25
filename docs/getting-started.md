# Getting Started with QuVel Kit

## Prerequisites

### Required Software

| Software | Version | Purpose |
|----------|---------|--------|
| Docker | 20.10+ | Container platform |
| Docker Compose | 2.0+ | Multi-container orchestration |
| Node.js | 18.0+ | JavaScript runtime |
| Yarn | 1.22+ | Package management |
| mkcert | Latest | SSL certificate generation |

### Platform-Specific Requirements

#### Mobile Development

- **iOS**: Xcode 12.0+, iOS Simulator 12.0+
- **Android**: Android Studio with latest SDK tools

#### Desktop Development

- **Electron**: Node.js 18.0+

### Verification

Verify your environment with these commands:

```bash
# Check Docker installation
docker -v
docker-compose -v

# Check Node.js and Yarn
node -v
yarn -v

# Install mkcert certificates in your system trust store
mkcert -install
```

## Installation

### 1. Clone Repository

```bash
git clone https://github.com/ItsIrv/quvel-kit.git
cd quvel-kit
```

### 2. Run Setup Script

Get your local IP address first, the setup script will need it. This will be your second tenant's domain.

The setup script automates the following tasks:

- Generates SSL certificates
- Creates Docker networks
- Builds Docker images
- Installs dependencies
- Starts all services

```bash
./scripts/setup.sh
```

### 3. Access Your Application

Once setup completes, access the application at these URLs:

| Service | URL | Description |
|---------|-----|-------------|
| Frontend | [https://quvel.127.0.0.1.nip.io](https://quvel.127.0.0.1.nip.io) | Quasar SSR application |
| API | [https://api.quvel.127.0.0.1.nip.io](https://api.quvel.127.0.0.1.nip.io) | Laravel API |
| API Telescope | [https://api.quvel.127.0.0.1.nip.io/telescope](https://api.quvel.127.0.0.1.nip.io/telescope) | Laravel debugging |
| Coverage | [https://coverage-api.quvel.127.0.0.1.nip.io](https://coverage-api.quvel.127.0.0.1.nip.io) | Test coverage reports |
| Traefik | [http://localhost:8080](http://localhost:8080) | Reverse proxy dashboard |

## Common Commands

| Action | Command | Description |
|--------|---------|-------------|
| Start | `./scripts/start.sh` | Start all services |
| Stop | `./scripts/stop.sh` | Stop all services |
| Restart | `./scripts/restart.sh` | Restart all services |
| Logs | `./scripts/log.sh` | View service logs |
| Reset | `./scripts/reset.sh` | Reset entire environment |

For detailed information about all available scripts, see the [Utility Scripts](./scripts.md) documentation.

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

- [Frontend Documentation](./frontend/README.md)
- [Backend Documentation](./backend/README.md)
- [Traefik Documentation](./traefik-structure.md)

---

[← Back to Docs](./README.md)
