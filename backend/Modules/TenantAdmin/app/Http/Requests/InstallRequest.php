<?php

namespace Modules\TenantAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InstallRequest extends FormRequest
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
            'username' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
            ],
            'installation_method' => [
                'required',
                Rule::in(['env', 'database']),
            ],
        ];
    }
}