<?php

namespace Modules\Auth\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest as BaseEmailVerificationRequest;

class EmailVerificationRequest extends BaseEmailVerificationRequest
{
    public function authorize(): bool
    {
        $user = User::findOrFail($this->route('id'));

        if (
            !hash_equals(
                (string) $this->route('hash'),
                sha1((string) $user->getEmailForVerification()),
            ) ||
            !hash_equals(
                (string) $this->route('id'),
                (string) $user->getKey()
            )
        ) {
            return false;
        }

        $this->userResolver = fn () => $user;

        return true;
    }
}
