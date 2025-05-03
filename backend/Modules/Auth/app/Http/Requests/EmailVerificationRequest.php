<?php

namespace Modules\Auth\Http\Requests;

use Modules\Core\Services\FrontendService;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function authorize(): bool
    {
        // If verify_email_before_login is true, allow all requests since the user is not logged in
        if (config('auth.verify_email_before_login')) {
            // TODO: Since laravel does /{loggedInId}/{hash} for email verification,
            // we need a custom flow that does not depend on the user being logged in
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
