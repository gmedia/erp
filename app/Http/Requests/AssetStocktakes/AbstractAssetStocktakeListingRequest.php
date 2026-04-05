<?php

namespace App\Http\Requests\AssetStocktakes;

use App\Http\Requests\BaseListingRequest;

abstract class AbstractAssetStocktakeListingRequest extends BaseListingRequest
{
    protected function assetStocktakeListingRules(string $sortBy, string $branchField = 'branch_id'): array
    {
        return [
            'search' => ['nullable', 'string'],
            $branchField => ['nullable', 'exists:branches,id'],
            'status' => ['nullable', 'in:draft,in_progress,completed,cancelled'],
            ...$this->listingSortRules($sortBy),
        ];
    }
}
