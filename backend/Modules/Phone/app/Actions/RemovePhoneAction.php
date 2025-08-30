<?php

namespace Modules\Phone\Actions;

use App\Models\User;
use Modules\Phone\Enums\PhoneStatusEnum;

/**
 * Removes phone number from user account.
 */
class RemovePhoneAction
{
    /**
     * Remove phone number and verification status from user.
     */
    public function __invoke(User $user): array
    {
        if (empty($user->phone)) {
            return [
                'status' => PhoneStatusEnum::NO_PHONE->value,
            ];
        }

        $user->update([
            'phone'             => null,
            'phone_verified_at' => null,
        ]);

        return [
            'status' => PhoneStatusEnum::PHONE_REMOVED->value,
        ];
    }
}
