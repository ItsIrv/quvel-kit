<?php

namespace Modules\Auth\Traits;

use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\Core\Enums\StatusEnum;

/**
 * Overrides the default send email verification and password reset notifications.
 */
trait UseAuthModuleNotifications
{
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    public function sendPasswordResetNotification($token): void
    {
        if ($this->provider_id !== null) {
            abort(403, StatusEnum::INTERNAL_ERROR->value);
        }

        $this->notify(new ResetPassword($token));
    }
}
