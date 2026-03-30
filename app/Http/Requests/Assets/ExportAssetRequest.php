<?php

namespace App\Http\Requests\Assets;

class ExportAssetRequest extends AbstractAssetListingRequest
{
    public function rules(): array
    {
        return $this->assetListingRules('asset_code,name,purchase_date,status,created_at,category,branch');
    }
}
