<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\EmailRule;
use Modules\Auth\Rules\PasswordRule;

/**
 * Request DTO for login.
 */
class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', EmailRule::default()],
            'password' => ['required', 'string', PasswordRule::default()],
        ];
    }
}
