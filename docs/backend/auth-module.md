# Auth Module

## Overview

The Auth module provides a comprehensive authentication system for QuVel Kit, built on Laravel Sanctum with enhanced security features. This module handles user authentication, token management, OAuth integration via Socialite, and HMAC signature verification for secure client-server communication.

## Key Components

### Service Providers

The Auth module is bootstrapped through the main service provider:

1. **AuthServiceProvider**: Registers all authentication services as **scoped** dependencies:
   - HmacService
   - ClientNonceService
   - ServerTokenService
   - UserAuthenticationService
   - NonceSessionService
   - SocialiteService
   
   > **Note**: All services are registered as **scoped** rather than singleton to support multi-tenancy. This ensures that services depending on tenant-specific configuration or other tenant-scoped services will be properly instantiated for each tenant context.

### Core Services

#### UserAuthenticationService

Handles user authentication with email/password and OAuth providers:

- Authenticates users with credentials
- Manages OAuth authentication flow with provider-specific identifiers
- Creates or retrieves user accounts
- Verifies email status based on configuration

#### OAuthCoordinator

Orchestrates the OAuth authentication flow:

- Creates and manages client nonces
- Builds redirect responses for OAuth providers
- Authenticates callbacks from OAuth providers
- Handles both stateless and session-based authentication

#### ServerTokenService

Manages secure token generation and validation:

- Creates signed tokens for authentication
- Maps server tokens to client nonces
- Validates token signatures
- Handles token expiration with configurable TTL

#### HmacService

Provides HMAC signature verification for enhanced security:

- Signs data with a shared secret
- Verifies signatures to prevent tampering
- Provides methods for signing and extracting values with HMAC

#### ClientNonceService

Manages client nonces for secure OAuth flows:

- Creates unique client nonces
- Tracks nonce state throughout the authentication flow
- Associates user IDs with nonces
- Handles nonce expiration

#### NonceSessionService

Manages nonce storage in the session:

- Stores nonces with timestamps
- Validates nonce expiration
- Clears expired nonces

#### SocialiteService

Manages OAuth authentication with third-party providers:

- Handles OAuth redirects with dynamic redirect URIs
- Processes OAuth callbacks
- Integrates with tenant context for multi-tenant applications

## Authentication Flows

### Traditional Authentication Flows

1. User submits credentials (email/password)
2. UserAuthenticationService validates credentials
3. On success, the user is authenticated
4. The session or token is used for subsequent requests

### OAuth Authentication Flows

The module supports both stateless and session-based OAuth flows:

#### Session-Based Flow

1. User initiates OAuth login
2. SocialiteService redirects to the OAuth provider
3. Provider redirects back with authorization code
4. SocialiteService exchanges code for user information
5. UserAuthenticationService creates or retrieves user account
6. User is logged in via session

#### Stateless Flow

1. Client creates a nonce via ClientNonceService
2. Client requests OAuth redirect with the nonce
3. Server creates a server token mapped to the client nonce
4. User is redirected to OAuth provider with the server token
5. Provider redirects back with the server token and authorization code
6. Server validates the token and retrieves the original nonce
7. UserAuthenticationService creates or retrieves user account
8. Client nonce is associated with the user ID
9. Client redeems the nonce to complete authentication

### HMAC Security Flows

The Auth module uses HMAC signatures to secure tokens and nonces:

1. Server generates tokens/nonces with HmacService
2. Values are signed with a shared secret
3. Signed values are verified before processing
4. Invalid signatures are rejected

## Routes

The Auth module provides the following routes:

### Traditional Authentication Routes

- `POST /auth/login`: User login with email/password
- `POST /auth/register`: User registration
- `POST /auth/logout`: User logout
- `GET /auth/session`: Check session status

### Email Verification Routes

- `POST /auth/email/verification-notification`: Request verification email
- `GET /auth/email/verify/{id}/{hash}`: Verify email

### Password Reset Routes

- `POST /auth/forgot-password`: Request password reset
- `GET /auth/password/{token}`: Password reset form

### OAuth Authentication Routes

- `GET /auth/provider/{provider}/redirect`: Redirect to OAuth provider
- `GET /auth/provider/{provider}/callback`: Handle OAuth callback
- `POST /auth/provider/{provider}/callback`: Handle OAuth callback (POST)
- `POST /auth/provider/{provider}/create-nonce`: Create client nonce
- `POST /auth/provider/{provider}/redeem-nonce`: Redeem client nonce

## Configuration

The Auth module is configured through `config/config.php`:

```php
return [
    'name' => 'Auth',
    
    // Disable Socialite Authentication
    'disable_socialite' => env('AUTH_DISABLE_SOCIALITE', false),
    
    // User must verify email before login
    'verify_email_before_login' => env('AUTH_VERIFY_EMAIL_BEFORE_LOGIN', true),
    
    // Socialite Configuration
    'socialite' => [
        // Supported providers (comma-separated)
        'providers' => explode(',', env('SOCIALITE_PROVIDERS', 'google')),
        
        // Nonce TTL in minutes
        'nonce_ttl' => env('SOCIALITE_NONCE_TTL', 60),
        
        // Token TTL in minutes
        'token_ttl' => env('SOCIALITE_TOKEN_TTL', 60),
        
        // HMAC Secret Key for signing
        'hmac_secret' => env('HMAC_SECRET_KEY'),
    ],
];
```

## Multi-Tenancy Integration

The Auth module integrates with the multi-tenancy system:

- OAuth redirects include tenant context via the SocialiteService
- Dynamic redirect URIs are generated based on tenant configuration
- Authentication respects tenant boundaries

## Testing

The Auth module includes comprehensive tests:

```bash
# Run Auth module tests
php artisan test --group=auth
```

---

[← Back to Backend Documentation](./README.md)
