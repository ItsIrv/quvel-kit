<?php

namespace Modules\Core\Services\Security;

use Modules\Core\Contracts\Security\CaptchaVerifierInterface;

/**
 * Captcha Service
 *
 * Handles captcha verification.
 */
class CaptchaService
{
    public function __construct(
        private readonly CaptchaVerifierInterface $verifier,
    ) {
    }

    /**
     * Verifies that the captcha token is valid.
     */
    public function verify(string $token, ?string $ip = null): bool
    {
        return $this->verifier->verify($token, $ip);
    }
}
