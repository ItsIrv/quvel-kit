<?php

namespace Modules\Auth\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;

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
     * Validate and update the user's password.
     *
     * @param  array<string, string>  $input The input data containing the current and new password
     */
    public function update(User $user, array $input): void
    {
        // Validate the current and new password
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password'         => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('The provided password does not match your current password.'),
        ])->validateWithBag('updatePassword');

        // Update the user's password
        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
