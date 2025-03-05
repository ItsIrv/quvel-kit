<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\NonceRule;
use Modules\Auth\Rules\ProviderRule;

/**
 * Request DTO for socialite redirect.
 */
class RedirectRequest extends FormRequest
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
            'nonce'    => ['required', ...NonceRule::RULES],
            'provider' => ['required', ...ProviderRule::RULES()],
        ];
    }

    /**
     * Merge route parameters into request data.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'provider' => $this->route('provider'),
        ]);
    }
}
