<?php

namespace Modules\Core\Http\Middleware\Security;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Services\Security\CaptchaService;

/**
 * Verifies that the captcha token is valid.
 */
class VerifyCaptcha
{
    public function __construct(private readonly CaptchaService $captchaService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->input('captcha_token');

        if ($token === null || !$this->captchaService->verify((string) $token, $request->ip())) {
            return response()->json(['message' => 'Captcha verification failed.'], 422);
        }

        return $next($request);
    }
}
