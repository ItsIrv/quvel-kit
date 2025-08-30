<?php

namespace Modules\Phone\Actions;

use App\Models\User;
use Modules\Phone\Enums\PhoneStatusEnum;
use Modules\Phone\Exceptions\OtpExpiredException;
use Modules\Phone\Rules\OtpRule;
use Modules\Phone\Services\OtpCacheService;
use Modules\Phone\Services\PhoneService;
use Illuminate\Support\Facades\Validator;

/**
 * Verifies phone number with OTP code.
 */
class VerifyPhoneAction
{
    public function __construct(
        private readonly PhoneService $phoneService,
        private readonly OtpCacheService $otpCacheService,
    ) {
    }

    /**
     * Verify OTP and update user's phone number.
     */
    public function __invoke(User $user, string $otp): array
    {
        $validator = Validator::make(
            ['otp' => $otp],
            ['otp' => [new OtpRule()]],
        );

        if ($validator->fails()) {
            throw new OtpExpiredException(
                $validator->errors()->first('otp'),
            );
        }

        if (!$user->phone) {
            throw new OtpExpiredException(PhoneStatusEnum::NO_PHONE->value);
        }

        $phoneNumber = $this->phoneService->verifyOtpFromCache($user, $otp);

        if (!$phoneNumber) {
            throw new OtpExpiredException(PhoneStatusEnum::INVALID_OTP->value);
        }

        $user->update([
            'phone'             => $phoneNumber,
            'phone_verified_at' => now(),
        ]);

        $this->otpCacheService->clearOtp($otp, $user->id);

        return [
            'status'      => PhoneStatusEnum::VERIFIED->value,
            'phone'       => $user->phone,
            'verified_at' => $user->phone_verified_at?->toISOString(),
        ];
    }
}
