# Tenant Configuration System

## Overview

QuVel Kit provides a powerful and flexible tenant configuration system built around the `DynamicTenantConfig` value object. This system supports dynamic key-value configuration storage, inheritance between parent and child tenants, visibility controls for frontend exposure, and a comprehensive set of helper functions for configuration management.

The configuration system is designed to be completely flexible - there are no hardcoded configuration properties, allowing modules to define their own configuration schemas through seeders and pipes.

## DynamicTenantConfig Value Object

The `DynamicTenantConfig` class is the core of the tenant configuration system, providing a flexible container for tenant-specific settings with built-in visibility control.

### Basic Structure

```php
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

$config = new DynamicTenantConfig(
    $configuration = [
        'app_name' => 'My Tenant App',
        'mail_from_address' => 'support@tenant.com',
        'api_key' => 'secret-key-123',
        'feature_enabled' => true,
    ],
    $visibility = [
        'app_name' => 'public',
        'mail_from_address' => 'protected',
        'feature_enabled' => 'public',
        // api_key private by default (omitted)
    ]
);
```

### Visibility Levels

The configuration system implements a three-tier visibility model:

| Level | Description | Access | Use Case |
|-------|-------------|--------|----------|
| `public` | Exposed to frontend via `window.__TENANT_CONFIG__` | Backend + SSR + Browser | App names, public API keys, feature flags |
| `protected` | Available to SSR but not browser | Backend + SSR | Internal URLs, session settings |
| `private` | Backend only, never exposed | Backend only | Database credentials, API secrets |

### Configuration Methods

#### Getting Values
```php
// Get a single value
$appName = $config->get('app_name');
$apiKey = $config->get('api_key', 'default-key'); // With default

// Check if value exists
if ($config->has('feature_enabled')) {
    $enabled = $config->get('feature_enabled');
}

// Get all configuration
$allConfig = $config->all();

// Get configuration by visibility
$publicConfig = $config->getPublicConfig();     // Frontend-safe
$protectedConfig = $config->getProtectedConfig(); // SSR + Backend
$privateConfig = $config->getPrivateConfig();     // Backend only
```

#### Setting Values
```php
// Set a single value
$config->set('new_feature', true);

// Set nested values
$config->set('mail.smtp.host', 'smtp.example.com');
$config->set('api.endpoints.users', '/api/v1/users');

// Set with explicit visibility
$config->set('public_key', 'pk_123', 'public');
$config->set('private_key', 'sk_456', 'private');

// Remove a value
$config->forget('old_setting');
```

#### Visibility Management
```php
// Set visibility for existing keys
$config->setVisibility('app_name', 'public');
$config->setVisibility('database_url', 'private');

// Get visibility level
$visibility = $config->getVisibility('app_name'); // Returns 'public'

// Batch visibility updates
$config->setVisibilities([
    'app_name' => 'public',
    'api_url' => 'protected',
    'secret_key' => 'private',
]);
```

### Working with Nested Configuration

```php
// Set nested configuration
$config->set('mail', [
    'driver' => 'smtp',
    'host' => 'smtp.example.com',
    'port' => 587,
    'credentials' => [
        'username' => 'user@example.com',
        'password' => 'secret123',
    ]
]);

// Access nested values
$mailDriver = $config->get('mail.driver');
$mailHost = $config->get('mail.host');
$mailPassword = $config->get('mail.credentials.password');

// Set visibility for nested values
$config->setVisibility('mail.driver', 'protected');
$config->setVisibility('mail.credentials.password', 'private');
```

## Configuration Inheritance

QuVel Kit supports parent-child tenant relationships with automatic configuration inheritance, allowing enterprise setups where child tenants inherit base configuration from parent tenants.

### Setting Up Inheritance

