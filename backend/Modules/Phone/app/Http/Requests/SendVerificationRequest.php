<?php

namespace Modules\Phone\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Phone\Rules\PhoneNumberRule;

class SendVerificationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new PhoneNumberRule()],
        ];
    }
}
