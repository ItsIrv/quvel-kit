<?php

namespace Modules\TenantAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tenant name is required.',
            'name.min' => 'Tenant name must be at least 3 characters.',
            'domain.required' => 'Domain is required.',
            'domain.unique' => 'This domain is already in use.',
        ];
    }
}
