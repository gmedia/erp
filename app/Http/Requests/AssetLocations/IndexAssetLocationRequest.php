<?php

namespace App\Http\Requests\AssetLocations;

use App\Http\Requests\BaseListingRequest;

class IndexAssetLocationRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'branch_id' => ['nullable', 'exists:branches,id'],
                'parent_id' => ['nullable', 'exists:asset_locations,id'],
            ],
            $this->listingSortRules('id,code,name,branch,branch_id,parent,parent_id,created_at,updated_at'),
            $this->paginationRules(),
        );
    }
}
