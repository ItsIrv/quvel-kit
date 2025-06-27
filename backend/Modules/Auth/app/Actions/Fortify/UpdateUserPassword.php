<?php

namespace Modules\Auth\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Modules\Auth\Logs\Actions\Fortify\UpdateUserPasswordLogs;

/**
 * Fortify action to update a user's password.
 *
 * This action validates the current password and the new password using
 * the module's password rules and updates the user's password in the database.
 */
class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    /**
     * The validation factory implementation.
     */
    protected ValidationFactory $validator;

    /**
     * The hasher implementation.
     */
    protected Hasher $hasher;

    /**
     * Create a new action instance.
     */
    public function __construct(
        ValidationFactory $validator, 
        Hasher $hasher,
        private readonly UpdateUserPasswordLogs $logs,
    ) {
        $this->validator = $validator;
        $this->hasher = $hasher;
    }

    /**
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input The input data containing the current and new password
     */
    public function update(User $user, array $input): void
    {
        $request = request();
        
        try {
            // Validate the current and new password
            $validator = $this->validator->make($input, [
                'current_password' => ['required', 'string', 'current_password:web'],
                'password'         => $this->passwordRules(),
            ], [
                'current_password.current_password' => __('The provided password does not match your current password.'),
            ]);

            $validator->validate();

            // Update the user's password
            $user->forceFill([
                'password' => $this->hasher->make($input['password']),
            ])->save();
            
            $this->logs->passwordUpdateSuccess(
                $user->id,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check if it's specifically a current password error
            if ($e->validator->errors()->has('current_password')) {
                $this->logs->passwordUpdateCurrentPasswordFailed(
                    $user->id,
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            } else {
                $this->logs->passwordUpdateValidationFailed(
                    $user->id,
                    $e->getMessage(),
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            }
            
            throw $e;
        } catch (\Exception $e) {
            $this->logs->passwordUpdateValidationFailed(
                $user->id,
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            throw $e;
        }
    }
}
