<?php

namespace App\Http\Requests\AssetModels;

class IndexAssetModelRequest extends AbstractAssetModelListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->assetModelListingRules('id,model_name,manufacturer,category,asset_category_id,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
