<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\ProviderRule;
use Modules\Auth\Rules\TokenRule;

/**
 * Request DTO for socialite callback.
 */
class CallbackRequest extends FormRequest
{
    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'state'    => ['required', ...TokenRule::RULES],
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
