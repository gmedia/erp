<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetStocktakeVarianceListingRequest extends BaseListingRequest
{
    /**
     * @param  array<string, array<int, string>>  $extraRules
     * @return array<string, array<int, string>>
     */
    protected function assetStocktakeVarianceListingRules(array $extraRules = []): array
    {
        return [
            'search' => ['nullable', 'string'],
            'asset_stocktake_id' => ['nullable', 'exists:asset_stocktakes,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'result' => ['nullable', 'in:missing,damaged,moved'],
            ...$this->listingSortRules(
                'id,stocktake_reference,asset_code,asset_name,expected_branch,' .
                    'expected_location,found_branch,found_location,result,checked_at'
            ),
            ...$extraRules,
        ];
    }
}
