<?php

namespace App\Http\Requests\AssetStocktakes;

class ExportAssetStocktakeVarianceRequest extends AbstractAssetStocktakeVarianceListingRequest
{
    public function rules(): array
    {
        return $this->assetStocktakeVarianceListingRules();
    }
}
