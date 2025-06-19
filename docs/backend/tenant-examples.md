# Tenant Examples and Integration Patterns

## Overview

This document provides comprehensive examples of integrating modules with the QuVel Kit tenant system, including real-world module configurations, complete tenant setups, and advanced integration patterns.

## Module Integration Examples

### Example 1: Auth Module Integration

The Auth module demonstrates complete tenant integration with OAuth, session management, and dynamic configuration.

#### Module Configuration (`Modules/Auth/config/tenant.php`)

```php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => [
                'session_cookie' => 'quvel_session',
                'socialite_providers' => [],
                'oauth_credentials' => [],
                'session_lifetime' => 120, // 2 hours
            ],
            'visibility' => [
                'session_cookie' => 'protected',
                'socialite_providers' => 'public',
                'session_lifetime' => 'protected',
                // oauth_credentials private by default
            ],
            'priority' => 20,
        ],
        'isolated' => [
            'config' => function (string $template, array $config): array {
                // Generate unique session cookie for isolated tenants
                $sessionCookie = 'quvel_session';
                if (isset($config['cache_prefix'])) {
                    if (preg_match('/tenant_([a-z0-9]+)_?/i', $config['cache_prefix'], $matches)) {
                        $sessionCookie = "quvel_{$matches[1]}";
                    }
                }

                return [
                    'session_cookie' => $sessionCookie,
                    'socialite_providers' => ['google', 'microsoft'],
                    'oauth_credentials' => [
                        'google' => [
                            'client_id' => env('GOOGLE_CLIENT_ID'),
                            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                        ],
                        'microsoft' => [
                            'client_id' => env('MICROSOFT_CLIENT_ID'),
                            'client_secret' => env('MICROSOFT_CLIENT_SECRET'),
                        ],
                    ],
                    'session_lifetime' => 240, // 4 hours for isolated tenants
                    'oauth_redirect_url' => "https://{$config['domain']}/auth/callback",
                ];
            },
            'visibility' => [
                'session_cookie' => 'protected',
                'socialite_providers' => 'public',
                'session_lifetime' => 'protected',
                // oauth_credentials and oauth_redirect_url private by default
            ],
            'priority' => 20,
        ],
    ],

    'pipes' => [
        \Modules\Auth\Pipes\AuthConfigPipe::class,
    ],
    
    'tables' => [
        'users' => [
            'after' => 'id',
            'cascade_delete' => true,
            'drop_uniques' => [
                ['email'],
                ['provider_id'],
            ],
            'tenant_unique_constraints' => [
                ['email'],
                ['provider_id'],
                ['email', 'provider_id'],
            ],
        ],
    ],
];
```

#### Auth Configuration Pipe

```php
namespace Modules\Auth\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Models\Tenant;

class AuthConfigPipe extends BaseConfigurationPipe
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Configure session settings
        if ($this->hasValue($tenantConfig, 'session_cookie')) {
            $config->set('session.cookie', $tenantConfig['session_cookie']);
        }
        
        if ($this->hasValue($tenantConfig, 'session_lifetime')) {
            $config->set('session.lifetime', $tenantConfig['session_lifetime']);
        }

        // Configure OAuth providers
        if ($this->hasValue($tenantConfig, 'oauth_credentials')) {
            foreach ($tenantConfig['oauth_credentials'] as $provider => $credentials) {
                $config->set("services.{$provider}.client_id", $credentials['client_id']);
                $config->set("services.{$provider}.client_secret", $credentials['client_secret']);
                
                if (isset($tenantConfig['oauth_redirect_url'])) {
                    $config->set("services.{$provider}.redirect", $tenantConfig['oauth_redirect_url']);
                }
            }
        }

        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    public function handles(): array
    {
        return [
            'session_cookie',
            'session_lifetime', 
            'socialite_providers',
            'oauth_credentials',
            'oauth_redirect_url',
        ];
    }

    public function priority(): int
    {
        return 50;
    }

    public function resolve(array $config): array
    {
        return [
            'sessionCookie' => $config['session_cookie'] ?? null,
            'socialiteProviders' => $config['socialite_providers'] ?? [],
        ];
    }
}
```

