<?php

namespace Modules\Auth\Actions\Fortify;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;

/**
 * Verifies an email address.
 */
class ForgotPassword
{
    public function __construct(
        private readonly PasswordResetLinkController $passwordResetLinkController,
    ) {
    }
    public function __invoke(Request $request): Responsable
    {
        return $this->passwordResetLinkController->store($request);
    }
}
