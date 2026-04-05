<?php

namespace App\Http\Requests\AssetModels;

class ExportAssetModelRequest extends AbstractAssetModelListingRequest
{
    public function rules(): array
    {
        return $this->assetModelListingRules('id,model_name,manufacturer,category,asset_category_id,created_at,updated_at');
    }
}
