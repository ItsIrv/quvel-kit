<?php

namespace Modules\Auth\Http\Requests;

use Modules\Core\Services\FrontendService;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function authorize(): bool
    {
        // If verify_email_before_login is true, allow all requests since the user can't be logged in
        if (config('auth.verify_email_before_login') === true) {
            return true;
        }

        // Otherwise make sure the user is logged in and the signature is valid
        $user = $this->user();

        if (!$user) {
            throw new HttpResponseException(
                app(FrontendService::class)->redirect(),
            );
        }

        return parent::authorize();
    }
}
