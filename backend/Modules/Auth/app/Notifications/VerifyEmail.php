<?php

namespace Modules\Auth\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends VerifyEmailBase
{
    /**
     * Build the email notification.
     */
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->subject(__('Verify Your Email Address'))
            ->greeting(__('Hello,'))
            ->line(__('Welcome to :app_name! We’re excited to have you join us.', [
                'app_name' => config('app.name'),
            ]))
            ->line(__('Before you can start using your account, please verify your email address by clicking the button below:'))
            ->action(__('Verify Email Address'), $url)
            ->line(__('If you didn’t create an account with us, please ignore this email.'))
            ->salutation(__('Best regards,') . "\n" . config('app.name') . ' Team');
    }
}
