# Authentication

## Overview

The Auth module provides a comprehensive authentication system for QuVel Kit, integrating traditional email/password authentication with OAuth providers. Built on Laravel Sanctum for token-based authentication and Laravel Socialite for OAuth integration, it features secure token management, HMAC signature verification, and multi-tenancy support.

## Architecture

### Service Registration

The Auth module uses **scoped services** to support multi-tenancy. All services are registered in the `AuthServiceProvider`:

```php
public function register(): void
{
    $this->app->register(RouteServiceProvider::class);

    // All services are scoped to support multi-tenancy
    $this->app->scoped(HmacService::class);
    $this->app->scoped(ClientNonceService::class);
    $this->app->scoped(ServerTokenService::class);
    $this->app->scoped(UserAuthenticationService::class);
    $this->app->scoped(NonceSessionService::class);
    $this->app->scoped(SocialiteService::class);
}

public function boot(): void
{
    parent::boot();

    // Register configuration pipe for tenant-specific auth settings
    if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
        $this->app->booted(function () {
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                \Modules\Auth\Pipes\AuthConfigPipe::class
            );
            
            // Register tenant configuration provider
            \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                \Modules\Auth\Providers\AuthTenantConfigProvider::class
            );
        });
    }
}
```

> **Note**: Scoped services ensure proper instantiation for each tenant context, allowing services to depend on tenant-specific configuration.

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

```php
// LoginAction example
public function __invoke(LoginRequest $request): JsonResponse
{
    $data = $request->validated();
    $user = $this->userFindService->findByEmail($data['email']);
    
    // Validate user exists and has password (not OAuth-only)
    if (!$user || !$user->password || $user->provider_id) {
        throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
    }
    
    // Attempt authentication
    $success = $this->userAuthenticationService->attempt(
        $data['email'], 
        $data['password']
    );
    
    if (!$success) {
        throw new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);
    }
    
    return $this->responseFactory->json([
        'message' => AuthStatusEnum::LOGIN_SUCCESS->value,
        'user' => new UserResource($user)
    ], 201);
}
```

### OAuth Authentication

The module supports both stateless (API/mobile) and session-based (web) OAuth flows.

#### Session-Based Flow

1. User clicks "Login with Google"
2. Browser is redirected to Google
3. After authentication, Google redirects back with an authorization code
4. Server exchanges code for user data and creates a session

```php
// Simplified callback handling
public function __invoke(CallbackRequest $request, string $provider)
{
    $state = $request->validated('state', '');
    $result = $this->authCoordinator->authenticateCallback($provider, $state);
    
    // For session-based flow
    if (!$result->isStateless()) {
        return $this->frontendService->redirect('', [
            'message' => $result->getStatus()->value
        ]);
    }
    
    // For stateless flow...
}
```

#### Stateless Flow (API/Mobile)

```php
// Client creates a nonce
$nonce = $authCoordinator->createClientNonce();

// Server creates a token mapped to the nonce
$redirectResponse = $authCoordinator->buildRedirectResponse('google', $nonce);

// After OAuth callback
$result = $authCoordinator->authenticateCallback('google', $signedToken);
$signedNonce = $result->getSignedNonce();

// Client redeems the nonce
$user = $authCoordinator->redeemClientNonce($signedNonce);
```

### HMAC Security

HMAC signatures secure tokens and nonces throughout the authentication process:

```php
// Signing data
$signature = $hmacService->sign($value); // Returns HMAC signature
$signedValue = $hmacService->signWithHmac($value); // Returns "value.signature"

// Verifying data
$isValid = $hmacService->verify($value, $signature);
$extractedValue = $hmacService->extractAndVerify($signedValue); // Returns value if valid
```

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

The Auth module supports tenant-specific configuration through the `AuthConfigPipe`:

```php
// Example: Configure OAuth providers per tenant
$tenant->config->set('socialite_providers', ['google', 'microsoft']);
$tenant->config->set('auth_verify_email', false);
$tenant->config->set('oauth_providers', [
    'google' => [
        'client_id' => 'tenant-specific-client-id',
        'client_secret' => 'tenant-specific-secret',
    ],
]);
$tenant->save();
```

### AuthConfigPipe

The `AuthConfigPipe` processes tenant-specific authentication settings:

- **Priority**: 50 (runs after core configuration pipes)
- **Handles**: `socialite_providers`, `auth_verify_email`, `oauth_providers`
- **Purpose**: Allows tenants to have custom OAuth configurations

### AuthTenantConfigProvider

The `AuthTenantConfigProvider` exposes authentication configuration to the frontend:

```php
class AuthTenantConfigProvider implements TenantConfigProviderInterface
{
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config' => [
                'auth_providers' => $this->getEnabledProviders($tenant),
                'auth_verify_email' => config('auth.verify_email_before_login'),
            ],
            'visibility' => [
                'auth_providers' => 'public',      // Available in browser
                'auth_verify_email' => 'public',   // Available in browser
            ],
        ];
    }
}
```

This allows the frontend to dynamically show/hide OAuth provider buttons based on tenant configuration.

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

- All services are registered as **scoped** instead of singleton
- OAuth redirects include tenant context
- SocialiteService generates tenant-specific redirect URIs
- Configuration can be overridden per tenant via `AuthConfigPipe`
- Frontend receives tenant-specific auth settings via `AuthTenantConfigProvider`

### Example: Tenant-Specific OAuth

```php
// Premium tenant with custom OAuth
$premiumTenant->config->set('oauth_providers', [
    'google' => [
        'client_id' => 'premium-google-client',
        'client_secret' => 'premium-google-secret',
    ],
    'microsoft' => [
        'client_id' => 'premium-ms-client',
        'client_secret' => 'premium-ms-secret',
    ],
]);
$premiumTenant->config->set('socialite_providers', ['google', 'microsoft']);

// Basic tenant with limited OAuth
$basicTenant->config->set('socialite_providers', ['google']);
```

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
