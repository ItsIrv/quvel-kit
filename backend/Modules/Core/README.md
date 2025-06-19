# QuVel Core Module

Core module for QuVel Kit - Provides base functionality, service providers, middleware, and traits.

## Installation

You can install the package via composer:

```bash
composer require itsirv/quvel-core
```

## Usage

This module is automatically registered by Laravel Modules. It provides:

### Service Providers

- `CoreServiceProvider` - Main service provider
- `ModuleServiceProvider` - Base module service provider
- `ModuleRouteServiceProvider` - Base route service provider

### Middleware

- `SetTraceId` - Adds trace ID to requests
- `SetRequestLocale` - Sets locale based on request
- `VerifyCaptcha` - Verifies CAPTCHA tokens
- `IsInternalRequest` - Checks if request is internal
- `CheckValue` - Validates configuration values

### Services

- `FrontendService` - Frontend configuration service
- `CaptchaService` - CAPTCHA verification service
- `UserCreateService` - User creation service
- `UserFindService` - User lookup service
- `RequestPrivacy` - Request privacy service

### Traits

- `RendersBadRequest` - Bad request response handling
- `TranslatableEnum` - Translatable enum support
- `TranslatableException` - Translatable exception support

### Contracts

- `CaptchaVerifierInterface` - CAPTCHA verifier interface
- `TranslatableEntity` - Translatable entity interface

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
