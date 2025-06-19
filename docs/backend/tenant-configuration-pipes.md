# Tenant Configuration Pipes

## Overview

The QuVel Kit tenant system uses a powerful configuration pipeline pattern with 12 specialized pipes that apply tenant-specific configurations to Laravel's config repository. Each pipe handles different aspects of application configuration and runs in priority order (higher numbers execute first), enabling comprehensive multi-tenancy strategies from simple isolation to complete database-per-tenant setups.

The configuration pipeline runs during the `TenantMiddleware` execution, ensuring all tenant-specific settings are applied before any application logic executes.

## How Configuration Pipes Work

### Pipeline Execution

```php
TenantMiddleware → ConfigurationPipeline → Individual Pipes (by priority) → Laravel Config
```

Each pipe:
1. Receives the current tenant, Laravel's config repository, and tenant configuration
2. Processes specific configuration keys it handles
3. Applies changes to Laravel's configuration
4. Passes control to the next pipe

### Pipe Interface

All pipes implement `ConfigurationPipeInterface`:

```php
interface ConfigurationPipeInterface
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed;
    public function handles(): array;     // Configuration keys this pipe processes
    public function priority(): int;     // Execution priority (higher = earlier)
}
```

## Built-in Configuration Pipes

### CoreConfigPipe (Priority: 100)

**Purpose**: Handles core Laravel application settings, CORS configuration, and frontend URL management.

**Configuration Keys**:
- Application: `app_name`, `app_env`, `app_key`, `app_debug`, `app_url`, `app_timezone`
- Localization: `app_locale`, `app_fallback_locale`
- URLs: `frontend_url`, `internal_api_url`, `capacitor_scheme`
- Pusher: `pusher_app_key`, `pusher_app_secret`, `pusher_app_id`, `pusher_app_cluster`

**Laravel Config Modified**:
- `app.*` - Application settings
- `frontend.*` - Frontend URL configuration  
- `cors.allowed_origins` - Dynamic CORS based on tenant URLs

**Special Features**:
- Refreshes URL Generator for tenant-specific URLs
- Handles X-Forwarded-Prefix for proxy environments
- Updates timezone and locale with service rebinding
- Exposes public configuration to frontend via `resolve()`

**Multi-tenancy Strategy**: Core application branding and URL management per tenant.

### DatabaseConfigPipe (Priority: 90)

**Purpose**: Enables complete database-per-tenant isolation.

**Configuration Keys**:
- `db_connection`, `db_host`, `db_port`, `db_database`
- `db_username`, `db_password`

**Laravel Config Modified**:
- `database.default` - Default connection name
- `database.connections.{connection}.*` - Connection parameters

**Multi-tenancy Strategy**: **Database Per Tenant**
- Switches entire database connection per tenant
- Purges and reconnects database manager
- Enables complete data isolation at database level
- Perfect for enterprise tenants requiring strict data separation

**Example Configuration**:
```php
[
    'db_connection' => 'mysql',
    'db_host' => 'tenant-db.example.com',
    'db_database' => 'tenant_enterprise_db',
    'db_username' => 'tenant_user',
    'db_password' => 'secure_password' // Private by default
]
```

### CacheConfigPipe (Priority: 85)

**Purpose**: Provides cache isolation between tenants using prefixes or separate stores.

**Configuration Keys**:
- `cache_store` - Cache store name (redis, file, etc.)
- `cache_prefix` - Tenant-specific cache prefix

**Laravel Config Modified**:
- `cache.default` - Cache store selection
- `cache.prefix` - Tenant-specific prefixing

**Multi-tenancy Strategy**: **Cache Isolation**
- Default prefix: `tenant_{public_id}_`
- Rebinds CacheManager for configuration changes
- Prevents cache key collisions between tenants
- Supports both shared stores with prefixes and dedicated stores

**Example Configuration**:
```php
[
    'cache_store' => 'redis',
    'cache_prefix' => 'tenant_enterprise_' // Private by default
]
```

### RedisConfigPipe (Priority: 84)

**Purpose**: Redis connection and key isolation management.

**Configuration Keys**:
- `redis_client`, `redis_host`, `redis_password`, `redis_port`
- `redis_prefix` - Key prefix for isolation

