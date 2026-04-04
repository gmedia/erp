<?php

namespace App\Http\Requests;

abstract class AbstractProductUnitItemsUpdateRequest extends AbstractItemsUpdateRequest
{
    /**
     * @return array<int, string>
     */
    protected function itemsRules(): array
    {
        return ['required', 'array', 'min:1'];
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function itemRules(): array
    {
        return array_merge([
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.unit_id' => ['required', 'exists:units,id'],
        ], $this->additionalItemRules());
    }

    /**
     * @return array<string, array<int, string>>
     */
    abstract protected function additionalItemRules(): array;
}
