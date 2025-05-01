<?php

namespace Modules\Auth\Traits;

use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\VerifyEmail;

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
        $this->notify(new ResetPassword($token));
    }
}
