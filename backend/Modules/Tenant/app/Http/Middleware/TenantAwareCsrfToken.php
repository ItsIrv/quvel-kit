<?php

namespace Modules\Tenant\Http\Middleware;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Modules\Tenant\Contexts\TenantContext;
use Symfony\Component\HttpFoundation\Cookie;

class TenantAwareCsrfToken extends VerifyCsrfToken
{
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
        parent::__construct(app(), app('encrypter'));
    }

    /**
     * Get the tenant-specific CSRF token cookie name.
     */
    protected function getCookieName(): string
    {
        $tenant = $this->tenantContext->has() ? $this->tenantContext->get() : null;
        if ($tenant !== null) {
            return "XSRF-TOKEN-{$tenant->public_id}";
        }

        return 'XSRF-TOKEN';
    }

    /**
     * Create a new XSRF-TOKEN cookie with tenant-specific name.
     *
     * @phpstan-ignore-next-line
     */
    protected function newCookie($request, $config)
    {
        $cookieName = $this->getCookieName();

        return new Cookie(
            $cookieName,
            $request->session()->token(),
            $this->availableAt(60 * $config['lifetime']),
            $config['path'],
            $config['domain'],
            $config['secure'],
            false, // HttpOnly false for XSRF tokens (JavaScript needs access)
            false,
            $config['same_site'] ?? null,
            $config['partitioned'] ?? false
        );
    }

    /**
     * Add the CSRF token to the response cookies.
     */
    protected function addCookieToResponse($request, mixed $response)
    {
        $config = config('session');

        if ($response instanceof Responsable) {
            $response = $response->toResponse($request);
        }

        $response->headers->setCookie($this->newCookie($request, $config));

        return $response;
    }

    /**
     * Get the CSRF token from the request with tenant-aware cookie handling.
     */
    protected function getTokenFromRequest(mixed $request): ?string
    {
        $token = $request->input('_token') ?? $request->header('X-CSRF-TOKEN');

        if (($token === null || $token === '' || $token === '0') && ($header = $request->header('X-XSRF-TOKEN')) !== null) {
            try {
                $token = CookieValuePrefix::remove(
                    $this->encrypter->decrypt($header, static::serialized()),
                );
            } catch (DecryptException) {
                $token = '';
            }
        }

        // Check tenant-specific XSRF token cookie
        if ($token === null || $token === '' || $token === '0') {
            $cookieName = $this->getCookieName();
            $cookieValue = $request->cookie($cookieName);
            if ($cookieValue !== null && is_string($cookieValue)) {
                try {
                    $token = CookieValuePrefix::remove(
                        $this->encrypter->decrypt($cookieValue, static::serialized()),
                    );
                } catch (DecryptException) {
                    $token = '';
                }
            }
        }

        return $token;
    }

    /**
     * Determine if the cookie contents should be serialized.
     */
    public static function serialized()
    {
        // This is tricky - we need to check if ANY XSRF token cookie should be serialized
        // For simplicity, use the default behavior
        return EncryptCookies::serialized('XSRF-TOKEN');
    }
}
