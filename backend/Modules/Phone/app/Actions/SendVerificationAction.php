<?php

namespace Modules\Phone\Actions;

use App\Models\User;
use Modules\Phone\Enums\PhoneStatusEnum;
use Modules\Phone\Exceptions\InvalidPhoneNumberException;
use Modules\Phone\Rules\PhoneNumberRule;
use Modules\Phone\Services\OtpCacheService;
use Modules\Phone\Services\PhoneService;
use Illuminate\Support\Facades\Validator;

/**
 * Sends OTP verification to a phone number.
 */
class SendVerificationAction
{
    public function __construct(
        private readonly PhoneService $phoneService,
        private readonly OtpCacheService $otpCacheService,
    ) {
    }

    /**
     * Send OTP to phone number for verification.
     */
    public function __invoke(User $user, string $phoneNumber): array
    {
        $validator = Validator::make(
            ['phone' => $phoneNumber],
            ['phone' => [new PhoneNumberRule()]],
        );

        if ($validator->fails()) {
            throw new InvalidPhoneNumberException(
                $validator->errors()->first('phone'),
            );
        }

        if ($user->phone_verified_at || !empty($user->phone)) {
            return [
                'status' => PhoneStatusEnum::ALREADY_VERIFIED->value,
            ];
        }

        $formattedPhone = $this->phoneService->formatPhoneNumber($phoneNumber);

        if (!$this->phoneService->isPhoneAvailable($formattedPhone, $user->id)) {
            throw new InvalidPhoneNumberException(PhoneStatusEnum::PHONE_UNAVAILABLE->value);
        }

        $otp = $this->phoneService->generateOtp();

        $this->otpCacheService->storeOtp($otp, $formattedPhone, $user->id);

        $user->update([
            'phone'             => $otp,
            'phone_verified_at' => null,
        ]);

        return [
            'status'     => PhoneStatusEnum::VERIFICATION_SENT->value,
            'expires_at' => now()->addSeconds(config('phone.otp.ttl', 300)),
        ];
    }
}
