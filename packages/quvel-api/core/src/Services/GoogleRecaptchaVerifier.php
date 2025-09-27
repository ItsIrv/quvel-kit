<?php

declare(strict_types=1);

namespace Quvel\Core\Services;

use Quvel\Core\Concerns\Security\CaptchaVerifierInterface;
use Quvel\Core\Concerns\Security\CaptchaVerificationResult;
use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Http\Client\ConnectionException;

/**
 * Google reCAPTCHA v3 verifier implementation.
 */
class GoogleRecaptchaVerifier implements CaptchaVerifierInterface
{
    public function __construct(
        private readonly HttpClient $http
    ) {
    }

    /**
     * Verify a reCAPTCHA token.
     */
    public function verify(string $token, ?string $ip = null, ?string $action = null): CaptchaVerificationResult
    {
        $secretKey = config('quvel-core.captcha.providers.recaptcha_v3.secret_key');

        if (!$secretKey) {
            return CaptchaVerificationResult::failure([CaptchaVerificationResult::ERROR_MISSING_SECRET]);
        }

        try {
            $response = $this->http
                ->timeout(config('quvel-core.captcha.timeout', 30))
                ->asForm()
                ->post(config('quvel-core.captcha.providers.recaptcha_v3.verify_url'), [
                    'secret' => $secretKey,
                    'response' => $token,
                    'remoteip' => $ip,
                ]);

            $data = $response->json();

            if (!$data || !is_array($data)) {
                return CaptchaVerificationResult::failure([CaptchaVerificationResult::ERROR_NETWORK_ERROR]);
            }

            if (!($data['success'] ?? false)) {
                $errorCodes = $data['error-codes'] ?? [CaptchaVerificationResult::ERROR_INVALID_RESPONSE];
                return CaptchaVerificationResult::failure($errorCodes);
            }

            return CaptchaVerificationResult::success(
                score: $data['score'] ?? null,
                action: $data['action'] ?? $action,
                challengeTimestamp: $data['challenge_ts'] ?? null,
                hostname: $data['hostname'] ?? null
            );

        } catch (ConnectionException) {
            return CaptchaVerificationResult::failure([CaptchaVerificationResult::ERROR_NETWORK_ERROR]);
        }
    }

    /**
     * Check if provider supports scoring.
     */
    public function supportsScoring(): bool
    {
        return true;
    }

    /**
     * Get default score threshold.
     */
    public function getDefaultScoreThreshold(): ?float
    {
        return 0.5;
    }
}