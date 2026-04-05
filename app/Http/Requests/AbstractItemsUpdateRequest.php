<?php

namespace App\Http\Requests;

abstract class AbstractItemsUpdateRequest extends AuthorizedFormRequest
{
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
