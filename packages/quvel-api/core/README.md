# Quvel Core

A Laravel package for the Core functionality of Quvel applications, providing essential web application functionality including captcha verification, request security, logging enhancements, and platform-aware services.

## Features

### 🛡️ Security & Captcha
- **reCAPTCHA v3 Integration** - Complete reCAPTCHA v3 implementation with score-based validation
- **Internal Request Validation** - Secure API endpoints with IP and API key validation
- **Configurable Security** - Flexible security settings for different environments

### 🌐 Request Handling
- **Platform Detection** - Automatically detect web, mobile (Capacitor), and desktop (Electron/Tauri) platforms
- **Locale Middleware** - Automatic locale detection from Accept-Language headers
- **Smart Redirects** - Platform-aware redirects with deep linking support for mobile/desktop apps

### 📊 Logging & Tracing
- **Contextual Logging** - Enhanced logging with automatic context enrichment
- **PII Sanitization** - Built-in sanitization for sensitive data in logs
- **Distributed Tracing** - UUID-based request tracing across services
- **Configurable Enrichment** - Customizable log context via closures

### 🔧 Developer Tools
- **Debug Proxy Info** - Debug endpoint for troubleshooting proxy and request issues
- **Translation Support** - Full i18n support for all user-facing messages
- **Exception Traits** - Consistent error response formatting

## Installation

```bash
composer require quvel/core
```

The package uses the Laravel auto-discovery, so no manual registration is required.

### Publish Configuration

```bash
# Publish config file
php artisan vendor:publish --tag=quvel-core-config

# Publish translations (optional)
php artisan vendor:publish --tag=quvel-core-lang
```

## Configuration

Copy the example environment variables to your `.env` file:

```bash
cp vendor/quvel/core/.env.example .env.core.example
```

### Required Configuration

**reCAPTCHA v3 Setup:**
```env
RECAPTCHA_SITE_KEY=your_site_key_here
RECAPTCHA_SECRET_KEY=your_secret_key_here
```

**Security (for internal API requests):**
```env
SECURITY_API_KEY=your_internal_api_key_here
```

**Frontend Integration:**
```env
FRONTEND_URL=http://localhost:3000
```

See [.env.example](.env.example) for all available configuration options.

## Usage

### Middleware

The package provides several middleware with the `quvel.` prefix:

```php
Route::middleware(['quvel.captcha:g-recaptcha-response'])->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
});

Route::middleware(['quvel.internal-only'])->group(function () {
    Route::get('/admin/stats', [AdminController::class, 'stats']);
});

Route::middleware(['quvel.locale'])->group(function () {
    // Routes with automatic locale detection
});

Route::middleware(['quvel.trace'])->group(function () {
    // Routes with distributed tracing
});

Route::middleware(['quvel.config-gate:feature.enabled,true'])->group(function () {
    // Feature-gated routes
});
```

### Services

**Captcha Verification:**
```php
use Quvel\Core\Services\CaptchaService;

public function verify(Request $request, CaptchaService $captcha)
{
    $result = $captcha->verify(
        token: $request->input('g-recaptcha-response'),
        ip: $request->ip()
    );

    if ($result->isFailed()) {
        return back()->withErrors(['captcha' => 'Captcha verification failed']);
    }

    // Check score for reCAPTCHA v3
    if ($result->hasScore() && !$result->meetsScoreThreshold(0.7)) {
        return back()->withErrors(['captcha' => 'Suspicious activity detected']);
    }
}
```

**Platform-Aware Redirects:**
```php
use Quvel\Core\Services\RedirectService;

public function success(RedirectService $redirect)
{
    // Automatically handles web/mobile/desktop platforms
    return $redirect->redirectWithMessage('/dashboard', 'Login successful!');

    // Check platform
    if ($redirect->isPlatform('mobile')) {
        // Mobile-specific logic
    }
}
```

**Enhanced Logging:**
```php
use Quvel\Core\Logs\ContextualLogger;
use Quvel\Core\Logs\SanitizedContext;

$logger = new ContextualLogger(app('log'), 'audit');

// Log with automatic PII sanitization
$logger->info('User registered', SanitizedContext::forPii([
    'email' => 'user@example.com',     // Will become '@example.com'
    'password' => 'secret123',         // Will become '[REMOVED]'
    'phone' => '+1234567890',          // Will become '+1****7890'
]));
```

### Custom Implementations

**Custom Captcha Provider:**
```php
use Quvel\Core\Concerns\Security\CaptchaVerifierInterface;

class CustomCaptchaVerifier implements CaptchaVerifierInterface
{
    public function verify(string $token, ?string $ip = null, ?string $action = null): CaptchaVerificationResult
    {
        // Your implementation
    }

    // Implement other required methods...
}

// In a service provider
$this->app->bind(CaptchaVerifierInterface::class, CustomCaptchaVerifier::class);
```

**Custom Log Context Enrichment:**
```php
use Quvel\Core\Logs\ContextualLogger;

ContextualLogger::setContextEnricher(function ($context, $prefix) {
    // Add custom context data
    $context['app_version'] = config('app.version');
    $context['tenant_id'] = auth()->user()?->tenant_id;

    return $context;
});
```

## Middleware Reference

| Middleware               | Alias                 | Purpose                              |
|--------------------------|-----------------------|--------------------------------------|
| `ConfigGate`             | `quvel.config-gate`   | Feature gates based on config values |
| `LocaleMiddleware`       | `quvel.locale`        | Automatic locale detection           |
| `TraceMiddleware`        | `quvel.trace`         | Distributed tracing                  |
| `VerifyCaptcha`          | `quvel.captcha`       | reCAPTCHA v3 verification            |
| `RequireInternalRequest` | `quvel.internal-only` | Internal API protection              |

## Error Codes

The package uses constants for all error codes to ensure consistency:

```php
use Quvel\Core\Concerns\Security\CaptchaVerificationResult;

// Available error constants:
CaptchaVerificationResult::ERROR_MISSING_SECRET
CaptchaVerificationResult::ERROR_INVALID_RESPONSE
CaptchaVerificationResult::ERROR_NETWORK_ERROR
```

## Debug Tools

When `APP_DEBUG=true`, you can use the debug action to troubleshoot proxy and request issues:

```php
use Quvel\Core\Http\Actions\DebugProxyInfoAction;

Route::get('/debug/proxy', DebugProxyInfoAction::class);
```

## Translation

All user-facing messages support translation. Publish the language files to customize:

```bash
php artisan vendor:publish --tag=quvel-core-lang
```

Messages are namespaced under `quvel-core::messages.*`.

## Requirements

- PHP 8.1+
- Laravel 10.0+

## Security

This package follows security best practices:

- All user input is validated and sanitized
- Secret keys are never logged or exposed
- Configurable IP restrictions for internal endpoints
- PII sanitization in logs
- Secure HTTP client configuration with timeouts

## License

MIT License.