**Laravel Config Modified**:
- `database.redis.client` - Redis client type
- `database.redis.default.*` - Default connection settings
- `database.redis.*.prefix` - Tenant-specific key prefixes

**Multi-tenancy Strategy**: **Redis Key Isolation**
- Default prefix: `tenant_{public_id}:`
- Refreshes Redis connections when config changes
- Prevents Redis key conflicts between tenants
- Supports dedicated Redis instances for enterprise tenants

### MailConfigPipe (Priority: 70)

**Purpose**: Tenant-specific email configuration and branding.

**Configuration Keys**:
- Connection: `mail_mailer`, `mail_host`, `mail_port`, `mail_username`, `mail_password`
- Security: `mail_encryption`
- Branding: `mail_from_address`, `mail_from_name`

**Laravel Config Modified**:
- `mail.default` - Default mailer
- `mail.mailers.smtp.*` - SMTP configuration
- `mail.from.*` - From address and name

**Multi-tenancy Strategy**: **Email Per Tenant**
- Each tenant can have different SMTP settings
- Branded from addresses and names
- Isolated email delivery per tenant
- Support for different email providers per tenant

### QueueConfigPipe (Priority: 65)

**Purpose**: Queue system configuration and job isolation.

**Configuration Keys**:
- Basic: `queue_default`, `queue_connection`, `queue_name`
- Database: `queue_database_table`, `queue_failed_table`
- Redis: `redis_queue_database`
- Timing: `queue_retry_after`
- AWS SQS: `aws_sqs_queue`, `aws_sqs_region`, `aws_sqs_key`, `aws_sqs_secret`

**Laravel Config Modified**:
- `queue.default` - Default queue connection
- `queue.connections.*` - Queue connection settings
- `database.redis.queue.database` - Redis queue database

**Multi-tenancy Strategy**: **Queue Isolation**
- Tenant-specific queue names and tables
- Separate Redis databases for queue isolation
- Enterprise tenants can use dedicated SQS queues
- Prevents job mixing between tenants

### FilesystemConfigPipe (Priority: 55)

**Purpose**: File storage isolation and management across different storage drivers.

**Configuration Keys**:
- Basic: `filesystem_default`, `filesystem_cloud`
- Local: `filesystem_local_root`, `filesystem_public_root`
- AWS S3: `aws_s3_bucket`, `aws_s3_path_prefix`, `aws_s3_key`, `aws_s3_secret`, `aws_s3_region`, `aws_s3_url`
- Temp: `disable_temp_isolation`

**Laravel Config Modified**:
- `filesystems.default`, `filesystems.cloud`
- `filesystems.disks.*` for various storage drivers

**Multi-tenancy Strategy**: **File Storage Isolation**
- Local storage: `storage/app/tenants/{public_id}`
- Public storage: `storage/app/public/tenants/{public_id}`
- S3 prefix: `tenants/{public_id}`
- Temporary files: `storage/app/temp/tenants/{public_id}`
- Complete file separation between tenants

### BroadcastingConfigPipe (Priority: 45)

**Purpose**: WebSocket and real-time communication setup with channel isolation.

**Configuration Keys**:
- Pusher: `pusher_app_id`, `pusher_app_key`, `pusher_app_secret`, `pusher_app_cluster`
- Pusher Custom: `pusher_scheme`, `pusher_host`, `pusher_port`
- Reverb: `reverb_app_id`, `reverb_app_key`, `reverb_app_secret`, `reverb_host`, `reverb_port`
- Redis: `redis_broadcast_prefix`
- Ably: `ably_key`

**Laravel Config Modified**:
- `broadcasting.default` - Default broadcaster
- `broadcasting.connections.*` - Broadcasting connections

**Multi-tenancy Strategy**: **Broadcasting Isolation**
- Tenant-specific Pusher/Reverb credentials
- Redis broadcast prefixes for channel isolation
- Support for multiple broadcasting providers
- Isolated real-time communication per tenant

### LoggingConfigPipe (Priority: 40)

**Purpose**: Tenant-specific logging, monitoring, and error tracking.

