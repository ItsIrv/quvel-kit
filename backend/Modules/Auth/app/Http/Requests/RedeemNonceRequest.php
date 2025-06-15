<?php

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Rules\NonceRule;

class RedeemNonceRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nonce' => ['required', ...NonceRule::RULES],
        ];
    }
}