```php
// Create parent tenant
$parentTenant = Tenant::create([
    'name' => 'Parent Corp',
    'domain' => 'parent.example.com',
]);

$parentConfig = new DynamicTenantConfig([
    'mail_host' => 'smtp.parent.com',
    'mail_from_name' => 'Parent Corp Support',
    'app_timezone' => 'America/New_York',
    'feature_a' => true,
    'feature_b' => false,
], [
    'mail_host' => 'protected',
    'app_timezone' => 'public',
    'feature_a' => 'public',
    'feature_b' => 'public',
    // mail_from_name private by default
]);

$parentTenant->config = $parentConfig;
$parentTenant->save();

// Create child tenant
$childTenant = Tenant::create([
    'name' => 'Child Division',
    'domain' => 'child.example.com',
    'parent_id' => $parentTenant->id, // Establishes inheritance
]);

$childConfig = new DynamicTenantConfig([
    'app_name' => 'Child App',      // Child-specific
    'feature_a' => false,           // Overrides parent
    'feature_c' => true,            // Child-only feature
    // mail_host inherited from parent
    // app_timezone inherited from parent
    // feature_b inherited from parent
], [
    'app_name' => 'public',
    'feature_a' => 'public', 
    'feature_c' => 'public',
    // Other inherited visibility from parent
]);

$childTenant->config = $childConfig;
$childTenant->save();
```

### Effective Configuration

The effective configuration combines parent and child configurations:

```php
// Get child's effective configuration (includes inheritance)
$effectiveConfig = $childTenant->getEffectiveConfig();

$result = $effectiveConfig->all();
// Returns:
// [
//     'app_name' => 'Child App',           // From child
//     'feature_a' => false,                // Child overrides parent
//     'feature_c' => true,                 // Child-only
//     'mail_host' => 'smtp.parent.com',    // Inherited from parent
//     'mail_from_name' => 'Parent Corp Support', // Inherited
//     'app_timezone' => 'America/New_York', // Inherited
//     'feature_b' => false,                // Inherited
// ]
```

### Inheritance Rules

1. **Child values override parent values** for the same key
2. **Parent values are inherited** when not defined in child
3. **Visibility is inherited** from parent when not explicitly set in child
4. **Nested objects are merged**, with child values taking precedence
5. **Multiple inheritance levels** are supported (grandparent → parent → child)

### Multi-Level Inheritance

```php
// Grandparent tenant
$grandparent = Tenant::create(['name' => 'Global Corp']);
$grandparent->config = new DynamicTenantConfig([
    'global_setting' => 'value',
    'shared_feature' => true,
]);

// Parent tenant
$parent = Tenant::create([
    'name' => 'Regional Office', 
    'parent_id' => $grandparent->id
]);
$parent->config = new DynamicTenantConfig([
    'regional_setting' => 'value',
    'shared_feature' => false, // Overrides grandparent
]);

// Child tenant
$child = Tenant::create([
    'name' => 'Local Branch',
    'parent_id' => $parent->id
]);
$child->config = new DynamicTenantConfig([
    'local_setting' => 'value',
    // Inherits global_setting from grandparent
    // Inherits shared_feature (false) from parent
    // Inherits regional_setting from parent
]);

$effectiveConfig = $child->getEffectiveConfig();
// Contains settings from all three levels
```

## Helper Functions

QuVel Kit provides a comprehensive set of helper functions for working with tenant configuration.

### Tenant Context Management

```php
// Set tenant context and apply configuration
setTenant($tenantId);           // By ID
setTenant('domain.com');        // By domain  
setTenant($tenantInstance);     // By instance

// Set tenant context without applying configuration
setTenantContext($tenantId);

// Get current tenant
$tenant = getTenant();
if ($tenant) {
    echo "Current tenant: " . $tenant->name;
}

// Check if tenant context exists
if (hasTenant()) {
    // Tenant-specific logic
}
```

### Configuration Access

```php
// Get tenant configuration object
$config = getTenantConfig();

// Get specific configuration value
$appName = getTenantConfig('app_name');
$apiKey = getTenantConfig('api_key', 'default-key'); // With default

// Get nested configuration
$mailHost = getTenantConfig('mail.host');
$dbPassword = getTenantConfig('database.password');

// Set configuration (in memory only)
setTenantConfig('feature_enabled', true);
setTenantConfig('api_key', 'new-key', 'private'); // With visibility

// Batch configuration updates
setTenantConfig([
    'app_name' => 'Updated Name',
    'feature_a' => true,
    'feature_b' => false,
]);
```

### Configuration Persistence

```php
// Save configuration changes to database
$tenant = getTenant();
$config = $tenant->config;

$config->set('new_setting', 'value');
$config->setVisibility('new_setting', 'public');

$tenant->config = $config;
$tenant->save(); // Persists to database

// Or use the helper for immediate persistence
saveTenantConfig('immediate_setting', 'value', 'public');
```

