<?php

namespace App\Http\Requests\AssetModels;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetModelListingRequest extends BaseListingRequest
{
    protected function assetModelListingRules(string $sortBy): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            ],
            $this->listingSortRules($sortBy),
        );
    }
}