### Example 2: Core Module Integration

The Core module handles fundamental application settings and frontend configuration.

#### Module Configuration (`Modules/Core/config/tenant.php`)

```php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => function (string $template, array $config): array {
                $domain = $config['domain'] ?? 'example.com';
                $apiUrl = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                return [
                    'app_name' => $config['_seed_app_name'] ?? 'QuVel',
                    'app_url' => $apiUrl,
                    'frontend_url' => $frontendUrl,
                    'app_timezone' => $config['_seed_timezone'] ?? 'UTC',
                    'app_locale' => $config['_seed_locale'] ?? 'en',
                    'mail_from_name' => $config['_seed_mail_from_name'] ?? 'QuVel Support',
                    'mail_from_address' => $config['_seed_mail_from_address'] ?? "support@{$domain}",
                ];
            },
            'visibility' => [
                'app_name' => 'public',
                'app_url' => 'public',
                'frontend_url' => 'protected',
                'app_timezone' => 'public',
                'app_locale' => 'public',
                // mail_from_name and mail_from_address private by default
            ],
            'priority' => 10, // Run very early
        ],
        'isolated' => [
            'config' => function (string $template, array $config): array {
                $domain = $config['domain'] ?? 'example.com';
                $apiUrl = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                $coreConfig = [
                    'app_name' => $config['_seed_app_name'] ?? 'QuVel',
                    'app_url' => $apiUrl,
                    'frontend_url' => $frontendUrl,
                    'app_timezone' => $config['_seed_timezone'] ?? 'UTC',
                    'app_locale' => $config['_seed_locale'] ?? 'en',
                    'mail_from_name' => $config['_seed_mail_from_name'] ?? 'QuVel Support',
                    'mail_from_address' => $config['_seed_mail_from_address'] ?? "support@{$domain}",
                ];

                // Add internal API URL for isolated template
                if (!isset($config['internal_api_url'])) {
                    $internalDomain = str_replace(['https://', 'http://'], '', $apiUrl);
                    $coreConfig['internal_api_url'] = "http://{$internalDomain}:8000";
                }

                return $coreConfig;
            },
            'visibility' => [
                'app_name' => 'public',
                'app_url' => 'public',
                'frontend_url' => 'protected',
                'app_timezone' => 'public',
                'app_locale' => 'public',
                'internal_api_url' => 'protected',
                // mail_from_name and mail_from_address private by default
            ],
            'priority' => 10,
        ],
    ],

    'shared_seeders' => [
        'recaptcha' => [
            'config' => function (string $template, array $config): array {
                $recaptchaConfig = [];

                if (isset($config['_seed_recaptcha_site_key'])) {
                    $recaptchaConfig['recaptcha_site_key'] = $config['_seed_recaptcha_site_key'];
                    $recaptchaConfig['recaptcha_secret_key'] = $config['_seed_recaptcha_secret_key'] ?? '';
                } elseif (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
                    $recaptchaConfig['recaptcha_site_key'] = env('RECAPTCHA_GOOGLE_SITE_KEY');
                    $recaptchaConfig['recaptcha_secret_key'] = env('RECAPTCHA_GOOGLE_SECRET', '');
                }

                return $recaptchaConfig;
            },
            'visibility' => [
                'recaptcha_site_key' => 'public',
                // recaptcha_secret_key private by default
            ],
            'priority' => 15,
        ],
    ],
];
```

### Example 3: E-commerce Module Integration

A more complex example showing an e-commerce module with payment integration.

#### Module Configuration (`Modules/Ecommerce/config/tenant.php`)

