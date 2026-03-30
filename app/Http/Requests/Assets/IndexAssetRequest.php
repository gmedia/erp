<?php

namespace App\Http\Requests\Assets;

class IndexAssetRequest extends AbstractAssetListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->assetListingRules(
                'id,asset_code,name,purchase_date,purchase_cost,status,created_at,category,branch,location,department,employee,supplier',
            ),
            [
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
                'page' => ['nullable', 'integer', 'min:1'],
            ],
        );
    }
}
