<?php

namespace Modules\Phone\Services;

use Illuminate\Cache\Repository as Cache;

/**
 * Handles OTP storage and retrieval using cache.
 * Maps OTP codes to phone numbers temporarily during verification.
 */
class OtpCacheService
{
    public function __construct(
        private readonly Cache $cache,
    ) {
    }

    /**
     * Store OTP to phone number mapping in cache.
     */
    public function storeOtp(string $otp, string $phoneNumber, int $userId): void
    {
        $key = $this->getOtpKey($otp, $userId);
        $ttl = config('phone.otp.ttl', 300);

        $this->cache->put($key, $phoneNumber, $ttl);
    }

    /**
     * Retrieve phone number by OTP and user ID.
     */
    public function getPhoneByOtp(string $otp, int $userId): ?string
    {
        $key = $this->getOtpKey($otp, $userId);

        return $this->cache->get($key);
    }

    /**
     * Clear OTP from cache after verification.
     */
    public function clearOtp(string $otp, int $userId): void
    {
        $key = $this->getOtpKey($otp, $userId);

        $this->cache->forget($key);
    }

    /**
     * Check if OTP exists in cache.
     */
    public function otpExists(string $otp, int $userId): bool
    {
        $key = $this->getOtpKey($otp, $userId);

        return $this->cache->has($key);
    }

    /**
     * Generate cache key for OTP.
     */
    public function getOtpKey(string $otp, int $userId): string
    {
        $prefix = config('phone.otp.cache_prefix', 'phone_otp:');

        return "$prefix$userId:$otp";
    }
}
