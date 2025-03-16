<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

class HmacService
{
    private readonly string $hmacSecret;

    public function __construct(
        private readonly ConfigRepository $config,
    ) {
        $this->hmacSecret = $this->config->get('auth.oauth.hmac_secret');
    }

    /**
     * Generate HMAC signature for the given value.
     */
    public function sign(string $value): string
    {
        return hash_hmac('sha256', $value, $this->hmacSecret);
    }

    /**
     * Verify HMAC signature.
     */
    public function verify(string $value, string $hmac): bool
    {
        return hash_equals($this->sign($value), $hmac);
    }

    /**
     * Sign and return value as `{value}.{hmac}`.
     */
    public function signWithHmac(string $value): string
    {
        return "$value.{$this->sign($value)}";
    }

    /**
     * Extract and verify `{value}.{hmac}`, returning the original value if valid.
     */
    public function extractAndVerify(string $signedValue): ?string
    {
        if (! str_contains($signedValue, '.')) {
            return null;
        }

        $parts = explode('.', $signedValue, 2);

        [$value, $hmac] = $parts;

        return $this->verify($value, $hmac) ? $value : null;
    }
}