```php
<?php

return [
    'seeders' => [
        'basic' => [
            'config' => [
                'payment_gateway' => 'stripe',
                'currency' => 'USD',
                'tax_rate' => 0.08,
                'shipping_enabled' => true,
                'inventory_tracking' => false,
                'max_products' => 100,
            ],
            'visibility' => [
                'payment_gateway' => 'protected',
                'currency' => 'public',
                'tax_rate' => 'public',
                'shipping_enabled' => 'public',
                'inventory_tracking' => 'public',
                'max_products' => 'protected',
            ],
            'priority' => 30,
        ],
        'isolated' => [
            'config' => function (string $template, array $config): array {
                return [
                    'payment_gateway' => 'stripe',
                    'currency' => $config['_seed_currency'] ?? 'USD',
                    'tax_rate' => $config['_seed_tax_rate'] ?? 0.08,
                    'shipping_enabled' => true,
                    'inventory_tracking' => true,
                    'max_products' => -1, // Unlimited for isolated
                    'webhook_endpoint' => "https://{$config['domain']}/webhooks/ecommerce",
                    'stripe_webhook_secret' => $config['_seed_stripe_webhook_secret'] ?? '',
                ];
            },
            'visibility' => [
                'payment_gateway' => 'protected',
                'currency' => 'public',
                'tax_rate' => 'public',
                'shipping_enabled' => 'public',
                'inventory_tracking' => 'public',
                'max_products' => 'protected',
                // webhook_endpoint and stripe_webhook_secret private by default
            ],
            'priority' => 30,
        ],
    ],

    'pipes' => [
        \Modules\Ecommerce\Pipes\EcommerceConfigPipe::class,
    ],

    'tables' => [
        'products' => [
            'after' => 'id',
            'cascade_delete' => true,
            'drop_uniques' => [['sku']],
            'tenant_unique_constraints' => [
                ['sku'],
                ['name', 'category_id'],
            ],
        ],
        'orders' => [
            'after' => 'id',
            'cascade_delete' => true,
        ],
        'customers' => [
            'after' => 'id',
            'cascade_delete' => true,
            'drop_uniques' => [['email']],
            'tenant_unique_constraints' => [['email']],
        ],
    ],

    'exclusions' => [
        'paths' => [
            '/webhooks/ecommerce',
            '/api/ecommerce/webhook',
        ],
    ],
];
```

## Complete Tenant Setup Examples

### Example 1: Basic SaaS Tenant

A standard SaaS tenant using shared resources with basic isolation.

```php
// Create basic tenant
$tenant = Tenant::create([
    'name' => 'Small Business Inc',
    'domain' => 'smallbiz.example.com',
    'template' => 'basic',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Small Business App',
    'app_timezone' => 'America/New_York',
    'app_locale' => 'en',
    'mail_from_name' => 'Small Business Support',
    'mail_from_address' => 'support@smallbiz.example.com',
    
    // Database sharing with prefixes
    'cache_prefix' => 'smallbiz_',
    'session_cookie' => 'smallbiz_session',
    
    // Basic features
    'max_users' => 25,
    'storage_limit' => '5GB',
    'features' => [
        'basic_reporting' => true,
        'api_access' => false,
        'custom_branding' => false,
    ],
    
    // Payment integration
    'stripe_publishable_key' => 'pk_test_...',
    'billing_plan' => 'basic',
], [
    'app_name' => 'public',
    'app_timezone' => 'public',
    'app_locale' => 'public',
    'mail_from_name' => 'protected',
    'mail_from_address' => 'private',
    'cache_prefix' => 'private',
    'session_cookie' => 'protected',
    'max_users' => 'protected',
    'storage_limit' => 'protected',
    'features' => 'public',
    'stripe_publishable_key' => 'public',
    'billing_plan' => 'protected',
]);

$tenant->config = $config;
$tenant->save();
```

### Example 2: Enterprise Isolated Tenant

An enterprise tenant with complete resource isolation.

