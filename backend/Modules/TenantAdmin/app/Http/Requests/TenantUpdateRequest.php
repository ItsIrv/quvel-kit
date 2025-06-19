<?php

namespace Modules\TenantAdmin\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TenantUpdateRequest extends FormRequest
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
            'name.min' => 'Tenant name must be at least 3 characters.',
            'domain.unique' => 'This domain is already in use.',
            'status.in' => 'Invalid status. Must be active, inactive, or suspended.',
        ];
    }
}
