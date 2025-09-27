<?php

declare(strict_types=1);

namespace Quvel\Core\Http\Middleware;

use Quvel\Core\Services\CaptchaService;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to verify captcha tokens.
 * Protects endpoints from bots and automated attacks.
 */
class VerifyCaptcha
{
    public function __construct(
        private readonly CaptchaService $captchaService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $inputField = 'captcha_token'): mixed
    {
        if (!$this->captchaService->isEnabled()) {
            return $next($request);
        }

        $token = $request->input($inputField);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => __('quvel-core::messages.captcha.token_required'),
            ], 422);
        }

        $result = $this->captchaService->verify((string) $token, $request->ip());

        if ($result->isFailed()) {
            return response()->json([
                'success' => false,
                'message' => __('quvel-core::messages.captcha.verification_failed'),
                'errors' => $result->errorCodes,
            ], 422);
        }

        if ($result->hasScore()) {
            $threshold = config('quvel-core.captcha.providers.recaptcha_v3.score_threshold', 0.5);

            if (!$result->meetsScoreThreshold($threshold)) {
                return response()->json([
                    'success' => false,
                    'message' => __('quvel-core::messages.captcha.score_too_low'),
                    'score' => $result->score,
                ], 422);
            }
        }

        return $next($request);
    }
}