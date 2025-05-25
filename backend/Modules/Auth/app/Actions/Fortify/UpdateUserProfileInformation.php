<?php

namespace Modules\Auth\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Modules\Auth\Rules\EmailRule;
use Modules\Auth\Rules\NameRule;

/**
 * Fortify action to update a user's profile information.
 *
 * This action validates and updates a user's profile information, including
 * their name and email address. If the email address is changed and the user
 * must verify their email, the email verification status is reset and a new
 * verification email is sent.
 */
class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * The validation factory implementation.
     */
    protected ValidationFactory $validator;

    /**
     * Create a new action instance.
     */
    public function __construct(ValidationFactory $validator)
    {
        $this->validator = $validator;
    }
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, string>  $input The input data containing the profile information
     */
    public function update(User $user, array $input): void
    {
        // Validate the profile information
        $this->validator->make($input, [
            'name' => ['required', ...NameRule::RULES],
            // 'email' => [
            //     'required',
            //     'string',
            //     EmailRule::default(),
            //     Rule::unique('users')->ignore($user->id),
            // ],
        ])->validateWithBag('updateProfileInformation');

        // Handle email verification if email has changed
        if (
            $input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail
        ) {
            $this->updateVerifiedUser($user, $input);
        } else {
            // Update the user's profile information
            $user->forceFill([
                'name'  => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information and reset verification status.
     *
     * @param  User  $user The user whose profile is being updated
     * @param  array<string, string>  $input The input data containing the profile information
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        // Update the user's profile information and reset verification status
        $user->forceFill([
            'name'              => $input['name'],
            'email'             => $input['email'],
            'email_verified_at' => null,
        ])->save();

        // Send a new verification email
        $user->sendEmailVerificationNotification();
    }
}