```php
// Create isolated enterprise tenant
$tenant = Tenant::create([
    'name' => 'Enterprise Solutions Corp',
    'domain' => 'enterprise.example.com',
    'template' => 'isolated',
]);

$config = new DynamicTenantConfig([
    'app_name' => 'Enterprise Portal',
    'app_timezone' => 'America/Los_Angeles',
    'app_locale' => 'en',
    
    // Dedicated database
    'db_connection' => 'mysql',
    'db_host' => 'enterprise-db.example.com',
    'db_port' => 3306,
    'db_database' => 'enterprise_tenant_db',
    'db_username' => 'enterprise_user',
    'db_password' => 'secure_enterprise_password',
    
    // Dedicated cache and Redis
    'cache_store' => 'redis',
    'cache_prefix' => 'enterprise_',
    'redis_host' => 'enterprise-redis.example.com',
    'redis_password' => 'redis_secure_password',
    'redis_prefix' => 'enterprise:',
    
    // Dedicated file storage
    'filesystem_default' => 's3',
    'aws_s3_bucket' => 'enterprise-files-bucket',
    'aws_s3_path_prefix' => 'enterprise/',
    'aws_s3_key' => 'AKIAIOSFODNN7EXAMPLE',
    'aws_s3_secret' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'aws_s3_region' => 'us-west-2',
    
    // Premium mail service
    'mail_mailer' => 'smtp',
    'mail_host' => 'smtp.enterprise.example.com',
    'mail_port' => 587,
    'mail_username' => 'enterprise@example.com',
    'mail_password' => 'mail_secure_password',
    'mail_from_name' => 'Enterprise Support',
    'mail_from_address' => 'support@enterprise.example.com',
    
    // Dedicated queue system
    'queue_connection' => 'sqs',
    'aws_sqs_queue' => 'enterprise-queue',
    'aws_sqs_region' => 'us-west-2',
    
    // Enterprise features
    'max_users' => -1, // Unlimited
    'storage_limit' => -1, // Unlimited
    'features' => [
        'advanced_reporting' => true,
        'api_access' => true,
        'custom_branding' => true,
        'sso_integration' => true,
        'dedicated_support' => true,
        'audit_logs' => true,
    ],
    
    // Payment integration
    'stripe_publishable_key' => 'pk_live_...',
    'stripe_secret_key' => 'sk_live_...',
    'stripe_webhook_secret' => 'whsec_...',
    'billing_plan' => 'enterprise',
    
    // Monitoring and logging
    'sentry_dsn' => 'https://enterprise@sentry.io/project',
    'log_level' => 'info',
    'log_daily_path' => 'logs/enterprise/laravel.log',
], [
    'app_name' => 'public',
    'app_timezone' => 'public',
    'app_locale' => 'public',
    'mail_from_name' => 'protected',
    'features' => 'public',
    'stripe_publishable_key' => 'public',
    'billing_plan' => 'protected',
    // All passwords, secrets, and sensitive data private by default
]);

$tenant->config = $config;
$tenant->save();
```

### Example 3: Multi-Tenant Hierarchy

Parent-child tenant relationship for organizations with subsidiaries.

