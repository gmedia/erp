<?php

namespace App\Http\Requests\AssetLocations;

class ExportAssetLocationRequest extends AbstractAssetLocationListingRequest
{
    public function rules(): array
    {
        return $this->assetLocationListingRules('code,name,branch,parent,created_at,updated_at');
    }
}
