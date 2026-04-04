<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class AbstractItemsUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => $this->itemsRules(),
            ...$this->itemRules(),
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function itemsRules(): array
    {
        return ['required', 'array'];
    }

    /**
     * @return array<string, array<int, string>>
     */
    abstract protected function itemRules(): array;
}
