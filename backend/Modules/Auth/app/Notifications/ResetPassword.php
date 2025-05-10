<?php

namespace Modules\Auth\Notifications;

use Modules\Core\Services\FrontendService;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends ResetPasswordBase
{
    protected function buildMailMessage($url)
    {
        // Generate the password reset URL
        $url = app(FrontendService::class)->getPageUrl(
            '',
            [
                'form'  => 'password-reset',
                'token' => $this->token,
            ],
        );

        return (new MailMessage())
            ->subject(__('Reset Your Password'))
            ->greeting(__('Hello,'))
            ->line(__('You requested to reset the password for your account on :app_name.', [
                'app_name' => config('app.name'),
            ]))
            ->line(__('To reset your password, click the button below:'))
            ->action(__('Reset Password'), $url)
            ->line(__('This link will expire in :count minutes. If it expires, you’ll need to request a new password reset.', [
                'count' => config('auth.passwords.' . config('auth.defaults.passwords') . '.expire'),
            ]))
            ->line(__('If you did not request a password reset, please ignore this email. Your account remains secure.'))
            ->salutation(__('Thank you,') . "\n" . config('app.name') . ' Team');
    }
}
