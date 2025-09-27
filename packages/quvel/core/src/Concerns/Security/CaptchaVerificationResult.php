<?php

declare(strict_types=1);

namespace Quvel\Core\Concerns\Security;

/**
 * Result object for captcha verification operations.
 * Provides detailed information about the verification attempt.
 */
readonly class CaptchaVerificationResult
{
    public function __construct(
        public bool $success,
        public ?float $score = null,
        public ?string $action = null,
        public ?string $challengeTimestamp = null,
        public ?string $hostname = null,
        public array $errorCodes = [],
    ) {
    }

    /**
     * Create a successful verification result.
     */
    public static function success(
        ?float $score = null,
        ?string $action = null,
        ?string $challengeTimestamp = null,
        ?string $hostname = null
    ): self {
        return new self(
            success: true,
            score: $score,
            action: $action,
            challengeTimestamp: $challengeTimestamp,
            hostname: $hostname
        );
    }

    /**
     * Create a failed verification result.
     */
    public static function failure(array $errorCodes = []): self
    {
        return new self(
            success: false,
            errorCodes: $errorCodes
        );
    }

    /**
     * Check if the verification was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->success;
    }

    /**
     * Check if the verification failed.
     */
    public function isFailed(): bool
    {
        return !$this->success;
    }

    /**
     * Check if the verification has a score (reCAPTCHA v3).
     */
    public function hasScore(): bool
    {
        return $this->score !== null;
    }

    /**
     * Check if the score meets the minimum threshold.
     */
    public function meetsScoreThreshold(float $threshold): bool
    {
        return $this->hasScore() && $this->score >= $threshold;
    }

    /**
     * Check if there are any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errorCodes);
    }
}