```php
// Create parent organization
$parentTenant = Tenant::create([
    'name' => 'Global Corp',
    'domain' => 'global.example.com',
    'template' => 'isolated',
]);

$parentConfig = new DynamicTenantConfig([
    // Shared organizational settings
    'organization_name' => 'Global Corp',
    'organization_timezone' => 'UTC',
    'organization_locale' => 'en',
    
    // Shared branding
    'brand_primary_color' => '#1a365d',
    'brand_secondary_color' => '#2d3748',
    'brand_logo_url' => 'https://global.example.com/logo.png',
    
    // Shared email infrastructure
    'mail_host' => 'smtp.global.example.com',
    'mail_username' => 'global@example.com',
    'mail_password' => 'global_mail_password',
    'mail_from_name' => 'Global Corp',
    
    // Shared services
    'stripe_publishable_key' => 'pk_live_global...',
    'stripe_secret_key' => 'sk_live_global...',
    'sentry_dsn' => 'https://global@sentry.io/project',
    
    // Global policies
    'security_policy' => 'strict',
    'data_retention_days' => 2555, // 7 years
    'audit_required' => true,
], [
    'organization_name' => 'public',
    'brand_primary_color' => 'public',
    'brand_secondary_color' => 'public',
    'brand_logo_url' => 'public',
    'security_policy' => 'protected',
    'data_retention_days' => 'protected',
    'audit_required' => 'protected',
    // Passwords, secrets, and sensitive data private by default
]);

$parentTenant->config = $parentConfig;
$parentTenant->save();

// Create subsidiary tenant
$childTenant = Tenant::create([
    'name' => 'Regional Office East',
    'domain' => 'east.global.example.com',
    'parent_id' => $parentTenant->id, // Inheritance
    'template' => 'isolated',
]);

$childConfig = new DynamicTenantConfig([
    // Child-specific settings (override parent)
    'app_name' => 'Regional East Portal',
    'app_timezone' => 'America/New_York',
    'app_locale' => 'en',
    
    // Regional database
    'db_host' => 'east-db.global.example.com',
    'db_database' => 'regional_east_db',
    'db_username' => 'east_user',
    'db_password' => 'east_db_password',
    
    // Regional mail settings
    'mail_from_address' => 'support@east.global.example.com',
    
    // Regional-specific features
    'regional_manager' => 'john.doe@global.example.com',
    'local_currency' => 'USD',
    'local_tax_rate' => 0.08,
    
    // Inherit: organization_name, brand colors, stripe keys, etc.
], [
    'app_name' => 'public',
    'app_timezone' => 'public',
    'app_locale' => 'public',
    'regional_manager' => 'protected',
    'local_currency' => 'public',
    'local_tax_rate' => 'public',
    // Database passwords and mail addresses private by default
]);

$childTenant->config = $childConfig;
$childTenant->save();

// The child tenant effective config will include:
// - Child's own settings (app_name, timezone, etc.)
// - Parent's inherited settings (organization_name, brand colors, etc.)
$effectiveConfig = $childTenant->getEffectiveConfig();
```

## Advanced Integration Patterns

### Pattern 1: Conditional Feature Rollout

Gradually rolling out features based on tenant properties.

```php
// Feature rollout configuration pipe
class FeatureRolloutPipe extends BaseConfigurationPipe
{
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Feature flags based on tenant properties
        $features = [];
        
        // Beta features for specific tenants
        if (in_array($tenant->id, [1, 5, 12, 25])) {
            $features['beta_dashboard'] = true;
            $features['new_api_endpoints'] = true;
        }
        
        // Percentage rollout based on tenant ID
        if ($tenant->id % 10 === 0) { // 10% of tenants
            $features['experimental_ui'] = true;
        }
        
        // Enterprise-only features
        if ($tenant->template === 'isolated') {
            $features['advanced_analytics'] = true;
            $features['custom_integrations'] = true;
        }
        
        // Date-based rollout
        if ($tenant->created_at->gt(now()->subDays(30))) {
            $features['onboarding_v2'] = true;
        }
        
        $config->set('features', array_merge(
            $config->get('features', []),
            $features
        ));
        
        return $next([
            'tenant' => $tenant,
            'config' => $config,
            'tenantConfig' => array_merge($tenantConfig, ['features' => $features]),
        ]);
    }

    public function handles(): array
    {
        return ['features'];
    }

    public function priority(): int
    {
        return 5; // Run late to see other config
    }

    public function resolve(array $config): array
    {
        return [
            'features' => $config['features'] ?? [],
        ];
    }
}
```

### Pattern 2: Environment-Aware Configuration

Configuration that adapts based on the environment.

