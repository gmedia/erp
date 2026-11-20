<?php

namespace App\Http\Requests\InventoryStocktakes;

use App\Models\InventoryStocktake;

class UpdateInventoryStocktakeRequest extends AbstractInventoryStocktakeRequest
{
    public function rules(): array
    {
        $stocktake = $this->route('inventory_stocktake') ?? $this->route('inventoryStocktake');
        $stocktakeId = $stocktake instanceof InventoryStocktake ? $stocktake->getKey() : $stocktake;

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
