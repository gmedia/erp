<?php

namespace App\Http\Requests\AssetModels;

use App\Http\Requests\BaseListingRequest;

class ExportAssetModelRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'asset_category_id' => ['nullable', 'exists:asset_categories,id'],
            ],
            $this->listingSortRules('id,model_name,manufacturer,category,asset_category_id,created_at,updated_at'),
        );
    }
}