### Visibility Helpers

```php
// Get configuration by visibility level
$publicConfig = getPublicTenantConfig();     // Frontend-safe
$protectedConfig = getProtectedTenantConfig(); // SSR-safe
$privateConfig = getPrivateTenantConfig();     // Backend-only

// Check configuration visibility
$visibility = getTenantConfigVisibility('app_name'); // Returns 'public'

// Get frontend-ready configuration
$frontendConfig = getFrontendTenantConfig(); // Only public + protected
```

## Configuration Seeders

Configuration seeders provide default values for tenant configuration during tenant creation. They support both static values and dynamic functions.

### Static Configuration Seeders

```php
// In modules/YourModule/config/tenant.php
return [
    'seeders' => [
        'basic' => [
            'config' => [
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.example.com',
                'session_lifetime' => 120,
                'feature_enabled' => true,
            ],
            'visibility' => [
                'mail_driver' => 'protected',
                'mail_host' => 'private',
                'session_lifetime' => 'public',
                'feature_enabled' => 'public',
            ],
            'priority' => 50,
        ],
    ],
];
```

### Dynamic Configuration Seeders

```php
// Dynamic seeder with business logic
'seeders' => [
    'isolated' => [
        'config' => function(string $template, array $config): array {
            $tenantId = substr(uniqid(), -8);
            $domain = $config['domain'] ?? 'example.com';
            
            return [
                'cache_prefix' => "tenant_{$tenantId}_",
                'session_cookie' => "session_{$tenantId}",
                'db_database' => "tenant_{$tenantId}_db",
                'app_url' => "https://{$domain}",
                'mail_from_address' => "support@{$domain}",
            ];
        },
        'visibility' => [
            'cache_prefix' => 'private',
            'session_cookie' => 'protected',
            'db_database' => 'private',
            'app_url' => 'public',
            'mail_from_address' => 'private',
        ],
        'priority' => 20,
    ],
],
```

### Shared Seeders

Shared seeders apply to all tenant templates:

```php
'shared_seeders' => [
    'common_config' => [
        'config' => function(string $template, array $config): array {
            $commonConfig = [];
            
            // Apply environment-based defaults
            if (env('RECAPTCHA_SITE_KEY')) {
                $commonConfig['recaptcha_site_key'] = env('RECAPTCHA_SITE_KEY');
                $commonConfig['recaptcha_secret_key'] = env('RECAPTCHA_SECRET_KEY');
            }
            
            return $commonConfig;
        },
        'visibility' => [
            'recaptcha_site_key' => 'public',
            'recaptcha_secret_key' => 'private',
        ],
        'priority' => 15,
    ],
],
```

### Seeder Priority

Seeders run in priority order (higher numbers first):

- **100-90**: Core system defaults
- **89-50**: Module-specific defaults  
- **49-1**: Customization and overrides

## Configuration Templates

QuVel Kit provides tenant templates for different use cases:

### Basic Template
- Shared infrastructure
- Simple configuration
- Cost-effective for standard tenants

### Isolated Template  
- Dedicated resources
- Enhanced security
- Enterprise-grade isolation

### Template Selection

```php
// Create tenant with specific template
$tenant = Tenant::create([
    'name' => 'Enterprise Client',
    'domain' => 'enterprise.example.com',
    'template' => 'isolated', // or 'basic'
]);

// Template-specific seeders are automatically applied
```

## Advanced Configuration Patterns

### Conditional Configuration

```php
// Configuration based on tenant properties
'config' => function(string $template, array $config): array {
    $tenantConfig = [];
    
    // Enterprise tenants get enhanced features
    if ($template === 'isolated') {
        $tenantConfig['max_users'] = 1000;
        $tenantConfig['advanced_features'] = true;
        $tenantConfig['dedicated_support'] = true;
    } else {
        $tenantConfig['max_users'] = 50;
        $tenantConfig['advanced_features'] = false;
        $tenantConfig['dedicated_support'] = false;
    }
    
    return $tenantConfig;
}
```

### Environment-Based Configuration

