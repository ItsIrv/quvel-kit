# Quvel Core

The foundational package for the Quvel framework, providing essential services and abstractions for Laravel applications.

## Features

### 🔐 Security
- **Captcha Management**: Multi-provider captcha system (reCAPTCHA v2/v3, hCaptcha, Turnstile)
- **Request Validation**: Flexible internal request authentication
- **Configurable Strategies**: Multiple validation approaches

### 📊 Logging
- **Contextual Logger**: Enhanced PSR-3 logger with automatic context enrichment
- **Sensitive Data Sanitization**: Automatic PII sanitization with configurable rules
- **Trace Integration**: Built-in distributed tracing support

### 🌐 HTTP
- **API Controllers**: Standardized API response formatting
- **Response Helpers**: Consistent JSON response structure
- **Locale Middleware**: Advanced locale detection and negotiation

### 🏷️ Type Safety
- **Enums**: HTTP status codes, headers, operation statuses
- **Traits**: Reusable functionality for translations and error handling

## Installation

```bash
composer require quvel/core
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=quvel-core-config
```

## Usage

### Captcha Management

```php
use Quvel\Core\Security\CaptchaManager;

$captcha = app(CaptchaManager::class);
$result = $captcha->verify($token, $ip, $action);

if ($result->isSuccessful()) {
    // Captcha verification passed
    $score = $result->score; // For reCAPTCHA v3
}
```

### API Responses

```php
use Quvel\Core\Http\Controllers\ApiController;

class MyController extends ApiController
{
    public function index()
    {
        return $this->success($data, 'Retrieved successfully');
    }

    public function store()
    {
        return $this->created($resource);
    }

    public function error()
    {
        return $this->validationError($errors);
    }
}
```

### Contextual Logging

```php
use Quvel\Core\Logging\ContextualLogger;

$logger = app(ContextualLogger::class);
$logger = $logger->withPrefix('auth')->withContext(['user_id' => 123]);

$logger->info('User logged in'); // Automatically includes trace_id and context
```

### Sensitive Data Sanitization

```php
use Quvel\Core\Logging\SensitiveDataSanitizer;

$sanitizer = SensitiveDataSanitizer::with([
    'email' => 'user@example.com',
    'password' => 'secret123',
    'token' => 'abc123def456'
]);

$clean = $sanitizer->toArray();
// Result: ['email' => '@example.com', 'password' => '[REMOVED]', 'token' => 'ab****56']
```

## Configuration

### Environment Variables

```env
# Captcha
CAPTCHA_PROVIDER=recaptcha_v3
CAPTCHA_ENABLED=true
RECAPTCHA_SITE_KEY=your_site_key
RECAPTCHA_SECRET_KEY=your_secret_key
RECAPTCHA_SCORE_THRESHOLD=0.5

# Security
SECURITY_VALIDATION_STRATEGY=strict
SECURITY_TRUSTED_IPS="127.0.0.1,::1"
SECURITY_API_KEY=your_internal_api_key

# Headers
HEADER_TRACE_ID=X-Trace-ID
HEADER_SSR_KEY=X-SSR-Key

# Logging
LOG_INCLUDE_TRACE_ID=true
LOG_CONTEXT_ENRICHMENT=true
LOG_SANITIZE_SENSITIVE=true

# Locale
LOCALE_STRATEGY=header_with_fallback
LOCALE_ALLOWED="en,es,fr"
LOCALE_FALLBACK=en
LOCALE_NORMALIZE=true
```

### Advanced Configuration

See `config/core.php` for full configuration options including:

- Multiple captcha providers
- Custom sanitization rules
- Middleware registration control
- Advanced locale detection strategies

## Architecture

This package follows clean architecture principles:

- **Contracts**: Interfaces for extensibility
- **Enums**: Type-safe constants and status codes
- **Traits**: Reusable functionality
- **Services**: Business logic implementations
- **HTTP**: Web layer abstractions
- **Security**: Authentication and validation
- **Logging**: Enhanced logging capabilities

## License

MIT License. See LICENSE file for details.