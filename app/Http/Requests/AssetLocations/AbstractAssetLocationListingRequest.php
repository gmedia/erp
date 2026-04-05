<?php

namespace App\Http\Requests\AssetLocations;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetLocationListingRequest extends BaseListingRequest
{
    protected function assetLocationListingRules(string $sortBy): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch_id' => ['nullable', 'exists:branches,id'],
                'parent_id' => ['nullable', 'exists:asset_locations,id'],
            ],
            $this->listingSortRules($sortBy),
        );
    }
}
