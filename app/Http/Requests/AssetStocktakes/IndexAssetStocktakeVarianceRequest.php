<?php

namespace App\Http\Requests\AssetStocktakes;

class IndexAssetStocktakeVarianceRequest extends AbstractAssetStocktakeVarianceListingRequest
{
    public function rules(): array
    {
        return array_merge($this->assetStocktakeVarianceListingRules(), [
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
