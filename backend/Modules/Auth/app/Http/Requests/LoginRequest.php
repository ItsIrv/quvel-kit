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
     * Determine if the user is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

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
