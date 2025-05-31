<?php

namespace Modules\TenantAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['boolean'],
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.min' => 'Username must be at least 3 characters.',
            'password.required' => 'Password is required.',
        ];
    }
}