```php
// Environment-aware seeder
'config' => function(string $template, array $config): array {
    $environment = app()->environment();
    $tenantConfig = [];
    
    // Development settings
    if ($environment === 'local') {
        $tenantConfig['debug_mode'] = true;
        $tenantConfig['log_level'] = 'debug';
        $tenantConfig['cache_ttl'] = 60; // 1 minute
        $tenantConfig['mail_log_emails'] = true;
    }
    
    // Staging settings
    elseif ($environment === 'staging') {
        $tenantConfig['debug_mode'] = false;
        $tenantConfig['log_level'] = 'info';
        $tenantConfig['cache_ttl'] = 300; // 5 minutes
        $tenantConfig['mail_log_emails'] = true;
    }
    
    // Production settings
    elseif ($environment === 'production') {
        $tenantConfig['debug_mode'] = false;
        $tenantConfig['log_level'] = 'error';
        $tenantConfig['cache_ttl'] = 3600; // 1 hour
        $tenantConfig['mail_log_emails'] = false;
    }
    
    return $tenantConfig;
}
```

### Pattern 3: White-Label Configuration

Complete white-labeling with custom domains and branding.

```php
// White-label module configuration
return [
    'seeders' => [
        'whitelabel' => [
            'config' => function(string $template, array $config): array {
                $domain = $config['domain'];
                $brandName = $config['_seed_brand_name'] ?? 'Custom Brand';
                
                return [
                    // Complete rebranding
                    'app_name' => $brandName,
                    'brand_name' => $brandName,
                    'brand_domain' => $domain,
                    'brand_logo_url' => "https://{$domain}/assets/logo.png",
                    'brand_favicon_url' => "https://{$domain}/assets/favicon.ico",
                    'brand_primary_color' => $config['_seed_brand_color'] ?? '#007bff',
                    
                    // Custom mail branding
                    'mail_from_name' => "{$brandName} Support",
                    'mail_from_address' => "support@{$domain}",
                    'mail_footer_text' => "© 2024 {$brandName}. All rights reserved.",
                    
                    // Custom URLs
                    'terms_url' => "https://{$domain}/terms",
                    'privacy_url' => "https://{$domain}/privacy",
                    'support_url' => "https://{$domain}/support",
                    
                    // Separate analytics
                    'google_analytics_id' => $config['_seed_ga_id'] ?? null,
                    'facebook_pixel_id' => $config['_seed_fb_pixel'] ?? null,
                    
                    // Custom integrations
                    'custom_api_endpoints' => $config['_seed_api_endpoints'] ?? [],
                    'webhook_urls' => $config['_seed_webhooks'] ?? [],
                ];
            },
            'visibility' => [
                'app_name' => 'public',
                'brand_name' => 'public',
                'brand_logo_url' => 'public',
                'brand_primary_color' => 'public',
                // mail_from_address, custom_api_endpoints, webhook_urls private by default
            ],
            'priority' => 5, // Run late
        ],
    ],
];
```

## Testing Tenant Integration

### Basic Tenant Test Case

```php
namespace Modules\YourModule\Tests;

use Modules\Tenant\Tests\TestCase;
use Modules\Tenant\Models\Tenant;

class TenantIntegrationTest extends TestCase
{
    public function test_module_configuration_applied()
    {
        // Tenant is automatically created and set in TestCase
        
        // Test that module configuration is applied
        $this->assertEquals('test-value', getTenantConfig('your_module_setting'));
        $this->assertTrue(getTenantConfig('your_feature_enabled'));
        
        // Test Laravel config is updated
        $this->assertEquals('expected-value', config('your_module.api_key'));
    }
    
    public function test_tenant_isolation()
    {
        // Create a product for current tenant
        $product = Product::factory()->create(['name' => 'Tenant 1 Product']);
        $this->assertEquals($this->tenant->id, $product->tenant_id);
        
        // Switch to different tenant
        $tenant2 = Tenant::factory()->create();
        setTenant($tenant2->id);
        
        // Create product for second tenant
        Product::factory()->create(['name' => 'Tenant 2 Product']);
        
        // Verify isolation
        $this->assertCount(1, Product::all());
        $this->assertEquals('Tenant 2 Product', Product::first()->name);
    }
    
    public function test_configuration_inheritance()
    {
        // Create parent tenant
        $parent = Tenant::factory()->create();
        $parent->config = new DynamicTenantConfig([
            'parent_setting' => 'parent_value',
            'shared_setting' => 'parent_shared',
        ]);
        $parent->save();
        
        // Create child tenant
        $child = Tenant::factory()->create(['parent_id' => $parent->id]);
        $child->config = new DynamicTenantConfig([
            'child_setting' => 'child_value',
            'shared_setting' => 'child_override',
        ]);
        $child->save();
        
        // Test effective configuration
        setTenant($child->id);
        $effectiveConfig = $child->getEffectiveConfig();
        
        $this->assertEquals('parent_value', $effectiveConfig->get('parent_setting'));
        $this->assertEquals('child_value', $effectiveConfig->get('child_setting'));
        $this->assertEquals('child_override', $effectiveConfig->get('shared_setting'));
    }
}
```

