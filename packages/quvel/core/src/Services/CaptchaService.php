<?php

declare(strict_types=1);

namespace Quvel\Core\Services;

use Quvel\Core\Concerns\Security\CaptchaVerifierInterface;
use Quvel\Core\Concerns\Security\CaptchaVerificationResult;

/**
 * Captcha service that delegates to configured verifier.
 */
class CaptchaService
{
    public function __construct(
        private readonly CaptchaVerifierInterface $verifier
    ) {
    }

    /**
     * Verify a captcha token.
     */
    public function verify(string $token, ?string $ip = null, ?string $action = null): CaptchaVerificationResult
    {
        if (!config('quvel-core.captcha.enabled', true)) {
            return CaptchaVerificationResult::success();
        }

        return $this->verifier->verify($token, $ip, $action);
    }

    /**
     * Check if captcha verification is enabled.
     */
    public function isEnabled(): bool
    {
        return config('quvel-core.captcha.enabled', true);
    }

}