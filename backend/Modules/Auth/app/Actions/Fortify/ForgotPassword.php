<?php

namespace Modules\Auth\Actions\Fortify;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Modules\Auth\Logs\Actions\Fortify\ForgotPasswordLogs;

/**
 * Sends a password reset link to the user's email.
 */
class ForgotPassword
{
    public function __construct(
        private readonly PasswordResetLinkController $passwordResetLinkController,
        private readonly ForgotPasswordLogs $logs,
    ) {
    }
    public function __invoke(Request $request): Responsable
    {
        $email = $request->input('email', '');
        
        try {
            $response = $this->passwordResetLinkController->store($request);
            
            $this->logs->resetLinkSent(
                $email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            return $response;
        } catch (Exception $e) {
            $this->logs->resetLinkFailed(
                $email,
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            throw $e;
        }
    }
}
