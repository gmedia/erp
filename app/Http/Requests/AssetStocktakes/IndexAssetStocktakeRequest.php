<?php

namespace App\Http\Requests\AssetStocktakes;

class IndexAssetStocktakeRequest extends AbstractAssetStocktakeListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->assetStocktakeListingRules(
                'id,ulid,reference,branch,planned_at,performed_at,status,created_by,created_at,updated_at',
            ),
            $this->paginationRules(),
        );
    }
}
