# Authentication

## Overview

The Auth module provides a comprehensive authentication system for QuVel Kit, integrating traditional email/password authentication with OAuth providers. Built on Laravel Sanctum for token-based authentication and Laravel Socialite for OAuth integration, it features secure token management, HMAC signature verification, and multi-tenancy support.

## Architecture

### Service Registration

The Auth module registers **scoped services** to support multi-tenancy, ensuring each tenant context gets fresh service instances. Services include `UserAuthenticationService`, `HmacService`, `SocialiteService`, and OAuth-related services.

The module also registers configuration pipes and default authentication settings for all tenants through `registerAuthConfigSeeders()`.

### Core Services

| Service | Responsibility | Key Methods |
|---------|---------------|-------------|
| **UserAuthenticationService** | Handles user authentication | `attempt()`, `handleOAuthLogin()`, `logInWithId()` |
| **OAuthCoordinator** | Orchestrates OAuth flow | `createClientNonce()`, `buildRedirectResponse()`, `authenticateCallback()` |
| **HmacService** | Provides cryptographic security | `sign()`, `verify()`, `signWithHmac()`, `extractAndVerify()` |
| **ClientNonceService** | Manages client-side nonces | `create()`, `getNonce()`, `assignUserToNonce()` |
| **ServerTokenService** | Manages server-side tokens | `create()`, `getClientNonce()`, `forget()` |
| **SocialiteService** | Interfaces with OAuth providers | `getRedirectResponse()`, `getProviderUser()` |
| **NonceSessionService** | Manages session-based nonces | `setNonce()`, `getNonce()`, `isValid()` |

## Authentication Flows

### Traditional Authentication

Standard email/password authentication using `UserAuthenticationService::attempt()`. The system validates user credentials, ensures the user has a password (not OAuth-only), and establishes a session upon successful authentication.

### OAuth Authentication

The module supports both stateless (API/mobile) and session-based (web) OAuth flows.

#### OAuth Flow Types

**Session-Based Flow**: Traditional web flow where the browser is redirected to the OAuth provider and back, creating a server session.

**Stateless Flow (API/Mobile)**: Uses nonces and tokens for mobile/API clients. The `OAuthCoordinator` manages the nonce creation, provider redirect, callback handling, and nonce redemption process.

### HMAC Security

The `HmacService` provides cryptographic security for tokens and nonces using HMAC signatures. It offers methods for signing values, verifying signatures, and extracting verified values from signed data to prevent tampering and replay attacks.

## Configuration

The Auth module is configured through environment variables and can be overridden per tenant:

```dotenv
# General Auth Settings
AUTH_DISABLE_SOCIALITE=false
AUTH_VERIFY_EMAIL_BEFORE_LOGIN=true

# Socialite Configuration
SOCIALITE_PROVIDERS=google,github,facebook
SOCIALITE_NONCE_TTL=60
SOCIALITE_TOKEN_TTL=60
HMAC_SECRET_KEY=your-secure-key-here

# Provider-specific settings (in services.php)
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
```

### Tenant-Specific Configuration

The `AuthConfigPipe` processes tenant-specific authentication settings including OAuth providers, session configuration, and authentication behavior. It has priority 50 and handles settings like `socialite_providers`, `oauth_credentials`, and `session_timeout`.

Tenants can override default authentication configuration by setting values in their `DynamicTenantConfig`.

## Routes

### Traditional Authentication

- `POST /auth/login`: Authenticate with email/password
- `POST /auth/register`: Create new account
- `GET /auth/session`: Check authentication status
- `POST /auth/logout`: End session

### OAuth

- `GET /auth/provider/{provider}/redirect`: Redirect to OAuth provider
- `GET|POST /auth/provider/{provider}/callback`: Handle provider response
- `POST /auth/provider/{provider}/create-nonce`: Create client nonce (stateless)
- `POST /auth/provider/{provider}/redeem-nonce`: Exchange nonce for session (stateless)

### Security Features

- Rate limiting on all endpoints
- CAPTCHA verification for registration and password reset
- Email verification enforcement (configurable)
- Secure token handling with HMAC signatures

## Multi-Tenancy Support

The Auth module is fully multi-tenant aware:

- Configuration can be overridden per tenant via `AuthConfigPipe`
- Frontend receives tenant-specific auth settings resolved by `AuthConfigPipe`

### Example: Tenant-Specific OAuth

Different tenants can have different OAuth provider configurations. Premium tenants might have access to multiple providers (Google, Microsoft) with custom credentials, while basic tenants might be limited to Google authentication only.

## Capacitor Integration

All authentication flows work seamlessly in Capacitor, including OAuth flows:

- **WebSockets**: Real-time communication via Laravel Echo for stateful auth flows
- **Tenant Context**: Authentication maintains tenant awareness across platforms
- **Token Storage**: Secure storage mechanisms for each platform
- **Deep Links**: Support for custom URL schemes for OAuth callbacks (in development)

## Security Considerations

- **Token Security**: All tokens are hashed and stored securely
- **HMAC Signatures**: Prevents token tampering and replay attacks
- **CSRF Protection**: Enabled for all web routes
- **Rate Limiting**: Applied to all authentication endpoints
- **Logging**: Failed login attempts are logged with IP and user agent information

## Testing

Run the comprehensive test suite:

```bash
# Run all Auth module tests
php artisan test --group=auth

# Run specific test groups
php artisan test --group=auth-fortify
php artisan test --group=auth-actions
```

---

[← Back to Backend Documentation](./README.md)
