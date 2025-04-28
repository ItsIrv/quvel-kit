# Auth Module

## Overview

The Auth module provides a comprehensive authentication system for QuVel Kit, built on Laravel Sanctum with enhanced security features. This module handles user authentication, token management, OAuth integration via Socialite, and HMAC signature verification.

## Key Components

### Service Providers

The Auth module is bootstrapped through two main service providers:

1. **AuthServiceProvider**: Registers all authentication services and dependencies

2. **FortifyServiceProvider**: Configures Laravel Fortify for traditional authentication flows

### Core Services

The Auth module provides several key services:

#### UserAuthenticationService

Handles user authentication with email/password and OAuth providers. This service:

- Authenticates users with credentials
- Manages OAuth authentication flow
- Creates or retrieves user accounts

#### ServerTokenService

Manages secure token generation and validation:

- Creates signed tokens for authentication
- Validates token signatures
- Handles token expiration

#### HmacService

Provides HMAC signature verification for enhanced security:

- Signs data with a shared secret
- Verifies signatures to prevent tampering
- Secures communication between client and server

#### SocialiteService

Manages OAuth authentication with third-party providers:

- Handles OAuth redirects
- Processes OAuth callbacks
- Integrates with tenant context for multi-tenant applications

## Authentication Flows

### Traditional Authentication

1. User submits credentials (email/password)
2. UserAuthenticationService validates credentials
3. On success, a token is generated and returned
4. Token is used for subsequent API requests

### OAuth Authentication

1. Client requests OAuth redirect URL from SocialiteService
2. User is redirected to OAuth provider (Google, GitHub, etc.)
3. Provider redirects back with authorization code
4. SocialiteService exchanges code for user information
5. UserAuthenticationService creates or retrieves user account
6. Token is generated and returned to client

### HMAC Security

The Auth module uses HMAC signatures to secure sensitive operations:

1. Client generates a nonce (one-time value)
2. Server signs the nonce with HmacService
3. Signed token is returned to client
4. Client includes token in subsequent requests
5. Server verifies signature before processing request

## Multi-Tenancy Integration

The Auth module integrates with the multi-tenancy system:

- OAuth redirects include tenant context
- User accounts are scoped to tenants
- Authentication respects tenant boundaries

## Configuration

The Auth module is configured through the `config/auth.php` file:

```php
// Key configuration options
'oauth' => [
    'hmac_secret' => env('OAUTH_HMAC_SECRET'),
    'token_ttl' => env('OAUTH_TOKEN_TTL', 60), // minutes
    'providers' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
        // Other providers...
    ],
],
```

## Security Considerations

The Auth module implements several security measures:

1. **Token-Based Authentication**: Uses Laravel Sanctum for secure token management
2. **HMAC Signatures**: Prevents request tampering and replay attacks
3. **Rate Limiting**: Prevents brute force attacks
4. **Secure Defaults**: Follows security best practices by default

## Testing

The Auth module includes comprehensive tests:

```bash
# Run Auth module tests
php artisan test --group=auth-module
```

## Extending the Auth Module

The Auth module can be extended to support additional authentication methods:

1. Create a new service in the Auth module
2. Register the service in AuthServiceProvider
3. Create necessary controllers and routes
4. Update tests to cover new functionality

## Best Practices

1. **Always Use HTTPS**: Ensure all authentication endpoints use HTTPS
2. **Token Management**: Implement proper token expiration and refresh mechanisms
3. **Validation**: Validate all authentication inputs
4. **Error Handling**: Provide clear error messages for authentication failures
5. **Audit Logging**: Log authentication events for security monitoring

---

[← Back to Backend Documentation](./README.md)
