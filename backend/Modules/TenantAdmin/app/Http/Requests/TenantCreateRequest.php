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
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'domain' => ['required', 'string', 'min:3', 'max:255', 'unique:tenants,domain'],
            'database' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:tenants,public_id'],
        ];
    }

    /**
     * Get custom error messages
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
