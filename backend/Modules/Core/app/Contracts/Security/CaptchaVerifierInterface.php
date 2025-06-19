<?php

namespace Modules\Core\Contracts\Security;

/**
 * Captcha Verifier Interface
 */
interface CaptchaVerifierInterface
{
    public function verify(string $token, ?string $ip = null): bool;
}
