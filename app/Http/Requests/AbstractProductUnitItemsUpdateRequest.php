<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractProductUnitItemsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->baseItemRules(), $this->additionalItemRules());
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function baseItemRules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    abstract protected function additionalItemRules(): array;
}
