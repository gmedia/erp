<?php

namespace App\Http\Requests\InventoryStocktakes;

class UpdateInventoryStocktakeRequest extends AbstractInventoryStocktakeRequest
{
    public function rules(): array
    {
        $stocktakeId = $this->route('inventoryStocktake')->id ?? $this->route('id');

        return array_merge(
            [
                'stocktake_number' => [
                    'sometimes',
                    'nullable',
                    'string',
                    'max:255',
                    'unique:inventory_stocktakes,stocktake_number,' . $stocktakeId,
                ],
            ],
            $this->prefixRulesWithSometimes($this->inventoryStocktakeBaseRules()),
            $this->updateInventoryStocktakeItemRules(),
        );
    }

    /**
     * @param  array<string, array<int, string>>  $rules
     * @return array<string, array<int, string>>
     */
    protected function prefixRulesWithSometimes(array $rules): array
    {
        return collect($rules)
            ->map(fn (array $fieldRules) => ['sometimes', ...$fieldRules])
            ->all();
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function updateInventoryStocktakeItemRules(): array
    {
        return array_merge(
            [
                'items' => ['sometimes', 'array'],
            ],
            collect($this->inventoryStocktakeItemRules())
                ->except('items')
                ->all(),
        );
    }
}