**Configuration Keys**:
- Basic: `log_channel`, `log_level`
- File Logging: `log_single_path`, `log_daily_path`, `log_daily_days`
- Deprecations: `log_deprecations_channel`
- Slack: `log_slack_webhook_url`, `log_slack_channel`, `log_slack_username`
- Sentry: `sentry_dsn`, `sentry_level`, `sentry_environment`
- Custom: `log_custom_driver`, `log_custom_path`, `log_custom_level`, `log_custom_days`, `log_stack_channels`

**Laravel Config Modified**:
- `logging.default` - Default log channel
- `logging.channels.*` - Log channel configurations
- `services.sentry.*` - Sentry configuration

**Multi-tenancy Strategy**: **Log Isolation**
- Tenant-specific log paths: `storage/logs/tenants/{public_id}/`
- Individual Sentry/Slack configurations
- Custom log channels per tenant
- Isolated error tracking and monitoring

### ServicesConfigPipe (Priority: 35)

**Purpose**: Third-party service integration and API credential management per tenant.

**Configuration Keys**:
- **Payment**: `stripe_key`, `stripe_secret`, `stripe_webhook_secret`, `paypal_client_id`, `paypal_secret`, `paypal_mode`
- **Communication**: `twilio_sid`, `twilio_token`, `twilio_from`
- **Email Services**: `sendgrid_api_key`, `mailgun_domain`, `mailgun_secret`, `postmark_token`, `ses_key`, `ses_secret`
- **Analytics**: `google_analytics_id`, `google_maps_key`, `algolia_app_id`, `algolia_secret`
- **Monitoring**: `bugsnag_api_key`, `slack_webhook_url`
- **Custom**: `custom_api_endpoints`, `custom_api_keys`

**Laravel Config Modified**:
- `services.*` for all third-party integrations

**Multi-tenancy Strategy**: **Service Per Tenant**
- Individual API keys and credentials per tenant
- Tenant-specific payment gateway configurations
- Custom service endpoints for enterprise tenants
- Complete third-party service isolation

### SessionConfigPipe (Priority: 10)

**Purpose**: Session management and cookie isolation between tenants.

**Configuration Keys**:
- `session_driver`, `session_lifetime`, `session_encrypt`
- `session_path`, `session_domain`, `session_cookie`

**Laravel Config Modified**:
- `session.*` configuration
- Updates SessionManager with new cookie names

**Multi-tenancy Strategy**: **Session Isolation**
- Default cookie name: `tenant_{public_id}_session`
- Domain-based session sharing for subdomains
- Database sessions use tenant's database connection
- Prevents session mixing between tenants

## Multi-tenancy Strategies Enabled

The configuration pipes enable several multi-tenancy strategies that can be combined:

### 1. Database Per Tenant
- **Pipe**: DatabaseConfigPipe
- **Strategy**: Complete database isolation
- **Use Case**: Enterprise customers requiring strict data separation
- **Benefits**: Maximum security, independent backups, custom schemas

### 2. Shared Database with Isolation
- **Pipes**: CacheConfigPipe, RedisConfigPipe, SessionConfigPipe
- **Strategy**: Tenant prefixes and scoping
- **Use Case**: Standard SaaS tenants
- **Benefits**: Cost-effective, easy maintenance, good performance

### 3. Hybrid Isolation
- **Pipes**: Combination of above
- **Strategy**: Critical data separated, non-critical shared
- **Use Case**: Mixed customer base
- **Benefits**: Balanced security and cost

### 4. Service-Level Isolation
- **Pipes**: ServicesConfigPipe, MailConfigPipe, QueueConfigPipe
- **Strategy**: Tenant-specific third-party services
- **Use Case**: White-label solutions
- **Benefits**: Complete service branding and isolation

## Frontend Configuration Exposure

Several pipes expose safe configuration to the frontend via the `resolve()` method:

```php
// CoreConfigPipe exposes
$frontendConfig = [
    'apiUrl' => $config['app_url'],
    'appUrl' => $config['frontend_url'],
    'appName' => $config['app_name'],
    'pusherAppKey' => $config['pusher_app_key'],
    'pusherAppCluster' => $config['pusher_app_cluster']
];

// BroadcastingConfigPipe exposes
$frontendConfig = [
    'pusherAppKey' => $config['pusher_app_key'],
    'pusherAppCluster' => $config['pusher_app_cluster']
];

// SessionConfigPipe exposes (protected)
$frontendConfig = [
    'sessionCookie' => $config['session_cookie']
];

// ServicesConfigPipe exposes
$frontendConfig = [
    'recaptchaGoogleSiteKey' => $config['recaptcha_google_site_key']
];
```

