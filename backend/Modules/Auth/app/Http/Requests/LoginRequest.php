<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\app\Rules\EmailRule;
use Modules\Auth\app\Rules\PasswordRule;

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
            'email' => ['required', 'string', EmailRule::default()],
            'password' => ['required', 'string', PasswordRule::default()],
        ];
    }
}
