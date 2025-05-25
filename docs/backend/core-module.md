# Core Module

## Overview

The Core module provides the foundational functionality for QuVel Kit, including base services, security features, frontend configuration, and common utilities used across all other modules.

## Architecture

### Service Registration

The Core module registers essential services and providers:

```php
public function register(): void
{
    $this->app->register(ModuleServiceProvider::class);
    $this->app->register(ModuleRouteServiceProvider::class);
}

public function boot(): void
{
    parent::boot();

    // Register tenant configuration provider
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                \Modules\Core\Providers\CoreTenantConfigProvider::class
            );
        });
    }
}
```

## Core Services

### FrontendService

Manages frontend URL generation and redirects:

```php
class FrontendService
{
    public function url(string $path = '', array $queryParams = []): string
    {
        $baseUrl = config('frontend.url');
        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
        
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        return $url;
    }
    
    public function redirect(string $path = '', array $queryParams = []): RedirectResponse
    {
        return redirect()->away($this->url($path, $queryParams));
    }
}
```

### Security Services

#### CaptchaService

Provides CAPTCHA verification through configurable providers:

```php
interface CaptchaVerifierInterface
{
    public function verify(string $captchaResponse): bool;
}

// Google reCAPTCHA implementation
class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    public function verify(string $captchaResponse): bool
    {
        // Verifies with Google reCAPTCHA API
    }
}
```

### User Services

#### UserCreateService

Handles user creation with multi-tenant support:

```php
class UserCreateService
{
    public function create(array $data): User
    {
        return User::create([
            'tenant_id' => getTenant()?->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }
}
```

#### UserFindService

Provides user lookup methods:

```php
class UserFindService
{
    public function find(int $id): ?User
    {
        return User::find($id);
    }
    
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
```

## Core Traits

### RendersBadRequest

Standardizes bad request responses:

```php
trait RendersBadRequest
{
    public function renderBadRequest(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => ['general' => [$message]],
        ], $code);
    }
}
```

### TranslatableEnum

Enables translation support for enums:

```php
trait TranslatableEnum
{
    public function translate(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        $key = $this->getTranslationKey();
        
        return trans($key, [], $locale);
    }
    
    abstract protected function getTranslationKey(): string;
}
```

### TranslatableException

Adds translation support to exceptions:

```php
trait TranslatableException
{
    public function getTranslatedMessage(?string $locale = null): string
    {
        return trans($this->getTranslationKey(), $this->getTranslationParameters(), $locale);
    }
}
```

## Tenant Configuration Provider

### CoreTenantConfigProvider

The Core module provides essential configuration to the frontend through the `CoreTenantConfigProvider`:

```php
class CoreTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config' => [
                // Frontend service configuration
                'frontend_service_url' => config('frontend.url'),
                'frontend_internal_api_url' => config('frontend.internal_api_url'),
                
                // API configuration
                'api_version' => config('app.api_version', 'v1'),
                'api_timeout' => config('app.api_timeout', 30),
                
                // Localization
                'supported_locales' => config('app.supported_locales', ['en']),
                'default_locale' => config('app.locale', 'en'),
                
                // Application settings
                'app_debug' => config('app.debug', false),
                'app_environment' => config('app.env', 'production'),
            ],
            'visibility' => [
                'frontend_service_url' => 'protected',      // SSR only
                'frontend_internal_api_url' => 'protected', // SSR only
                'api_version' => 'public',                  // Browser
                'api_timeout' => 'public',                  // Browser
                'supported_locales' => 'public',            // Browser
                'default_locale' => 'public',               // Browser
                'app_debug' => 'protected',                 // SSR only
                'app_environment' => 'protected',           // SSR only
            ],
        ];
    }

    public function priority(): int
    {
        return 100; // High priority as Core module
    }
}
```

This configuration is automatically merged with tenant configuration and made available to the frontend based on visibility settings.

## Module Service Providers

### ModuleServiceProvider

Base class for all module service providers:

```php
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Register module-specific configuration
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );
    }
    
    /**
     * Register module translations
     */
    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/' . $this->moduleNameLower);
        
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(
                module_path($this->moduleName, 'lang'),
                $this->moduleNameLower
            );
        }
    }
}
```

### ModuleRouteServiceProvider

Base class for module route providers:

```php
abstract class ModuleRouteServiceProvider extends ServiceProvider
{
    /**
     * Map web routes for the module
     */
    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path($this->moduleName, '/routes/web.php'));
    }
    
    /**
     * Map API routes for the module
     */
    protected function mapApiRoutes(): void
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path($this->moduleName, '/routes/api.php'));
    }
}
```

## Core Enums

### StatusEnum

Common status values used across modules:

```php
enum StatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case DELETED = 'deleted';
}
```

### CoreHeader

HTTP headers used by the Core module:

```php
enum CoreHeader: string
{
    case X_REQUESTED_WITH = 'X-Requested-With';
    case X_CSRF_TOKEN = 'X-CSRF-TOKEN';
    case X_TENANT_ID = 'X-Tenant-ID';
    case X_API_KEY = 'X-API-Key';
}
```

## Contracts

### TranslatableEntity

Interface for entities that support translation:

```php
interface TranslatableEntity
{
    public function translate(?string $locale = null): string;
    public function getTranslationKey(): string;
}
```

### Security Contracts

```php
namespace Modules\Core\Contracts\Security;

interface CaptchaVerifierInterface
{
    /**
     * Verify the CAPTCHA response
     */
    public function verify(string $captchaResponse): bool;
    
    /**
     * Get the CAPTCHA provider name
     */
    public function getProviderName(): string;
}
```

## Configuration

The Core module configuration is defined in `config/config.php`:

```php
return [
    'name' => 'Core',
    
    // Frontend configuration
    'frontend' => [
        'url' => env('FRONTEND_URL', 'https://quvel.127.0.0.1.nip.io'),
        'internal_api_url' => env('FRONTEND_INTERNAL_API_URL'),
    ],
    
    // Security settings
    'security' => [
        'captcha' => [
            'enabled' => env('CAPTCHA_ENABLED', true),
            'provider' => env('CAPTCHA_PROVIDER', 'google'),
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        ],
    ],
    
    // API settings
    'api' => [
        'version' => env('API_VERSION', 'v1'),
        'timeout' => env('API_TIMEOUT', 30),
    ],
];
```

## Testing

The Core module includes comprehensive tests for all services and traits:

```bash
# Run Core module tests
php artisan test Modules/Core

# Run specific test suites
php artisan test --filter CoreServiceProviderTest
php artisan test --filter UserCreateServiceTest
php artisan test --filter TranslatableEnumTest
```

---

[ê Back to Backend Documentation](./README.md)