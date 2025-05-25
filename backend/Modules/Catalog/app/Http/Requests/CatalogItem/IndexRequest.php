<?php

namespace Modules\Catalog\Http\Requests\CatalogItem;

use Illuminate\Foundation\Http\FormRequest;

class IndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'filter.*' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'in:name,-name,created_at,-created_at'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
