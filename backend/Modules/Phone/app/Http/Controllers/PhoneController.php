<?php

namespace Modules\Phone\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Core\Http\Controllers\Controller;
use Modules\Phone\Actions\RemovePhoneAction;
use Modules\Phone\Actions\SendVerificationAction;
use Modules\Phone\Actions\VerifyPhoneAction;
use Modules\Phone\Enums\PhoneStatusEnum;
use Modules\Phone\Http\Requests\RemovePhoneRequest;
use Modules\Phone\Http\Requests\SendVerificationRequest;
use Modules\Phone\Http\Requests\VerifyPhoneRequest;

class PhoneController extends Controller
{
    /**
     * Send verification code to phone number.
     */
    public function sendVerification(
        SendVerificationRequest $request,
        SendVerificationAction $action,
    ): JsonResponse {
        $user        = $request->user();
        $key         = "phone_verification:{$user->id}";
        $maxAttempts = config('phone.rate_limit.attempts', 3);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message'     => PhoneStatusEnum::RATE_LIMITED->value,
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, config('phone.rate_limit.decay_minutes', 60) * 60);

        try {
            $result = $action($user, $request->validated('phone'));

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    /**
     * Verify phone number with OTP.
     */
    public function verify(
        VerifyPhoneRequest $request,
        VerifyPhoneAction $action,
    ): JsonResponse {
        try {
            $result = $action($request->user(), $request->validated('otp'));

            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 422);
        }
    }

    /**
     * Remove phone number from account.
     */
    public function remove(
        RemovePhoneRequest $request,
        RemovePhoneAction $action,
    ): JsonResponse {
        $result = $action($request->user());

        return response()->json($result);
    }
}
