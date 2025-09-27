<?php

declare(strict_types=1);

namespace Quvel\Core\Concerns\Security;

/**
 * Interface for captcha verification services.
 * Supports multiple captcha providers (reCAPTCHA v2/v3, hCaptcha, etc.)
 */
interface CaptchaVerifierInterface
{
    /**
     * Verify a captcha token.
     *
     * @param string $token The captcha response token
     * @param string|null $ip The user's IP address
     * @param string|null $action The expected action (for reCAPTCHA v3)
     * @return CaptchaVerificationResult The verification result with details
     */
    public function verify(string $token, ?string $ip = null, ?string $action = null): CaptchaVerificationResult;

    /**
     * Check if the provider supports scoring.
     *
     * @return bool True if provider supports scoring (like reCAPTCHA v3)
     */
    public function supportsScoring(): bool;

    /**
     * Get the default minimum score threshold (if applicable).
     *
     * @return float|null The default threshold, or null if scoring not supported
     */
    public function getDefaultScoreThreshold(): ?float;
}