```php
'config' => function(string $template, array $config): array {
    $environment = app()->environment();
    
    return [
        'debug_mode' => $environment === 'local',
        'log_level' => $environment === 'production' ? 'error' : 'debug',
        'cache_ttl' => $environment === 'production' ? 3600 : 60,
    ];
}
```

### Feature Flag Management

```php
// Feature flags with gradual rollout
'config' => function(string $template, array $config): array {
    $tenantId = $config['_tenant_id'] ?? 0;
    
    return [
        'feature_new_ui' => $tenantId % 10 === 0, // 10% rollout
        'feature_beta_api' => in_array($tenantId, [1, 5, 12, 25]), // Specific tenants
        'feature_analytics' => $template === 'isolated', // Enterprise only
    ];
}
```

## Configuration Validation

### Input Validation

```php
// Validate configuration before saving
function validateTenantConfig(DynamicTenantConfig $config): array
{
    $errors = [];
    
    // Required fields
    if (!$config->has('app_name')) {
        $errors[] = 'App name is required';
    }
    
    // Email format validation
    if ($config->has('mail_from_address')) {
        $email = $config->get('mail_from_address');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
    }
    
    // Numeric validation
    if ($config->has('session_lifetime')) {
        $lifetime = $config->get('session_lifetime');
        if (!is_numeric($lifetime) || $lifetime < 1) {
            $errors[] = 'Session lifetime must be positive number';
        }
    }
    
    return $errors;
}

// Usage
$config = getTenantConfig();
$errors = validateTenantConfig($config);
if (!empty($errors)) {
    throw new ValidationException('Configuration validation failed', $errors);
}
```

### Schema Validation

```php
use Illuminate\Support\Facades\Validator;

function validateConfigSchema(array $config): bool
{
    $rules = [
        'app_name' => 'required|string|max:255',
        'mail_from_address' => 'email',
        'session_lifetime' => 'integer|min:1|max:10080',
        'feature_enabled' => 'boolean',
        'api_key' => 'string|min:32',
    ];
    
    $validator = Validator::make($config, $rules);
    
    return $validator->passes();
}
```

## Caching and Performance

### Configuration Caching

```php
// Configuration is automatically cached per request
// Manual cache management if needed
use Illuminate\Support\Facades\Cache;

$cacheKey = "tenant_config_{$tenant->id}";
$config = Cache::remember($cacheKey, 3600, function() use ($tenant) {
    return $tenant->getEffectiveConfig();
});

// Clear tenant configuration cache
Cache::forget("tenant_config_{$tenant->id}");
```

### Lazy Loading

```php
// Configuration is loaded only when needed
class OptimizedTenantService
{
    private ?DynamicTenantConfig $config = null;
    
    public function getConfig(): DynamicTenantConfig
    {
        if ($this->config === null) {
            $this->config = getTenantConfig();
        }
        
        return $this->config;
    }
}
```

## Debugging Configuration

### Configuration Inspection

```php
// Debug current configuration
$config = getTenantConfig();
dd([
    'all_config' => $config->all(),
    'public_config' => $config->getPublicConfig(),
    'protected_config' => $config->getProtectedConfig(),
    'private_config' => $config->getPrivateConfig(),
]);

// Debug effective configuration with inheritance
$tenant = getTenant();
$effectiveConfig = $tenant->getEffectiveConfig();
dd([
    'tenant_config' => $tenant->config->all(),
    'effective_config' => $effectiveConfig->all(),
    'parent_config' => $tenant->parent?->config->all(),
]);
```

### Configuration Tracing

```php
// Trace configuration value source
function traceConfigValue(string $key): array
{
    $tenant = getTenant();
    $trace = [];
    
    // Check current tenant
    if ($tenant->config->has($key)) {
        $trace['current'] = $tenant->config->get($key);
    }
    
    // Check parent hierarchy
    $parent = $tenant->parent;
    while ($parent) {
        if ($parent->config->has($key)) {
            $trace["parent_{$parent->id}"] = $parent->config->get($key);
        }
        $parent = $parent->parent;
    }
    
    return $trace;
}

// Usage
$trace = traceConfigValue('mail_host');
// Shows where the configuration value comes from
```

---

[← Back to Tenant Middleware](./tenant-middleware.md) | [Next: Tenant Examples →](./tenant-examples.md)