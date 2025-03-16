<?php

namespace Modules\Auth\app\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\app\Rules\EmailRule;
use Modules\Auth\app\Rules\NameRule;
use Modules\Auth\app\Rules\PasswordRule;

/**
 * Request DTO for register.
 */
class RegisterRequest extends FormRequest
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
            'name'     => ['required', ...NameRule::RULES],
        ];
    }
}
