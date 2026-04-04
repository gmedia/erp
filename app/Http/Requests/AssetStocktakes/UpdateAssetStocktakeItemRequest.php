<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\AbstractItemsUpdateRequest;

class UpdateAssetStocktakeItemRequest extends AbstractItemsUpdateRequest
{
    protected function itemRules(): array
    {
        return [
            'items.*.asset_id' => ['required', 'exists:assets,id'],
            'items.*.expected_branch_id' => ['required', 'exists:branches,id'],
            'items.*.expected_location_id' => ['nullable', 'exists:asset_locations,id'],
            'items.*.found_branch_id' => ['nullable', 'required_if:items.*.result,moved', 'exists:branches,id'],
            'items.*.found_location_id' => [
                'nullable',
                'required_if:items.*.result,moved',
                'exists:asset_locations,id',
            ],
            'items.*.result' => ['required', 'in:found,missing,damaged,moved'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }
}
