# Authentication

## Overview

QuVel Kit implements a robust authentication system using Laravel Sanctum for token-based authentication and Laravel Socialite for OAuth integration. This guide covers the authentication architecture, implementation details, and best practices.

## Authentication Architecture

The authentication system is implemented in the `Auth` module and provides:

- Token-based authentication using Laravel Sanctum
- Social authentication via Socialite OAuth providers
- HMAC signature verification for enhanced security
- Role-based access control (RBAC)

## Authentication Flow

1. **Registration**: User registers with email/password or OAuth provider
2. **Login**: User authenticates and receives a token
3. **Token Usage**: Token is included in subsequent API requests
4. **Verification**: Server validates the token for each protected request

## Authentication Services

### UserAuthenticationService

The `UserAuthenticationService` handles user authentication operations:

```php
use Modules\Auth\Services\UserAuthenticationService;

class AuthController
{
    public function __construct(private UserAuthenticationService $authService)
    {
    }
    
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);
        
        $result = $this->authService->authenticate($credentials['email'], $credentials['password']);
        
        if ($result->isSuccess()) {
            return response()->json([
                'token' => $result->getToken(),
                'user' => $result->getUser(),
            ]);
        }
        
        return response()->json([
            'message' => 'Invalid credentials',
        ], 401);
    }
}
```

### ServerTokenService

The `ServerTokenService` manages token generation and validation:

```php
use Modules\Auth\Services\ServerTokenService;

class TokenController
{
    public function __construct(private ServerTokenService $tokenService)
    {
    }
    
    public function refreshToken(Request $request)
    {
        $token = $this->tokenService->refreshToken($request->user());
        
        return response()->json([
            'token' => $token,
        ]);
    }
}
```

### HmacService

The `HmacService` provides HMAC signature verification for enhanced security:

```php
use Modules\Auth\Services\HmacService;

class SecureController
{
    public function __construct(private HmacService $hmacService)
    {
    }
    
    public function verifySignature(Request $request)
    {
        $isValid = $this->hmacService->verifySignature(
            $request->header('X-Signature'),
            $request->getContent()
        );
        
        if (!$isValid) {
            return response()->json([
                'message' => 'Invalid signature',
            ], 401);
        }
        
        // Process the request
    }
}
```

## Social Authentication

QuVel Kit supports authentication via OAuth providers using Laravel Socialite:

```php
use Modules\Auth\Services\SocialiteService;

class SocialAuthController
{
    public function __construct(private SocialiteService $socialiteService)
    {
    }
    
    public function redirect(string $provider)
    {
        return $this->socialiteService->redirect($provider);
    }
    
    public function callback(string $provider)
    {
        $result = $this->socialiteService->handleCallback($provider);
        
        if ($result->isSuccess()) {
            return response()->json([
                'token' => $result->getToken(),
                'user' => $result->getUser(),
            ]);
        }
        
        return response()->json([
            'message' => 'Authentication failed',
        ], 401);
    }
}
```

## Protecting Routes

### API Routes

Protect API routes using the `auth:sanctum` middleware:

```php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::apiResource('resources', ResourceController::class);
});
```

### Role-Based Access Control

Implement role-based access control using middleware:

```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('admin/users', AdminUserController::class);
});
```

## Authentication Configuration

Configure authentication settings in the Auth module's configuration:

```php
// Modules/Auth/config/config.php
return [
    'token_expiration' => env('AUTH_TOKEN_EXPIRATION', 60 * 24), // 24 hours
    'refresh_token_expiration' => env('AUTH_REFRESH_TOKEN_EXPIRATION', 60 * 24 * 7), // 7 days
    'providers' => [
        'github' => [
            'enabled' => env('GITHUB_AUTH_ENABLED', false),
        ],
        'google' => [
            'enabled' => env('GOOGLE_AUTH_ENABLED', false),
        ],
    ],
];
```

## Multi-Tenancy Integration

The authentication system integrates with the multi-tenancy system:

```php
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    // Tenant-specific authenticated routes
});
```

## Testing Authentication

Create tests for authentication endpoints:

```php
public function test_user_can_login()
{
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password'),
    ]);
    
    $response = $this->postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password',
    ]);
    
    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => [
                'id',
                'name',
                'email',
            ],
        ]);
}
```

## Security Best Practices

1. **HTTPS Only**: Always use HTTPS for authentication endpoints
2. **Token Expiration**: Set appropriate token expiration times
3. **Secure Storage**: Store tokens securely on the client side
4. **CSRF Protection**: Implement CSRF protection for web routes
5. **Rate Limiting**: Apply rate limiting to authentication endpoints
6. **Audit Logging**: Log authentication events for security auditing
7. **Password Policies**: Enforce strong password policies

## Troubleshooting

### Common Issues

- **Invalid Token**: Token has expired or is malformed
- **Unauthorized**: User does not have the required permissions
- **Rate Limited**: Too many authentication attempts

### Debugging

Enable debug mode in the Auth module for detailed error messages:

```php
// .env
AUTH_DEBUG=true
```

---

[← Back to Backend Documentation](./README.md)