## Creating Custom Configuration Pipes

### Basic Pipe Structure

```php
namespace Modules\YourModule\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

class YourModuleConfigPipe implements ConfigurationPipeInterface
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply your configuration logic
        if (isset($tenantConfig['your_api_key'])) {
            $config->set('services.your_service.key', $tenantConfig['your_api_key']);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return ['your_api_key', 'your_setting'];
    }

    public function priority(): int
    {
        return 50; // Higher numbers run first
    }
}
```

### Advanced Pipe with Frontend Configuration

```php
class AdvancedConfigPipe extends BaseConfigurationPipe
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Backend configuration
        if ($this->hasValue($tenantConfig, 'api_endpoint')) {
            $config->set('services.api.endpoint', $tenantConfig['api_endpoint']);
        }
        
        // Conditional configuration
        if ($tenant->isEnterprise()) {
            $config->set('services.api.timeout', 60);
        }
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function resolve(array $config): array
    {
        // Frontend-safe configuration
        $frontendConfig = [];
        
        if (isset($config['public_api_key'])) {
            $frontendConfig['apiKey'] = $config['public_api_key'];
        }
        
        return $frontendConfig;
    }
}
```

### Registering Your Pipe

Add to your module's `config/tenant.php`:

```php
return [
    'pipes' => [
        \Modules\YourModule\Pipes\YourModuleConfigPipe::class,
    ],
];
```

## Best Practices

### 1. Priority Management
- **100-90**: Core system configuration (app, database)
- **89-80**: Infrastructure (cache, redis, queues)
- **79-70**: Communication (mail, broadcasting)
- **69-50**: Services and features
- **49-1**: UI and frontend configuration

### 2. Configuration Validation
```php
public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
{
    // Validate configuration before applying
    if (isset($tenantConfig['api_key']) && !$this->isValidApiKey($tenantConfig['api_key'])) {
        throw new InvalidConfigurationException('Invalid API key format');
    }
    
    // Apply configuration...
}
```

### 3. Service Rebinding
```php
// Rebind services when configuration changes
if ($configChanged) {
    app()->forgetInstance('your.service');
    app()->singleton('your.service', function() use ($newConfig) {
        return new YourService($newConfig);
    });
}
```

### 4. Frontend Configuration Security
```php
public function resolve(array $config): array
{
    // Only expose safe, public configuration
    return [
        'publicKey' => $config['public_api_key'] ?? null,
        'timeout' => $config['timeout'] ?? 30,
        // NEVER expose secrets or private keys
    ];
}
```

## Debugging Configuration Pipes

### Check Applied Configuration
```php
// Get all tenant configuration
$config = getTenantConfig();
dd($config->all());

// Check effective configuration (with inheritance)
$effectiveConfig = getTenant()->getEffectiveConfig();
dd($effectiveConfig->all());
```

### Verify Pipe Registration
```php
$pipeline = app(\Modules\Tenant\Services\ConfigurationPipeline::class);
$pipes = $pipeline->getPipes();
dd($pipes); // See all registered pipes and their priorities
```

### Monitor Configuration Changes
```php
// In your pipe's handle method
\Log::info('Applying tenant configuration', [
    'tenant_id' => $tenant->id,
    'config_keys' => array_keys($tenantConfig),
    'pipe' => static::class,
]);
```

## Performance Considerations

### 1. Pipe Efficiency
- Keep pipe logic lightweight
- Avoid database queries in pipes
- Cache expensive computations

### 2. Configuration Caching
- The system caches tenant configurations in production
- Configuration is loaded once per request
- Pipes run in priority order for optimal performance

### 3. Service Rebinding
- Only rebind services when configuration actually changes
- Use conditional checks to avoid unnecessary rebinds
- Consider lazy loading for expensive services

---

[← Back to Tenant Module](./tenant-module.md) | [Next: Tenant Middleware →](./tenant-middleware.md)