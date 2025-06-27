<?php

namespace Modules\Auth\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Modules\Auth\Logs\Actions\Fortify\PasswordResetLogs;

/**
 * Fortify action to reset a user's password.
 *
 * This action validates the new password using the module's password rules
 * and updates the user's password in the database.
 */
class PasswordReset implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(
        private readonly PasswordResetLogs $logs,
    ) {
    }

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     * @return void
     */
    public function reset(User $user, array $input): void
    {
        $request = request();
        
        try {
            Validator::make($input, [
                'password' => $this->passwordRules(),
            ])->validate();

            $user->forceFill([
                'password' => Hash::make($input['password']),
            ])->save();
            
            $this->logs->passwordResetSuccess(
                $user->id,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
        } catch (\Exception $e) {
            $this->logs->passwordResetValidationFailed(
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            throw $e;
        }
    }
}
