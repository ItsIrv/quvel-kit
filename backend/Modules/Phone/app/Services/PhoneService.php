<?php

namespace Modules\Phone\Services;

use App\Models\User;

/**
 * Handles phone number operations including validation, formatting,
 * OTP generation, and verification.
 */
class PhoneService
{
    public function __construct(
        private readonly OtpCacheService $otpCacheService,
    ) {
    }

    /**
     * Generate and return OTP for phone verification.
     */
    public function generateOtp(): string
    {
        $length = config('phone.otp.length', 6);

        return str_pad((string) random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Format phone number to a consistent format for storage.
     */
    public function formatPhoneNumber(string $phoneNumber, string $country = null): string
    {
        $country ??= config('phone.phone.default_country', 'US');

        $cleanPhone = preg_replace('/\D/', '', $phoneNumber);

        return $cleanPhone;
    }

    /**
     * Check if phone number is available (not taken by another user).
     */
    public function isPhoneAvailable(string $phoneNumber, ?int $excludeUserId = null): bool
    {
        $formatted = $this->formatPhoneNumber($phoneNumber);
        $query     = User::where('phone', $formatted)
            ->whereNotNull('phone_verified_at');

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return !$query->exists();
    }

    /**
     * Verify OTP against cached phone number and return the phone if valid.
     */
    public function verifyOtpFromCache(User $user, string $otp): ?string
    {
        $phoneNumber = $this->otpCacheService->getPhoneByOtp($otp, $user->id);

        if (!$phoneNumber) {
            return null;
        }

        if ($user->phone !== $otp) {
            return null;
        }

        return $phoneNumber;
    }
}
