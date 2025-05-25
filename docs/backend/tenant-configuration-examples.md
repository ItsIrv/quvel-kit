# Tenant Configuration Examples

This document provides comprehensive examples of configuring tenants with the dynamic configuration system.

## Basic Tier Tenant Configuration

```php
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

// Create a basic tier tenant with minimal configuration
$tenant = Tenant::create([
    'name' => 'Small Business Inc',
    'domain' => 'smallbiz.example.com',
]);

$config = new DynamicTenantConfig([
    // Core settings
    'app_name' => 'Small Business App',
    'app_timezone' => 'America/New_York',
    
    // Mail settings (using shared mail server)
    'mail_from_name' => 'Small Business Support',
    'mail_from_address' => 'support@smallbiz.example.com',
    
    // Basic queue configuration
    'queue_connection' => 'database',
    'queue_name' => 'tenant_' . $tenant->id,
    
    // Basic logging
    'log_level' => 'info',
], [
    'app_name' => TenantConfigVisibility::PUBLIC,
    'app_timezone' => TenantConfigVisibility::PUBLIC,
], 'basic');

$tenant->config = $config;
$tenant->save();
```

## Standard Tier Tenant Configuration

```php
// Create a standard tier tenant with dedicated cache and Redis
$tenant = Tenant::create([
    'name' => 'Medium Corp',
    'domain' => 'mediumcorp.example.com',
]);

$config = new DynamicTenantConfig([
    // Core settings
    'app_name' => 'Medium Corp Portal',
    'app_timezone' => 'Europe/London',
    'app_locale' => 'en_GB',
    
    // Dedicated cache with prefix
    'cache_prefix' => 'medium_corp',
    
    // Dedicated Redis database
    'redis_default_database' => 1,
    'redis_cache_database' => 2,
    'redis_queue_database' => 3,
    
    // Queue with Redis
    'queue_connection' => 'redis',
    'queue_name' => 'medium_corp_queue',
    'queue_retry_after' => 120,
    
    // Enhanced logging
    'log_channel' => 'daily',
    'log_level' => 'debug',
    'log_daily_days' => 30,
    
    // Broadcasting with Pusher
    'broadcast_driver' => 'pusher',
    'pusher_app_id' => 'medium_corp_app',
    'pusher_app_key' => 'mc_key_123',
    'pusher_app_secret' => 'mc_secret_456',
    'pusher_app_cluster' => 'eu',
    
    // Filesystem with S3
    'filesystem_cloud' => 's3',
    'aws_s3_bucket' => 'medium-corp-files',
    'aws_s3_path_prefix' => 'tenants/medium_corp',
], [
    'app_name' => TenantConfigVisibility::PUBLIC,
    'app_timezone' => TenantConfigVisibility::PUBLIC,
    'app_locale' => TenantConfigVisibility::PUBLIC,
    'pusher_app_key' => TenantConfigVisibility::PUBLIC,
    'pusher_app_cluster' => TenantConfigVisibility::PUBLIC,
], 'standard');

$tenant->config = $config;
$tenant->save();
```

## Premium Tier Tenant Configuration

