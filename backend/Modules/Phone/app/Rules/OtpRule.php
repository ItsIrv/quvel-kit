<?php

namespace Modules\Phone\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for OTP codes.
 */
class OtpRule implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail('The :attribute must be a valid OTP code.');
            return;
        }

        $otpLength = config('phone.otp.length', 6);

        if (!ctype_digit($value) || strlen($value) !== $otpLength) {
            $fail("The :attribute must be a {$otpLength}-digit code.");
        }
    }

    /**
     * Static helper to check if a value is a valid OTP.
     */
    public static function isValidOtp(string $value): bool
    {
        $otpLength = config('phone.otp.length', 6);

        return strlen($value) === $otpLength && ctype_digit($value);
    }
}
