# Authentication

## Overview

QuVel Kit implements a robust authentication system using Laravel Sanctum for token-based authentication and Laravel Socialite for OAuth integration. This guide covers the authentication architecture, implementation details, and usage patterns.

## Authentication Architecture

The authentication system is implemented in the `Auth` module and provides:

- Token-based authentication using Laravel Sanctum
- Social authentication via Socialite OAuth providers
- HMAC signature verification for enhanced security
- Multi-tenancy integration for tenant-specific authentication

## Authentication Flow

### Traditional Authentication

1. User submits credentials (email/password)
2. Credentials are validated against the database
3. On success, a Sanctum token is generated and returned
4. Token is used for subsequent API requests

### Social Authentication

1. Client requests OAuth redirect URL
2. User is redirected to OAuth provider (Google, GitHub, etc.)
3. Provider redirects back with authorization code
4. Code is exchanged for user information
5. User account is created or retrieved
6. Sanctum token is generated and returned

## Authentication Configuration

Configure authentication settings in the Auth module's configuration `Modules/Auth/config/config.php`:

```php
return [
    'name' => 'Auth',
    'oauth' => [
        'hmac_secret' => env('OAUTH_HMAC_SECRET'),
        'token_ttl' => env('OAUTH_TOKEN_TTL', 60), // minutes
        'providers' => [
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'redirect' => env('GOOGLE_REDIRECT_URI'),
            ],
            // Other providers
        ],
    ],
];
```

## Key Services

### UserAuthenticationService

Handles user authentication with email/password and OAuth providers:

```php
// Inject the service
private UserAuthenticationService $authService;

// Authenticate a user
$user = $this->authService->authenticate($email, $password);

// Get the current authenticated user
$currentUser = $this->authService->getCurrentUser();
```

### SocialiteService

Manages OAuth authentication with third-party providers:

```php
// Inject the service
private SocialiteService $socialiteService;

// Get OAuth redirect URL
$redirectUrl = $this->socialiteService->getRedirectUrl('google');

// Handle OAuth callback
$user = $this->socialiteService->handleCallback('google', $code);
```

## Capacitor Integration

All authentication flows work in Capacitor, including Socialite OAuth flows. This is implemented through:

1. WebSockets and Laravel Echo for real-time communication
2. Tenant-aware authentication context
3. Secure token storage

A non-socket method will be developed when more thought is put into deep links, custom URL schemes, and other mobile app features.

## Security Considerations

- Tokens are hashed and stored securely
- HMAC signatures prevent token tampering
- CSRF protection is enabled for web routes
- Rate limiting is applied to authentication endpoints
- Failed login attempts are logged and can trigger lockouts

## Testing Authentication

The Auth module includes comprehensive tests for authentication flows:

```bash
# Run Auth module tests
php artisan test --group=auth-module
```

---

[← Back to Backend Documentation](./README.md)