```php
// Create a premium tier tenant with dedicated database and resources
$tenant = Tenant::create([
    'name' => 'Enterprise Solutions Ltd',
    'domain' => 'enterprise.example.com',
]);

$config = new DynamicTenantConfig([
    // Core settings
    'app_name' => 'Enterprise Portal',
    'app_url' => 'https://enterprise.example.com',
    'app_timezone' => 'America/Los_Angeles',
    'app_debug' => false,
    
    // Dedicated database
    'db_connection' => 'tenant_enterprise',
    'db_host' => 'enterprise-db.example.com',
    'db_port' => 3306,
    'db_database' => 'enterprise_tenant',
    'db_username' => 'enterprise_user',
    'db_password' => 'secure_password_here',
    
    // Dedicated cache and Redis
    'cache_store' => 'redis',
    'cache_prefix' => 'enterprise',
    'redis_host' => 'enterprise-redis.example.com',
    'redis_password' => 'redis_password',
    'redis_default_database' => 0,
    
    // Advanced queue configuration
    'queue_connection' => 'redis',
    'queue_name' => 'enterprise_high_priority',
    'queue_retry_after' => 180,
    
    // Mail with dedicated SMTP
    'mail_mailer' => 'smtp',
    'mail_host' => 'smtp.enterprise.example.com',
    'mail_port' => 587,
    'mail_username' => 'enterprise@example.com',
    'mail_password' => 'smtp_password',
    'mail_encryption' => 'tls',
    'mail_from_name' => 'Enterprise Support',
    'mail_from_address' => 'support@enterprise.example.com',
    
    // Advanced logging with Sentry
    'log_channel' => 'stack',
    'log_stack_channels' => ['daily', 'slack', 'sentry'],
    'log_level' => 'warning',
    'log_slack_webhook_url' => 'https://hooks.slack.com/services/xxx',
    'sentry_dsn' => 'https://xxx@sentry.io/xxx',
    'sentry_environment' => 'production',
    
    // Premium filesystem features
    'filesystem_default' => 's3',
    'aws_s3_bucket' => 'enterprise-premium-storage',
    'aws_s3_key' => 'AKIAIOSFODNN7EXAMPLE',
    'aws_s3_secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'aws_s3_region' => 'us-west-2',
    
    // Enterprise broadcasting with Reverb
    'broadcast_driver' => 'reverb',
    'reverb_app_id' => 'enterprise_app',
    'reverb_app_key' => 'ent_key_789',
    'reverb_app_secret' => 'ent_secret_012',
    'reverb_host' => 'ws.enterprise.example.com',
    'reverb_port' => 6001,
    
    // Third-party services
    'stripe_key' => 'pk_live_enterprise',
    'stripe_secret' => 'sk_live_enterprise',
    'stripe_webhook_secret' => 'whsec_enterprise',
    'twilio_sid' => 'AC_enterprise',
    'twilio_token' => 'auth_token_enterprise',
    'twilio_from' => '+1234567890',
    'google_analytics_id' => 'UA-ENTERPRISE-1',
], [
    // Public configuration
    'app_name' => TenantConfigVisibility::PUBLIC,
    'app_url' => TenantConfigVisibility::PUBLIC,
    'app_timezone' => TenantConfigVisibility::PUBLIC,
    'reverb_app_key' => TenantConfigVisibility::PUBLIC,
    'reverb_host' => TenantConfigVisibility::PUBLIC,
    'reverb_port' => TenantConfigVisibility::PUBLIC,
    'stripe_key' => TenantConfigVisibility::PUBLIC,
    'google_analytics_id' => TenantConfigVisibility::PUBLIC,
    
    // Protected configuration (SSR only)
    'app_debug' => TenantConfigVisibility::PROTECTED,
    'mail_from_name' => TenantConfigVisibility::PROTECTED,
    'mail_from_address' => TenantConfigVisibility::PROTECTED,
], 'premium');

$tenant->config = $config;
$tenant->save();
```

## Enterprise Tier with SQS and Custom Services

```php
// Create an enterprise tenant with AWS SQS and custom integrations
$tenant = Tenant::create([
    'name' => 'Global Enterprises',
    'domain' => 'global.example.com',
]);

$config = new DynamicTenantConfig([
    // Previous premium configurations...
    
    // AWS SQS for queue processing
    'queue_connection' => 'sqs',
    'aws_sqs_queue' => 'https://sqs.us-east-1.amazonaws.com/123456789/global-queue',
    'aws_sqs_region' => 'us-east-1',
    'aws_sqs_key' => 'AKIAIOSFODNN7EXAMPLE',
    'aws_sqs_secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    
    // Multiple third-party services
    'paypal_client_id' => 'AZDxjNScFpBgQ7Dq...',
    'paypal_secret' => 'EGnHDxD_qRPdaLdZz8...',
    'paypal_mode' => 'live',
    
    'sendgrid_api_key' => 'SG.xxxxxxxxxxxxxxxxxxxx',
    
    'algolia_app_id' => 'GLOBAL123',
    'algolia_secret' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    
    'bugsnag_api_key' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
    
    // Custom API endpoints
    'custom_api_endpoints' => [
        'inventory' => 'https://api.global-inventory.com/v2',
        'shipping' => 'https://api.global-shipping.com/v1',
        'analytics' => 'https://analytics.global.com/api',
    ],
    'custom_api_keys' => [
        'inventory' => 'inv_key_xxx',
        'shipping' => 'ship_key_yyy',
        'analytics' => 'analytics_key_zzz',
    ],
    
    // Enterprise logging with multiple channels
    'log_custom_driver' => 'daily',
    'log_custom_path' => '/var/log/global-enterprise/app.log',
    'log_custom_level' => 'info',
    'log_custom_days' => 90,
    
    // Dedicated Ably for real-time features
    'broadcast_driver' => 'ably',
    'ably_key' => 'xVLyHw.DQvL7w:xxxxxxxxxxxxxxxxxx',
], [
    // Additional public settings
    'algolia_app_id' => TenantConfigVisibility::PUBLIC,
    'custom_api_endpoints' => TenantConfigVisibility::PROTECTED,
], 'enterprise');

$tenant->config = $config;
$tenant->save();
```

