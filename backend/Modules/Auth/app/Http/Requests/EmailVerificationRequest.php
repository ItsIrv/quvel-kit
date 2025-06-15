<?php

namespace Modules\Auth\Http\Requests;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserFindService;
use Modules\Auth\Services\UserAuthenticationService;
use Throwable;

/**
 * Overwrites the default authorize method to use the public_id instead of the primary key.
 * This also checks the config to see if the email verification should be done before login.
 */
class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    /**
     * The resolved user for verification.
     */
    protected ?User $verificationUser = null;

    /**
     * Determine if the verification request is authorized.
     */
    public function authorize(): bool
    {
        $publicId          = (string) $this->route('id');
        $hash              = (string) $this->route('hash');
        $verifyBeforeLogin = config('auth.verify_email_before_login') === true;

        if ($verifyBeforeLogin) {
            try {
                $user = app(UserFindService::class)->findByPublicId($publicId);
                if ($user === null || !hash_equals($hash, sha1($user->getEmailForVerification()))) {
                    return false;
                }

                $this->verificationUser = $user;

                return true;
            } catch (Throwable) {
                return false;
            }
        }

        // User must be logged in
        if (!app(UserAuthenticationService::class)->check()) {
            throw new HttpResponseException(app(FrontendService::class)->redirect());
        }

        $user = $this->user();
        if ($user === null) {
            return false;
        }
        if (!hash_equals($publicId, (string) ($user->public_id ?? $user->getKey()))) {
            return false;
        }

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return false;
        }

        $this->verificationUser = $user;

        return true;
    }

    /**
     * Mark the user's email as verified.
     */
    public function fulfill(): void
    {
        $user = $this->verificationUser;

        if ($user !== null && !$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
