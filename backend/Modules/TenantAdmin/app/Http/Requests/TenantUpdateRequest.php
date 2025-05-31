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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant');
        
        return [
            'name' => ['nullable', 'string', 'min:3', 'max:255'],
            'domain' => ['nullable', 'string', 'min:3', 'max:255', "unique:tenants,domain,{$tenantId}"],
            'status' => ['nullable', 'in:active,inactive,suspended'],
        ];
    }

    /**
     * Get custom error messages
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