<?php

namespace App\Http\Requests\AssetLocations;

class IndexAssetLocationRequest extends AbstractAssetLocationListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->assetLocationListingRules('id,code,name,branch,branch_id,parent,parent_id,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
