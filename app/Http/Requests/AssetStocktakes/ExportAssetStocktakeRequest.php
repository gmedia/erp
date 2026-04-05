<?php

namespace App\Http\Requests\AssetStocktakes;

class ExportAssetStocktakeRequest extends AbstractAssetStocktakeListingRequest
{
    public function rules(): array
    {
        return $this->assetStocktakeListingRules(
            'id,reference,planned_at,performed_at,status,created_at,updated_at',
            'branch',
        );
    }
}