## Using Helper Functions (CLI/Tinker)

```php
// Set current tenant
setTenant('example.com'); // by domain
setTenant(1); // by ID
setTenant($tenant); // by instance

// Get current tenant
$tenant = getTenant();

// Get tenant configuration
$config = getTenantConfig(); // Full config object
$appName = getTenantConfig('app_name'); // Specific value
$queueName = getTenantConfig('queue_name', 'default'); // With default

// Get tenant tier
$tier = getTenantTier(); // 'basic', 'standard', 'premium', 'enterprise'

// Set configuration (in memory only, not persisted)
setTenantConfig('new_feature_enabled', true, 'public');
setTenantConfig('api_limit', 1000, TenantConfigVisibility::PROTECTED);

// Create configuration for new tenant
$config = createTenantConfig([
    'app_name' => 'New App',
    'mail_from_address' => 'support@newapp.com',
], [
    'app_name' => 'public',
], 'standard');

// Persist changes
$tenant = getTenant();
$tenant->save();
```

## Configuration Inheritance Example

```php
// Parent company configuration
$parentCompany = Tenant::create([
    'name' => 'Parent Corporation',
    'domain' => 'parent.example.com',
]);

$parentConfig = new DynamicTenantConfig([
    // Shared company-wide settings
    'mail_host' => 'smtp.parent-corp.com',
    'mail_port' => 587,
    'mail_encryption' => 'tls',
    'mail_from_name' => 'Parent Corp',
    
    // Shared services
    'stripe_key' => 'pk_live_parent',
    'stripe_secret' => 'sk_live_parent',
    
    // Company-wide logging
    'sentry_dsn' => 'https://xxx@sentry.io/parent',
    'log_level' => 'warning',
], [], 'premium');

$parentCompany->config = $parentConfig;
$parentCompany->save();

// Child division configuration
$childDivision = Tenant::create([
    'name' => 'Child Division A',
    'domain' => 'division-a.parent.example.com',
    'parent_id' => $parentCompany->id,
]);

$childConfig = new DynamicTenantConfig([
    // Override specific settings
    'app_name' => 'Division A Portal',
    'mail_from_name' => 'Division A Support',
    'mail_from_address' => 'support@division-a.parent.example.com',
    
    // Division-specific settings
    'queue_name' => 'division_a_queue',
    'log_channel' => 'daily',
    
    // Inherits all other settings from parent
], [
    'app_name' => TenantConfigVisibility::PUBLIC,
], 'standard');

$childDivision->config = $childConfig;
$childDivision->save();

// Get effective configuration (includes inherited values)
$effectiveConfig = $childDivision->getEffectiveConfig();
// Will have both parent and child configurations merged
```

## Testing Configuration in Development

```php
// In your tests or seeders
use Modules\Tenant\Database\Factories\DynamicTenantConfigFactory;

// Quick setup for development
$config = DynamicTenantConfigFactory::createFromEnv('premium', [
    'custom_feature' => true,
    'api_rate_limit' => 10000,
]);

$tenant = Tenant::factory()->create([
    'name' => 'Test Premium Tenant',
    'domain' => 'test.local',
    'config' => $config,
]);

// Apply configuration for testing
setTenant($tenant);

// Your tenant-specific code runs here with the configuration applied
```

## Best Practices

1. **Use appropriate tiers** - Don't over-provision resources for small tenants
2. **Secure sensitive data** - Always mark credentials as PRIVATE visibility
3. **Test inheritance** - Verify child tenants properly inherit parent configs
4. **Monitor resource usage** - Track usage to optimize tier assignments
5. **Document custom configs** - Keep track of custom configuration keys used
6. **Use environment variables** - For defaults and development values
7. **Validate before saving** - Ensure required configurations are present

## Troubleshooting

### Configuration Not Applied

```php
// Check if configuration exists
$config = getTenantConfig();
dd($config->all()); // Show all configuration

// Check effective configuration (with inheritance)
$effectiveConfig = getTenant()->getEffectiveConfig();
dd($effectiveConfig->all());

// Verify pipe is handling the key
$pipeline = app(\Modules\Tenant\Services\ConfigurationPipeline::class);
$pipes = $pipeline->getPipes();
// Check if your configuration key is in the handles() array
```

### Debugging Visibility

```php
// Check visibility settings
$config = getTenantConfig();
$visibility = $config->getVisibility();
dd($visibility); // Shows all visibility settings

// Check what's exposed to frontend
$publicConfig = $config->getPublicConfig();
dd($publicConfig); // Only PUBLIC visibility items
```