### Advanced Testing Patterns

```php
class AdvancedTenantTest extends TestCase
{
    public function test_configuration_pipe_priority()
    {
        // Test that pipes run in correct order
        $pipeline = app(\Modules\Tenant\Services\ConfigurationPipeline::class);
        $pipes = $pipeline->getPipes();
        
        // Verify pipe order
        $priorities = array_map(fn($pipe) => $pipe->priority(), $pipes);
        $this->assertEquals($priorities, array_reverse(sort($priorities)));
    }
    
    public function test_frontend_configuration_exposure()
    {
        // Set configuration with different visibility levels
        $config = new DynamicTenantConfig([
            'public_setting' => 'public_value',
            'protected_setting' => 'protected_value',
            'private_setting' => 'private_value',
        ], [
            'public_setting' => 'public',
            'protected_setting' => 'protected',
            'private_setting' => 'private',
        ]);
        
        $this->tenant->config = $config;
        $this->tenant->save();
        
        // Test frontend exposure
        $frontendConfig = getFrontendTenantConfig();
        $this->assertArrayHasKey('public_setting', $frontendConfig);
        $this->assertArrayHasKey('protected_setting', $frontendConfig);
        $this->assertArrayNotHasKey('private_setting', $frontendConfig);
    }
    
    public function test_path_exclusions()
    {
        // Test that certain paths bypass tenant resolution
        $response = $this->get('/webhooks/stripe');
        
        // Should not have tenant context
        $this->assertNull(getTenant());
    }
}
```

## Common Integration Patterns

### 1. Service Provider Integration

```php
class YourModuleServiceProvider extends TenantServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        
        // Register tenant-aware services
        $this->app->singleton(YourService::class, function ($app) {
            $config = getTenantConfig();
            return new YourService($config->get('your_api_key'));
        });
        
        // Exclude paths from tenant resolution
        $this->excludePaths([
            '/your-module/webhook',
            '/your-module/callback',
        ]);
        
        // Register event listeners
        Event::listen(TenantResolved::class, function ($event) {
            // React to tenant resolution
            $this->configureTenantSpecificServices($event->tenant);
        });
    }
}
```

### 2. Model Integration

```php
use Modules\Tenant\Traits\TenantScopedModel;

class Product extends Model
{
    use TenantScopedModel;
    
    protected $fillable = ['name', 'price', 'description'];
    
    // Automatically scoped to current tenant
    // tenant_id automatically set on creation
    // Cross-tenant queries prevented
    
    public function getMaxAllowedAttribute(): int
    {
        return getTenantConfig('max_products', 100);
    }
    
    public function getPriceFormattedAttribute(): string
    {
        $currency = getTenantConfig('currency', 'USD');
        return number_format($this->price, 2) . ' ' . $currency;
    }
}
```

### 3. API Resource Integration

```php
class ProductResource extends JsonResource
{
    public function toArray($request): array
    {
        $config = getTenantConfig();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'currency' => $config->get('currency', 'USD'),
            'formatted_price' => $this->price_formatted,
            'features' => [
                'inventory_tracking' => $config->get('inventory_tracking', false),
                'reviews_enabled' => $config->get('reviews_enabled', true),
            ],
        ];
    }
}
```

---

[← Back to Tenant Configuration](./tenant-configuration.md) | [Back to Tenant Module →](./tenant-module.md)